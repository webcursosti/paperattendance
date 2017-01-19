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
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Belongs to plugin PaperAttendance

require_once (dirname(dirname(dirname(__FILE__)))."/config.php");
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once("$CFG->libdir/excellib.class.php");
require_once ($CFG->dirroot."/local/paperattendance/forms/export_form.php");

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

// Possible actions -> view, scan or students attendance . Standard is view mode
$action = optional_param("action", "viewform", PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);

$context = context_course::instance($COURSE->id);
$url = new moodle_url("/local/paperattendance/export.php", array('courseid' => $courseid));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

$contextsystem = context_system::instance();

//for navbar
$course = $DB->get_record("course",array("id" => $courseid));
$nodata = false;

require_login();
if (isguestuser()){
	die();
}

//Begins Teacher's View
$isteacher = paperattendance_getteacherfromcourse($courseid, $USER->id);

$isstudent = paperattendance_getstudentfromcourse($courseid, $USER->id);

if( $isteacher || is_siteadmin($USER)) {
	//breadcrumb for navigation
	$PAGE->navbar->ignore_active();
	$PAGE->navbar->add(get_string('courses', 'local_paperattendance'), new moodle_url('/course/index.php'));
	$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $courseid)));
	$PAGE->navbar->add(get_string('pluginname', 'local_paperattendance'));
	$PAGE->navbar->add(get_string('exporttitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/export.php", array("courseid" => $courseid)));
	
	if($action == "viewform"){
		$exportform = new paperattendance_export_form(null, array("courseid"=>$courseid));
		if($formdata = $exportform->get_data()){
			//form data analysis
			$types = array();
			foreach ($formdata->sesstype as $sesstype=>$type){
				$types[] = $sesstype;
			}
			list($selectedtypes, $paramsesstypes) = $DB->get_in_or_equal($types);
			$parametros = array_merge($paramsesstypes, array($courseid));
			//excel parameters
			$filename = $course->fullname."_attendances_".date('dmYHi');
			$title = $course->fullname;
			$header = array();
			$data = array();
			//Select all students from the last list
			$sqlstudentlist = "SELECT u.id, 
							u.firstname, 
							u.lastname, 
							u.email
							FROM {paperattendance_presence} AS p
							INNER JOIN {paperattendance_session} s ON (s.id = p.sessionid)
							INNER JOIN {paperattendance_sessmodule} sm ON (sm.sessionid = s.id)
							INNER JOIN {user} AS u ON (u.id = p.userid)
							WHERE sm.date = (SELECT MAX(sm.date) AS date
							              FROM {paperattendance_sessmodule} AS sm
							              INNER JOIN {paperattendance_session} AS s ON (s.id = sm.sessionid AND s.courseid = ?))
							GROUP BY u.id
							ORDER BY u.lastname ASC";
			$studentlist = $DB->get_records_sql($sqlstudentlist, array($courseid));
			array_push($header,"LastName", "FirstName", "Email");
			$list = new stdClass();
			$list->lastnames = array();
			$list->firstnames = array();
			$list->emails = array();
			$list->studentsid = array();
			foreach($studentlist as $student){
				$list->studentsid[] = $student->id;
				$list->lastnames[] = $student->lastname;
				$list->firstnames[] = $student->firstname;
				$list->emails[] = $student->email;
			}
			$data[] = $list->lastnames;
			$data[] = $list->firstnames;
			$data[] = $list->emails;
			//Select course sessions
			$parametros = array_merge($parametros, array($formdata->initdate, $formdata->enddate));
			$getsessions = "SELECT s.id,
								sm.date,
								CONCAT( m.initialtime, '-', m.endtime) AS hour,
								s.description AS description
								FROM {paperattendance_session} AS s
								INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid)
								INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id)
								WHERE s.description $selectedtypes AND s.courseid = ? AND sm.date BETWEEN ? AND ?
								ORDER BY sm.date ASC";
			$sessions = $DB->get_records_sql($getsessions, $parametros);
			//sql in for presences of studdents for each session
			list($studentids, $paramstudentsid) = $DB->get_in_or_equal($list->studentsid);
			foreach ($sessions as $session){
				$params = array_merge(array($session->id), $paramstudentsid);
				$header[] = date('d-m-Y',$session->date)." ".$session->hour." ".paperattendance_returnattendancedescription(false, $session->description);
				//get session attendances
				$getpresences = "SELECT  u.id, 
								IFNULL(p.status,0) AS status
								FROM {paperattendance_presence} AS p
								RIGHT JOIN {user} AS u ON (u.id = p.userid AND p.sessionid = ?)
								WHERE u.id $studentids
								ORDER BY u.lastname ASC";
				$presences = $DB->get_records_sql($getpresences, $params);
				$sess = array();
				foreach($presences as $presence){
					$sess[] = $presence->status;
				}	
				$data[] = $sess;
			}
			if(count($sessions)==0){
				$nodata = true;
			}
			else{
				$nodata = false;
				paperattendance_exporttoexcel($title, $header, $filename, $data);
			}
		}
	}
				
	if($action == "viewform"){
		echo $OUTPUT->header();
		echo $OUTPUT->tabtree(paperattendance_history_tabs($course->id), "export");
		if($nodata){
			echo html_writer::div(get_string("nodatatoexport","local_paperattendance"),"alert alert-danger", array("role"=>"alert"));
		}
		$exportform->display();
		echo $OUTPUT->footer();
	}
}