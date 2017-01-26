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
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $CFG, $DB, $USER;

require_login();
if (isguestuser()) {
	die();
}

$action = required_param('action', PARAM_ALPHA);
$omegaid = optional_param('omegaid', null, PARAM_TEXT);
$diasemana = optional_param('diasemana', null, PARAM_TEXT);
$data = optional_param('result', null, PARAM_TEXT);
$path = optional_param('path', 0, PARAM_INT);
$courseid = optional_param("courseid", 1, PARAM_INT);
$category = optional_param('category', 1, PARAM_INT);

switch ($action) {
	case 'curlgetmoduloshorario' :
		require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
		$token = $CFG->paperattendance_omegatoken;
		$url = $CFG->paperattendance_omegagetmoduloshorariosurl;
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
		$isteacher = paperattendance_getteacherfromcourse($courseid, $USER->id);
		if(!has_capability("local/paperattendance:printsecre", $context) && !$isteacher && !is_siteadmin($USER) && !has_capability("local/paperattendance:print", $context)){
			print_error(get_string('notallowedprint', 'local_paperattendance'));
		}
		$curl = curl_init();
		
		$fields = array (
				"diaSemana" => $diasemana,
				"seccionId" => $omegaid,
				"token" => $token
		);
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$result = curl_exec ($curl);
		curl_close ($curl);

		echo  json_encode($result);
		break;	
	case 'getcourses' :
		if($courseid > 1){
			if($course = $DB->get_record("course", array("id" => $courseid))){
				$context = context_coursecat::instance($course->category);
				$path = $course->category;
			}
		}else if($category > 1){
			$context = context_coursecat::instance($category);
			$path = $category;
		}else{
			$context = context_system::instance();
		}
		
		$contextsystem = context_system::instance();
		if (! has_capability('local/paperattendance:printsearch', $context) && ! has_capability('local/paperattendance:printsearch', $contextsystem)) {
			print_error(get_string('notallowedprint', 'local_paperattendance'));
		}
		
		$filter = array("%/".$path."%", "%".$data."%", $data."%");
		$sqlcourses = "SELECT c.id,
			c.fullname,
			cat.name,
			CONCAT( u.firstname, ' ', u.lastname) as teacher
			FROM {user} AS u
			INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
			INNER JOIN {context} ct ON (ct.id = ra.contextid)
			INNER JOIN {course} c ON (c.id = ct.instanceid AND c.idnumber IS NOT NULL)
			INNER JOIN {role} r ON (r.id = ra.roleid AND r.id IN ( 3, 4))
			INNER JOIN {course_categories} as cat ON (cat.id = c.category)
			WHERE (cat.path like ?) AND (CONCAT( u.firstname, ' ', u.lastname) like ? OR c.fullname like ?)
			GROUP BY c.id";
		$courses = $DB->get_records_sql($sqlcourses, $filter);
		
		echo json_encode($courses);
		break;
}