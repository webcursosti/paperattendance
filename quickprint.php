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

$courseid = required_param("courseid", PARAM_INT);
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
	
	$modules = json_encode($result);
	var_dump($modules);
	echo "<br>";
	var_dump(json_decode($modules));
	foreach($modules as $module){
		echo $module->horaInicio;
		echo "<br>";
	}
}

