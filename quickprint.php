<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 *
*
* @package    local
* @subpackage paperattendance
* @copyright  2017 Jorge Cabané (jcabane@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once (dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
require_once ("locallib.php");

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

require_login();
if (isguestuser()) {
	print_error("ACCESS DENIED");
	die();
}

$courseid = optional_param("courseid", 1616, PARAM_INT);
$action = optional_param("action", "add", PARAM_TEXT);
$category = optional_param('categoryid', 1, PARAM_INT);

if($courseid > 1){
	if($course = $DB->get_record("course", array("id" => $courseid)) ){
		if($course->idnumber != NULL){
			$context = context_coursecat::instance($course->category);
		}
	}
	else{
		$context = context_system::instance();
	}
}else if($category > 1){
	$context = context_coursecat::instance($category);
}else{
	$context = context_system::instance();
}

if(!has_capability("local/paperattendance:printsecre", $context) && !$isteacher && !is_siteadmin($USER) && !has_capability("local/paperattendance:print", $context)){
	print_error(get_string('notallowedprint', 'local_paperattendance'));
}

// Page navigation and URL settings.
$pagetitle = get_string('printtitle', 'local_paperattendance');
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin ( 'ui' );
$PAGE->requires->jquery_plugin ( 'ui-css' );

$course = $DB->get_record("course",array("id" => $courseid));

if (paperattendance_checktoken($CFG->paperattendance_omegatoken)){
	//CURL get modulos horario
	$curl = curl_init();
	
// 	$fields = array (
// 			"diaSemana" => date('w'),
// 			"seccionId" => $course -> idnumber,
// 			"token" => $token
// 	);
	$url = $CFG->paperattendance_omegagetmoduloshorariosurl;
	$token = $CFG->paperattendance_omegatoken;
	$fields = array("diaSemana" => 5, "seccionId"=> 46386, "token" => $token);
	
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($curl, CURLOPT_POST, TRUE);
	curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
	curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
	$result = curl_exec ($curl);
	curl_close ($curl);
	
	$modules = array();
	$modules = json_decode($result);
	
	//select teacher from course
	$teachersquery = "SELECT u.id,
				CONCAT (u.firstname, ' ', u.lastname) AS name,
				GROUP_CONCAT(e.enrol) AS enrol
				FROM {user_enrolments} ue
				INNER JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = ?)
				INNER JOIN {context} c ON (c.contextlevel = 50 AND c.instanceid = e.courseid)
				INNER JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.roleid = ? AND ra.userid = ue.userid)
				INNER JOIN {user} u ON (ue.userid = u.id)
				ORDER BY u.lastname";
	
	$teachers = $DB->get_records_sql($teachersquery, array($courseid,'3'));

	$enrolincludes = explode("," ,$CFG->paperattendance_enrolmethod);
	
	foreach ($teachers as $teacher){
		
		$enrolment = explode(",", $teacher->enrol);
		// Verifies that the teacher is enrolled through a valid enrolment and that we haven't added him yet.
		if (count(array_intersect($enrolment, $enrolincludes)) == 0 || isset($arrayteachers[$teacher->id])) {
			continue;
		}
		$requestor = $teacher->id;
	}

	$requestorinfo = $DB->get_record("user", array("id" => $requestor));
	
	//session date from today in unix
	$sessiondate = time();
	
	//Curricular class
	$description = 0;
	
	$path = $CFG -> dataroot. "/temp/local/paperattendance/";
	//list($path, $filename) = paperattendance_create_qr_image($courseid."*".$requestor."*", $path);
	
	$uailogopath = $CFG->dirroot . '/local/paperattendance/img/uai.jpeg';
	$webcursospath = $CFG->dirroot . '/local/paperattendance/img/webcursos.jpg';
	$timepdf = time();
	$attendancepdffile = $path . "/print/paperattendance_".$courseid."_".$timepdf.".pdf";
	
	if (!file_exists($path . "/print/")) {
		mkdir($path . "/print/", 0777, true);
	}
	
	$pdf = new PDF();
	$pdf->setPrintHeader(false);
	$pdf->setPrintFooter(false);
	
	// Get student for the list
	$studentinfo = paperattendance_students_list($context->id, $course);
	
	// We validate the number of students as we are filtering by enrolment.
	// Type after getting the data.
	$numberstudents = count($studentinfo);
	if ($numberstudents == 0) {
		throw new Exception('No students to print');
	}
	// Contruction string for QR encode
	foreach ($modules as $module){
		$mod = explode(":", $module->horaInicio);
		$module = $mod[0].":".$mod[1];
		$modquery = $DB->get_record("paperattendance_module",array("initialtime" => $module));
		$moduleid = $modquery -> id;
		
		$key = $moduleid."*".$module->horaInicio."*".$module->horaFin;
		
		$stringqr = $courseid."*".$requestor."*".$moduleid."*".$sessiondate."*";
		
		paperattendance_draw_student_list($pdf, $uailogopath, $course, $studentinfo, $requestorinfo, $key, $path, $stringqr, $webcursospath, $sessiondate, $description);
		
	}
	
	// Created new pdf
	$pdf->Output($attendancepdffile, "F");
	
	$fs = get_file_storage();
	$file_record = array(
			'contextid' => $context->id,
			'component' => 'local_paperattendance',
			'filearea' => 'draft',
			'itemid' => 0,
			'filepath' => '/',
			'filename' => "paperattendance_".$courseid."_".$timepdf.".pdf",
			'timecreated' => time(),
			'timemodified' => time(),
			'userid' => $USER->id,
			'author' => $USER->firstname." ".$USER->lastname,
			'license' => 'allrightsreserved'
	);
	
	// If the file already exists we delete it
	if ($fs->file_exists($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf")) {
		$previousfile = $fs->get_file($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf");
		$previousfile->delete();
	}
	// Info for the new file
	$fileinfo = $fs->create_file_from_pathname($file_record, $attendancepdffile);
	
	$url = moodle_url::make_pluginfile_url($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf");
	$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
			"src" => $url,
			"style" => "height:75vh; width:60vw"
	));
	
	}

