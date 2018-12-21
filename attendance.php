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
 * @copyright  2018 Matías Queirolo (mqueirolo@alumnos.uai.cl)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi.php");
global $CFG, $DB, $OUTPUT, $USER, $PAGE;
// User must be logged in.
require_login();
if (isguestuser()) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
	die();
}

//Possible actions -> view, save . Standard is view mode
$action = optional_param("action", "view", PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

//Context definition
$context = context_course::instance($COURSE->id);

//Page settings
$url = new moodle_url("/local/paperattendance/attendance.php", array('courseid' => $courseid));
$pagetitle = get_string('printtitle', 'local_paperattendance');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout("standard");
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin ( 'ui' );
$PAGE->requires->jquery_plugin ( 'ui-css' );

$isteacher = paperattendance_getteacherfromcourse($courseid, $USER->id);
if( !$isteacher && !is_siteadmin($USER) ){
	print_error(get_string('notallowedprint', 'local_paperattendance'));
}

//for navbar
$course = $DB->get_record("course",array("id" => $courseid));

//breadcrumb for navigation
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('courses', 'local_paperattendance'), new moodle_url('/course/index.php'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $courseid)));
$PAGE->navbar->add(get_string('pluginname', 'local_paperattendance'));
$PAGE->navbar->add(get_string('historytitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/history.php", array("courseid" => $courseid)));

if($action == "view"){
	
	if (paperattendance_checktoken($CFG->paperattendance_omegatoken)){
		//CURL get modulos horario
		$curl = curl_init();
		
		$url = $CFG->paperattendance_omegagetmoduloshorariosurl;
		$token = $CFG->paperattendance_omegatoken;
		
		$fields = array (
				"diaSemana" => date('w'),
				"seccionId" => $course -> idnumber,
				"token" => $token
		);
		
		//	$fields = array("diaSemana" => 5, "seccionId"=> 46386, "token" => $token);
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$result = curl_exec ($curl);
		curl_close ($curl);
		
		#$modules = array();
		$modules = json_decode($result);
		var_dump($modules);
		
		if(count($modules) == 0){
			echo get_string("nothingtoprint","local_paperattendance");
			die();
		}
	}
}

echo $OUTPUT->header();
echo $OUTPUT->footer();
?>