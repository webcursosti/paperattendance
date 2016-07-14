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
* @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl) 
* @copyright  2016 Hans Jeria (hansjeria@gmail.com) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once (dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . "/local/paperattendance/forms/print_form.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
require_once ($CFG->dirroot . "/mod/emarking/lib/openbub/ans_pdf_open.php");
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi.php");
require_once ($CFG->dirroot . "/mod/emarking/print/locallib.php");
require_once ("locallib.php");
global $DB, $PAGE, $OUTPUT;
require_login();
if (isguestuser()) {
	die();
}

$courseid = required_param("courseid", PARAM_INT);
$action = optional_param("action", "add", PARAM_INT);

$context = context_system::instance();
if (! has_capability("local/paperattendance:print", $context)) {
	print_error("ACCESS DENIED");
}

$urlprint = new moodle_url("/local/paperattendance/print.php");
// Page navigation and URL settings.
$pagetitle = "Imprimir lista de asistencia";
$PAGE->set_context($context);
$PAGE->set_url($urlprint);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

$course = $DB->get_record("course",array("id" => $courseid));

if($action == "add"){
	// Add the print form 
	$addform = new print_form(null, array("courseid" => $courseid));
	// If the form is cancelled, redirect to course.
	if ($addform->is_cancelled()) {
		$backtocourse = new moodle_url("/course/view.php", array('id' => $courseid));
		redirect($backtocourse);
	}
	else if ($data = $addform->get_data()) {
		// Create the PDF with the students
		// id teacher
		$requestor = $data->requestor;
		$requestorinfo = $DB->get_record("user", array("id" => $requestor));
		// date for session
		$sessiondate = $data->sessiondate;
		// array idmodule => {0 = no checked, 1 = checked}
		$modules = $data->modules;
		
		list($path, $filename) = paperattendance_create_qr_image($courseid."*".$requestor."*");
		
		$uailogopath = $CFG->dirroot . '/local/paperattendance/img/uai.jpeg';
		$webcursospath = $CFG->dirroot . '/local/paperattendance/img/webcursos.jpg';
		
		$attendancepdffile = $path . "/paperattendance_".$courseid.".pdf";
		$pdf = new PDF();		
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
	
		//TODO: Add enrolments for omega, Remember change manual.
		$enrolincludes = array("manual");
		$filedir = $CFG->dataroot . "/temp/emarking/$context->id";
		$userimgdir = $filedir . "/u";
		$students = paperattendance_get_students_for_printing($course);
		
		$studentinfo = array();
		// Fill studentnames with student info (name, idnumber, id and picture).
		foreach($students as $student) {
			$studentenrolments = explode(",", $student->enrol);
			// Verifies that the student is enrolled through a valid enrolment and that we haven't added her yet.
			if (count(array_intersect($studentenrolments, $enrolincludes)) == 0 || isset($studentinfo[$student->id])) {
				continue;
			}
			// We create a student info object.
			$studentobj = new stdClass();
			$studentobj->name = substr("$student->lastname, $student->firstname", 0, 65);
			$studentobj->idnumber = $student->idnumber;
			$studentobj->id = $student->id;
			$studentobj->picture = emarking_get_student_picture($student, $userimgdir);
			// Store student info in hash so every student is stored once.
			$studentinfo[$student->id] = $studentobj;
		}
		// We validate the number of students as we are filtering by enrolment.
		// type after getting the data.
		$numberstudents = count($studentinfo);
		if ($numberstudents == 0) {
			throw new Exception('No students to print');
		}
		
		paperattendance_draw_student_list($pdf, $uailogopath, $course, $studentinfo, $requestorinfo, $modules,$path."/".$filename, $webcursospath);
		
		$pdf->Output($attendancepdffile, "F"); // Se genera el nuevo pdf.
		$pdf = null;
		//unlink($path."/".$filename);
		//$action = "download";
	
	}
}

if($action == "add"){
	$PAGE->set_heading($pagetitle);
	echo $OUTPUT->header();
	
	echo html_writer::nonempty_tag("h2", $course->shortname." - ".$course->fullname);
	
	$addform->display();
}

if($action == "download" && isset($attendancepdffile)){
	

	//unlink($attendancepdffile);
}
echo $OUTPUT->footer();
