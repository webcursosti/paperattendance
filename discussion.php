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
* @copyright  2017 Cristobal Silva (cristobal.isilvap@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Belongs to plugin PaperAttendance

require_once (dirname(dirname(dirname(__FILE__)))."/config.php");
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot."/local/paperattendance/forms/response_form.php");

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

$action = optional_param("action", "view", PARAM_TEXT);
$iddiscussion = optional_param("iddiscussion", null, PARAM_INT);
$idcourse = required_param('courseid', PARAM_INT);

$context = context_course::instance($COURSE->id);
$url = new moodle_url("/local/paperattendance/discussion.php", array('courseid' => $idcourse));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");
$course = $DB->get_record("course",array("id" => $idcourse));

//breadcrumb for navigation
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('courses', 'local_paperattendance'), new moodle_url('/course/index.php'));
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $idcourse)));
$PAGE->navbar->add(get_string('pluginname', 'local_paperattendance'));
$PAGE->navbar->add(get_string('discussiontitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/discussion.php", array("courseid" => $idcourse)));


$contextsystem = context_system::instance();

/*if(!has_capability('local/paperattendance:printsecre', $contextsystem)){
	print_error("ACCESS DENIED");
}*/

require_login();
if (isguestuser()){
	die();
}

//It is verified if the user is student or teacher
$isteacher = paperattendance_getteacherfromcourse($idcourse, $USER->id);
$isstudent = paperattendance_getstudentfromcourse($idcourse, $USER->id);
//url to go back to the course
$backbuttonurl = new moodle_url("/course/view.php", array("id" => $idcourse));

if( $isteacher || is_siteadmin($USER)) {
	if($action == "view"){
		$discussionquery = "SELECT d.id, 
							d.comment, 
							p.userid, 
							d.result, 
							CONCAT(u.firstname, ' ', u.lastname) AS name, 
							FROM_UNIXTIME(sm.date,'%a %b %d') AS date, 
							m.name AS module
							FROM {paperattendance_discussion} d
							INNER JOIN {paperattendance_presence} p ON (d.presenceid = p.id)
							INNER JOIN {paperattendance_session} s ON (p.sessionid = s.id)
							INNER JOIN {user} u ON (p.userid = u.id)
							INNER JOIN {paperattendance_sessmodule} sm ON (sm.sessionid = s.id)
							INNER JOIN {paperattendance_module} m ON (m.id = sm.moduleid)
							WHERE s.courseid = ? AND d.result = ?";
		$discussions = $DB->get_records_sql($discussionquery, array($idcourse, 0));
		$discussiontable = new html_table();
		$discussiontable->head = array(
				"#",
				get_string('studentname', 'local_paperattendance'),
				get_string('comment', 'local_paperattendance'),
				get_string('attdate', 'local_paperattendance'),
				get_string('module', 'local_paperattendance'),
				get_string('response', 'local_paperattendance')
		);
		$counter = 1;
		foreach($discussions as $discussion){
			if($discussion->result == 0){
				$formbuttonurl = new moodle_url("/local/paperattendance/discussion.php", array("action"=>"response","iddiscussion" => $discussion->id,"courseid" => $idcourse));
				$discussiontable->data[] = array(
						$counter,
						$discussion->name,
						$discussion->comment,
						$discussion->date,
						$discussion->module,
						html_writer::nonempty_tag("div", $OUTPUT->single_button($formbuttonurl, get_string('response', 'local_paperattendance')))
				);
				$counter++;
			}
		}
	}
	if($action == "response"){
		$responseform = new paperattendance_response_form(null, array(
				"courseid" => $idcourse,
				"discussionid" => $iddiscussion
		));
		
		if($responseform->is_cancelled()){
			$goback = new moodle_url("/local/paperattendance/discussion.php", array(
					"courseid" => $idcourse
			));
			redirect($goback);
		}
		else if($data = $responseform->get_data()){
			$response = new stdClass();
			$response->id = $iddiscussion;
			$response->response = $data->response;
			//result equals: 1 is still absence, 2 is changed to present
			$response->result = $data->result;
			$DB->update_record("paperattendance_discussion", $response);
			
			if($data->result==2){
				$presencequery = "SELECT p.id, p.omegaid
							FROM {paperattendance_discussion} d
							INNER JOIN {paperattendance_presence} p ON (d.presenceid = p.id)
							WHERE d.id = ?";
				$presence = $DB->get_record_sql($presencequery, array($iddiscussion));
				$attendance = new stdClass();
				$attendance->id = $presence->id;
				$attendance->status = 1;
				$attendance->lastmodified = time();
				$DB->update_record("paperattendance_presence",$attendance);
				if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
					paperattendance_omegaupdateattendance(1, $presence->omegaid);
				}
			}
			$goback = new moodle_url("/local/paperattendance/discussion.php", array(
					"courseid" => $idcourse
			));
			redirect($goback);	
		}
	}
	echo $OUTPUT->header();
	if($action == "view"){
		echo html_writer::div(get_string("discussionhelp","local_paperattendance"),"alert alert-info", array("role"=>"alert"));
		if(count($discussions) > 0){
			echo html_writer::table($discussiontable);
		}
		else{
			echo html_writer::nonempty_tag("h4", get_string('nonexistintingrecords', 'local_paperattendance'), array("align" => "left"));
		}
	}
	if($action == "response"){
		$responseform->display();
	}
	echo html_writer::nonempty_tag("div", $OUTPUT->single_button($backbuttonurl, get_string('backtocourse', 'local_paperattendance')), array("align" => "left"));
	echo $OUTPUT->footer();
}
if($isstudent){
	if($action == "view"){
		//icons
		$urlicon = new moodle_url("#");
		$synchronizedicon = new pix_icon("i/scheduled", get_string('synchronized', 'local_paperattendance'));
		$synchronizediconaction = $OUTPUT->action_icon(
				$urlicon,
				$synchronizedicon
				);
		$validicon = new pix_icon("i/valid", get_string('synchronized', 'local_paperattendance'));
		$validiconaction = $OUTPUT->action_icon(
				$urlicon,
				$validicon
				);
		$invalidicon = new pix_icon("i/invalid", get_string('synchronized', 'local_paperattendance'));
		$invalidiconaction = $OUTPUT->action_icon(
				$urlicon,
				$invalidicon
				);
		
		$discussionquery = "SELECT d.result, 
							d.comment, d.response, 
							FROM_UNIXTIME(sm.date,'%a %b %d') AS date, 
							m.name AS module
							FROM {paperattendance_discussion} d 
							INNER JOIN {paperattendance_presence} p ON (d.presenceid = p.id)
							INNER JOIN {paperattendance_session} s ON (p.sessionid = s.id)
							INNER JOIN {paperattendance_sessmodule} sm ON (sm.sessionid = s.id)
							INNER JOIN {paperattendance_module} m ON (m.id = sm.moduleid)
							WHERE p.userid = ? AND s.courseid = ?";
		$discussions = $DB->get_records_sql($discussionquery, array(($USER->id),$idcourse));
		$discussiontable = new html_table();
		$discussiontable->head = array(
				"#",
				get_string('comment', 'local_paperattendance'),
				get_string('attdate', 'local_paperattendance'),
				get_string('module', 'local_paperattendance'),
				get_string('result', 'local_paperattendance'),
				get_string('response', 'local_paperattendance')
		);
		//Counter for the number of results
		$counter = 1;
		foreach($discussions as $discussion){
			$discussiontable->data[] = array(
					$counter,
					$discussion->comment,
					$discussion->date,
					$discussion->module,
					//result = 0 -> scheduled icon (Attendance request wasn't solved yet)
					//result = 1 -> invalid icon (Attendance request wasn't accepted)
					//result = 2 -> valid icon (Attendance request was accepted)
					($discussion->result == 0) ? $synchronizediconaction : (($discussion->result == 1) ? $invalidiconaction : $validiconaction), 
					$discussion->response
			);
			$counter++;
		}
		
		echo $OUTPUT->header();
		echo html_writer::div(get_string("discussionstudenthelp","local_paperattendance"),"alert alert-info", array("role"=>"alert"));
		if(count($discussions) > 0){
			echo html_writer::table($discussiontable);
		}
		else{
			echo html_writer::nonempty_tag("h4", get_string('nonexistintingrecords', 'local_paperattendance'), array("align" => "left"));
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($backbuttonurl, get_string('backtocourse', 'local_paperattendance')), array("align" => "left"));
		echo $OUTPUT->footer();
		
	}
}