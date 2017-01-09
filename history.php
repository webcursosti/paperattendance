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
* @copyright  2016 Matías Queirolo (mqueirolo@alumnos.uai.cl) 
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Belongs to plugin PaperAttendance

require_once (dirname(dirname(dirname(__FILE__)))."/config.php");
require_once ($CFG->dirroot."/local/paperattendance/forms/history_form.php");
require_once ($CFG->dirroot."/local/paperattendance/forms/addstudent_form.php");
require_once ($CFG->dirroot."/local/paperattendance/forms/reviewattendance_form.php");
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');

global $DB, $PAGE, $OUTPUT, $USER, $CFG;

// Possible actions -> view, scan or students attendance . Standard is view mode
$action = optional_param("action", "view", PARAM_TEXT);
$idattendance = optional_param("idattendance", null, PARAM_INT);
$idpresence = optional_param("idpresence", null, PARAM_INT);
$idcourse = required_param('courseid', PARAM_INT);

$context = context_course::instance($COURSE->id);
$url = new moodle_url("/local/paperattendance/history.php", array('courseid' => $idcourse));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

$contextsystem = context_system::instance();


//Page
$page = optional_param('page', 0, PARAM_INT);
$perpage = 26;
//for navbar
$course = $DB->get_record("course",array("id" => $idcourse));

require_login();
if (isguestuser()){
	die();
}

//Begins Teacher's View
$isteacher = paperattendance_getteacherfromcourse($idcourse, $USER->id);

$isstudent = paperattendance_getstudentfromcourse($idcourse, $USER->id);

if( $isteacher || is_siteadmin($USER)) {
	
	//breadcrumb for navigation
	$PAGE->navbar->ignore_active();
	$PAGE->navbar->add(get_string('courses', 'local_paperattendance'), new moodle_url('/course/index.php'));
	$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $idcourse)));
	$PAGE->navbar->add(get_string('pluginname', 'local_paperattendance'));
	$PAGE->navbar->add(get_string('historytitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/history.php", array("courseid" => $idcourse)));
	
	// action-> Students Attendance
	if ($action == "studentsattendance"){
		
		$PAGE->navbar->add(get_string('studentsattendance', 'local_paperattendance'),
				new moodle_url("/local/paperattendance/history.php", array("courseid" => $idcourse , "idattendance" => $idattendance, "action" => $action)));
		
		//Query for the total count of attendances
		$getstudentsattendancecount = 'SELECT
				count(*)
				FROM {paperattendance_presence} AS p
				INNER JOIN {user} AS u ON (u.id = p.userid)
				WHERE p.sessionid = ? ';
		
		$attendancescount = $DB->count_records_sql($getstudentsattendancecount, array($idattendance));
		
		//Query to get the table data of attendances
		$getstudentsattendance = 'SELECT
				p.id AS idp,
				u.lastname,
				u.firstname,
				u.email,
				p.status,
				p.omegasync,
				p.grayscale
				FROM {paperattendance_presence} AS p
				INNER JOIN {user} AS u ON (u.id = p.userid)
				WHERE p.sessionid = ?  ';
		
		$attendances = $DB->get_records_sql($getstudentsattendance, array($idattendance), $page * $perpage, $perpage);
		
		$attendancestable = new html_table();
		
		//Check if we have at least one attendance in the selected session
		if ($attendancescount > 0){
			if (is_siteadmin($USER)){
				$attendancestable->head = array(
						get_string('hashtag', 'local_paperattendance'),
						get_string('student', 'local_paperattendance'),
						get_string('mail', 'local_paperattendance'),
						get_string('attendance', 'local_paperattendance'),
						get_string('setting', 'local_paperattendance'),
						get_string('omegasync', 'local_paperattendance'),
						get_string('grayscale', 'local_paperattendance')
				);
			}
			else {
				$attendancestable->head = array(
						get_string('hashtag', 'local_paperattendance'),
						get_string('student', 'local_paperattendance'),
						get_string('mail', 'local_paperattendance'),
						get_string('attendance', 'local_paperattendance'),
						get_string('setting', 'local_paperattendance'),
						get_string('omegasync', 'local_paperattendance')
				);
			}
			//A mere counter for de number of records in the table
			$counter = $page * $perpage + 1;
			foreach ($attendances as $attendance){
				
				//Define synchronized or unsynchronized icon
				$urlomegasync = new moodle_url("#");
				
				if ($attendance->omegasync){
					$synchronizedicon = new pix_icon("i/checkpermissions", get_string('synchronized', 'local_paperattendance'));
				}
				else{
					$synchronizedicon = new pix_icon("i/scheduled", get_string('unsynchronized', 'local_paperattendance'));
				}
				$synchronizediconaction = $OUTPUT->action_icon(
						$urlomegasync,
						$synchronizedicon
						);
				
				//Define presente or ausente icon
				$urlattendance = new moodle_url("#");
				
				if($attendance->status){
					$statusicon = new pix_icon("i/valid", get_string('presentattendance', 'local_paperattendance'));
				}
				else{
					$statusicon = new pix_icon("i/invalid", get_string('absentattendance', 'local_paperattendance'));
				}
				
				$statusiconaction = $OUTPUT->action_icon(
						$urlattendance,
						$statusicon
						);
							
				// Define edition icon and url
				$editurlattendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "edit",
						"idpresence" => $attendance->idp,
						"idattendance" => $idattendance,
						"courseid" => $idcourse
				));
				$editiconattendance = new pix_icon("i/edit", get_string('edithistory', 'local_paperattendance'));
				$editactionasistencia = $OUTPUT->action_icon(
						$editurlattendance,
						$editiconattendance
						);
				
				$name = ($attendance->firstname.' '.$attendance->lastname);
				
				//Now we check if the student is present or not
				if (is_siteadmin($USER)){
					$attendancestable->data[] = array(
							$counter,
							$name,
							$attendance->email,
							$statusiconaction,
							$editactionasistencia,
							$synchronizediconaction,
							$attendance->grayscale
					);
				}
				else {
					$attendancestable->data[] = array(
							$counter,
							$name,
							$attendance->email,
							$statusiconaction,
							$editactionasistencia,
							$synchronizediconaction
					);
				}					
				$counter++;
			}
		}
		
		$viewbackbutton = new moodle_url("/local/paperattendance/history.php", array("action" => "view", "courseid" => $idcourse));	
		$insertstudenturl = new moodle_url("/local/paperattendance/history.php", array(
				"action" => "insertstudent",
				"courseid" => $idcourse,
				"idattendance" => $idattendance
		));
	}	
	// Edits an existent record for the students attendance view
	if($action == "edit"){
		if($idpresence == null){
			print_error(get_string('nonselectedstudent', 'local_paperattendance'));
			$cancelled = new moodle_url("/local/paperattendance/history.php", array(
					"idattendance" => $idattendance,
					"courseid" => $idcourse
					));
			redirect($cancelled);
		}
		else{
			
			if($attendance = $DB->get_record("paperattendance_presence", array("id" => $idpresence)) ){
			
				$editform = new paperattendance_editattendance_form(null, array(
						"idattendance" => $idattendance,
						"courseid" => $idcourse,
						"idpresence" => $idpresence
				));
	
				if($editform->is_cancelled()){
					$cancelled = new moodle_url("/local/paperattendance/history.php", array(
							"action" => "studentsattendance",
							"idattendance" => $idattendance,
							"courseid" => $idcourse
					));
					redirect($cancelled);
	
				}
				else if($data = $editform->get_data()){
	
					$record = new stdClass();
					$record->id = $idpresence;
					$record->lastmodified = time();
					$record->status = $data->status;
						
					$DB->update_record("paperattendance_presence", $record);
					
					if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
					paperattendance_omegaupdateattendance($record->status, $record->omegaid);
					}
					
					$backurl = new moodle_url("/local/paperattendance/history.php", array(
							"action" => "studentsattendance",
							"idattendance" => $idattendance,
							"courseid" => $idcourse
					));
					redirect($backurl);										
				}
			}
			else{
				print_error(get_string('nonexiststudent', 'local_paperattendance'));
				$canceled = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "studentsattendance",
						"idattendance" => $idattendance,
						"courseid" => $idcourse
				));
				redirect($canceled);
			
			}
		}
	}
	
	//Scan view
	if($action == "scan"){
		
		$backurl = new moodle_url("/local/paperattendance/history.php", array(
				"action" => "view",
				"idattendance" => $idattendance,
				"courseid" => $idcourse
				));
		
		$viewbackbutton = html_writer::nonempty_tag(
				"div",
				$OUTPUT->single_button($backurl, get_string('back', 'local_paperattendance')),
				array("align" => "left"
				));
		
		$getpdfname = 'SELECT
					   pdf
				       FROM {paperattendance_session} AS ps
				       WHERE ps.id = ?';
		
		$pdfname = $DB->get_record_sql($getpdfname, array($idattendance));
		
		//var_dump($contextsystem->id);
		//Context id as 1 because the var context->id gets the number 6 , check it later
		$url = moodle_url::make_pluginfile_url($contextsystem->id, 'local_paperattendance', 'draft', 0, '/', $pdfname->pdf);
	
		$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
				"src" => $url,
				"style" => "height:75vh; width:60vw"
		));
	}
	
	// Lists all records in the database
	if ($action == "view"){
		$getattendances = "SELECT s.id, sm.date, CONCAT( m.initialtime, ' - ', m.endtime) AS hour, s.pdf
						   FROM {paperattendance_session} AS s
						   INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid)
						   INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id)
						   WHERE s.courseid = ?
						   ORDER BY sm.date DESC";
		
		$attendances = $DB->get_records_sql($getattendances, array($idcourse));
		
		$attendancestable = new html_table();
		//we check if we have attendances for the selected course
		if (count($attendances) > 0){
			$attendancestable->head = array(
					get_string('hashtag', 'local_paperattendance'),
					get_string('date', 'local_paperattendance'),
					get_string('time', 'local_paperattendance'),
					get_string('percentage', 'local_paperattendance'),
					get_string('scan', 'local_paperattendance'),
					get_string('studentsattendance', 'local_paperattendance'),
					get_string('omegasync', 'local_paperattendance')
			);
			$attendancestable->size = array(
					'10%',
					'25%',
					'25%',
					'10%',
					'10%',
					'10%',
					'10%'
			);
				
			$attendancestable->align = array(
					'left',
					'left',
					'left',
					'center',
					'center',
					'center',
					'center'
			);
				
			//A mere counter for the number of records
			$counter = 1;
			foreach ($attendances as $attendance){
				//Query to get attendance percentage
				$percentagequery = "SELECT TRUNCATE((COUNT(*)/(SELECT COUNT(*)
									FROM {paperattendance_presence} AS p
									INNER JOIN {paperattendance_session} AS s ON (s.id = p.sessionid)
									WHERE p.sessionid = ?)*100),1) AS percentage
									FROM {paperattendance_presence} AS p
									INNER JOIN {paperattendance_session} AS s ON (s.id = p.sessionid)
									WHERE p.sessionid = ? AND p.status = 1";
				$percentage = $DB->get_record_sql($percentagequery, array($attendance->id, $attendance->id));
				// Define scan icon and url
				$scanurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "scan",
						"idattendance" => $attendance->id,
						"courseid" => $idcourse
						
				));
				$scanicon_attendance = new pix_icon("e/new_document", get_string('see', 'local_paperattendance'));
				$scanaction_attendance = $OUTPUT->action_icon(
						$scanurl_attendance,
						$scanicon_attendance
						);
	
				// Define Asistencia alumnos icon and url
				$studentsattendanceurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "studentsattendance",
						"idattendance" => $attendance->id,
						"courseid" => $idcourse
				));
				$studentsattendanceicon_attendance = new pix_icon("e/fullpage", get_string('seestudents', 'local_paperattendance'));
				$studentsattendanceaction_attendance = $OUTPUT->action_icon(
						$studentsattendanceurl_attendance,
						$studentsattendanceicon_attendance
						);
				
				//Convert the unix date to a local date
				$date= $attendance->date;
				$dateconverted = paperattendance_convertdate($date);
				
				//We get the total count of sync students for this session
				$synchronizedstudentnscount = paperattendance_getcountstudentssynchronizedbysession($attendance->id);
				//We get the total count of students for this session
				$studentscount = paperattendance_getcountstudentsbysession($attendance->id);
				//Check if this session is fully synchronized with omega and create de table
				//Define synchronized or unsynchronized url
				$urlomegasync = new moodle_url("#");
				if ( $synchronizedstudentnscount == $studentscount){
					$synchronizedicon = new pix_icon("t/go", get_string('synchronized', 'local_paperattendance'));
				}
				else{
					$synchronizedicon = new pix_icon("i/scheduled", get_string('unsynchronized', 'local_paperattendance'));
				}
				$synchronizediconaction = $OUTPUT->action_icon(
						$urlomegasync,
						$synchronizedicon
						);
					
				$attendancestable->data[] = array(
						$counter,
						$dateconverted,
						$attendance->hour,
						$percentage->percentage,
						$scanaction_attendance,
						$studentsattendanceaction_attendance,
						$synchronizediconaction
				);
				$counter++;
			}
		}
		$buttonurl = new moodle_url("/course/view.php", array("id" => $idcourse));
		
	}	
	
	if($action == "insertstudent"){
		$mform = new paperattendance_addstudent_form(null, array(
				"idcourse" => $idcourse,
				"idattendance" => $idattendance
		));
		if($mform->is_cancelled()){
			$goback = new moodle_url("/local/paperattendance/history.php", array(
					"action" => "studentsattendance",
					"idattendance" => $idattendance,
					"courseid" => $idcourse
			));
			redirect($goback);
		}
		else if($data = $mform->get_data()){
			$insertby = $data->insertby;
			$filter = $data->filter;
			$status = $data->status;
			$user = $DB->get_record("user", array($insertby => $filter));
			$addstudent = new stdClass();
			$addstudent->sessionid = $idattendance;
			$addstudent->userid = $user->id;
			$addstudent->status = $status;
			$addstudent->lastmodified = time();
			$addstudent->omegasync = 0;
			$insertattendance = $DB->insert_record("paperattendance_presence", $addstudent, false);
			$goback = new moodle_url("/local/paperattendance/history.php", array(
					"action" => "studentsattendance",
					"idattendance" => $idattendance,
					"courseid" => $idcourse
			));
			redirect($goback);
		}
	}
	
	$PAGE->set_title(get_string('historytitle', 'local_paperattendance'));
	$PAGE->set_heading(get_string('historyheading', 'local_paperattendance'));
	
	echo $OUTPUT->header();
	
	// Displays Students Attendance view
	if ($action == "studentsattendance"){
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", get_string('nonprocessingattendance', 'local_paperattendance'), array("align" => "left"));
		}
		else{
			//displays button to add a student manually
			echo html_writer::nonempty_tag("div", $OUTPUT->single_button($insertstudenturl, get_string('insertstudentmanually', 'local_paperattendance')), array("align" => "left"));
			//displays the table
			echo html_writer::table($attendancestable);
			//displays de pagination bar
			echo $OUTPUT->paging_bar($attendancescount, $page, $perpage,
				 	$CFG->wwwroot . '/local/paperattendance/history.php?action=' . $action . '&idattendance=' . $idattendance . '&courseid=' . $idcourse . '&page=');
		}	
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($viewbackbutton, get_string('back', 'local_paperattendance')), array("align" => "left"));
		
	}
	
	// Displays the form to edit a record
	if( $action == "edit" ){
		$editform->display();
	}
	
	//Displays the scan file
	if($action == "scan"){
	
		// Donwload and back buttons
		echo $OUTPUT->action_icon($url, new pix_icon('i/grades', get_string('download', 'local_paperattendance')), null, array("target" => "_blank"));
		echo html_writer::nonempty_tag("h7", get_string('downloadassistance', 'local_paperattendance'), array("align" => "left"));
	
		echo $viewbackbutton;
	
		// Preview PDF
		echo $viewerpdf;
	}
	
	// Displays all the records and options
	if ($action == "view"){
	
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", get_string('nonexistintingrecords', 'local_paperattendance'), array("align" => "left"));
		}
		else{
			echo html_writer::table($attendancestable);
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($buttonurl, get_string('backtocourse', 'local_paperattendance')), array("align" => "left"));
	}
	
	//Displays the insert student form
	if($action == "insertstudent"){
		$mform->display();
	}

}	

//Ends Teacher's view


//Begins Student's view
else if ($isstudent) {
	
	//breadcrumb for navigation
	$PAGE->navbar->ignore_active();
	$PAGE->navbar->add(get_string('courses', 'local_paperattendance'), new moodle_url('/course/index.php'));
	$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $idcourse)));
	$PAGE->navbar->add(get_string('pluginname', 'local_paperattendance'));
	$PAGE->navbar->add(get_string('historytitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/history.php", array("courseid" => $idcourse)));
	
	// Lists all records in the database
	if ($action == "view"){
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
		$getstudentattendances = "SELECT s.id AS sessionid, p.id AS presenceid, sm.date, CONCAT( m.initialtime, ' - ', m.endtime) AS hour, p.status, m.name
				FROM {paperattendance_session} AS s
				INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid)
				INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id)
				INNER JOIN {paperattendance_presence} AS p ON (s.id = p.sessionid)
				INNER JOIN {user} AS u ON (u.id = p.userid)
				WHERE s.courseid = ? AND u.id = ?
				ORDER BY sm.date DESC";
	
		$attendances = $DB->get_records_sql($getstudentattendances, array($idcourse, $USER->id));
	
		$attendancestable = new html_table();
	
		if (count($attendances) > 0){
			$attendancestable->head = array(
					get_string('hashtag', 'local_paperattendance'),
					get_string('date', 'local_paperattendance'),
					get_string('module', 'local_paperattendance'),
					get_string('time', 'local_paperattendance'),
					get_string('attendance', 'local_paperattendance'),
					get_string('reviewattendance', 'local_paperattendance')
			);
			//A mere counter for the numbers of records
			$counter = 1;
			foreach ($attendances as $attendance){
				
				$urlattendance = new moodle_url("#");
				
				if ($attendance->status){
					$statusicon = new pix_icon("i/valid", get_string('presentattendance', 'local_paperattendance'));
				}
				else{
					$statusicon = new pix_icon("i/invalid", get_string('absentattendance', 'local_paperattendance'));
				}
				$statusiconaction = $OUTPUT->action_icon(
						$urlattendance,
						$statusicon
						);
				$formbuttonurl = new moodle_url("/local/paperattendance/history.php", array("action"=>"requestattendance","idpresence" => $attendance->presenceid,"courseid" => $idcourse));
				
				$discussionquery = "SELECT d.result
									FROM {paperattendance_discussion} d
									WHERE d.presenceid = ?";
				$discussion = $DB->get_record_sql($discussionquery, array($attendance->presenceid));
				
				$attendancestable->data[] = array(
					$counter,
					date("d-m-Y", $attendance->date),
					$attendance->name,
					$attendance->hour,
					$statusiconaction,
					//staus = 0 -> Attendance has never been requested before
					//result = 0 -> scheduled icon (Attendance request wasn't solved yet)
					//result = 1 -> invalid icon (Attendance request wasn't accepted)
					//result = 2 -> valid icon (Attendance request was accepted)
					(!$attendance->status ? html_writer::nonempty_tag("div", $OUTPUT->single_button($formbuttonurl, get_string('request', 'local_paperattendance'))) 
					: ($discussion->result == 0) ? $synchronizediconaction 
					: (($discussion->result == 1) ? $invalidiconaction 
					: $validiconaction))
				);
						
				$counter++;
			}
		}
	
		$backbuttonurl = new moodle_url("/course/view.php", array("id" => $idcourse));
	
	}
	if ($action == "requestattendance"){
		$requestform = new paperattendance_reviewattendance_form(null, array(
				"courseid" => $idcourse,
				"presenceid" => $idpresence
		));
		$goback = new moodle_url("/local/paperattendance/history.php", array("action"=>"view","courseid" => $idcourse));
			
	
		if($requestform->is_cancelled()){
			redirect($goback);
		}
		else if($data = $requestform->get_data()){
			$newdiscussion = new stdClass();
			$newdiscussion->presenceid = $idpresence;
			$newdiscussion->comment = $data->comment;
			$newdiscussion->result = 0; //Result equals to 0 means that the discussion is open
			$insertdiscussion = $DB->insert_record("paperattendance_discussion", $newdiscussion, false);
			redirect($goback);
		}
	}
	
	$PAGE->set_title(get_string('historytitle', 'local_paperattendance'));
	$PAGE->set_heading(get_string('historyheading', 'local_paperattendance'));
	echo $OUTPUT->header();
	
	// Displays all the records and options
	if ($action == "view"){
	
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", get_string('nonexistintingrecords', 'local_paperattendance'), array("align" => "left"));
		}
		else{
			
			//student summary sql
			$sessions = $DB->count_records("paperattendance_session", array ("courseid" => $course->id));
			$present = "SELECT COUNT(*) 
						FROM {paperattendance_presence} AS p
						INNER JOIN {paperattendance_session} AS s ON (s.id = p.sessionid)
						WHERE s.courseid = ? AND p.status = 1";
			$present = $DB->count_records_sql($present, array($course->id));
			$absent = $sessions - $present;
			$percentagestudent = round(($present/$sessions)*100); 
			
			//summary
			echo html_writer::nonempty_tag("h4", $USER->firstname." ".$USER->lastname, array("align" => "left"));
			$resume = html_writer::nonempty_tag("div", get_string('totalattendances', 'local_paperattendance').": ".$sessions, array("align" => "right"));
			$resume .= html_writer::nonempty_tag("div", get_string('presentattendance', 'local_paperattendance').": ".$present, array("align" => "right"));
			$resume .= html_writer::nonempty_tag("div", get_string('absentattendance', 'local_paperattendance').": ".$absent, array("align" => "right"));
			$resume .= html_writer::nonempty_tag("div", get_string('percentagestudent', 'local_paperattendance')."*: ".$percentagestudent."%", array("align" => "right", "title" => get_string('tooltippercentage', 'local_paperattendance'), "data-placement"=>"left","data-toggle" =>"tooltip"));
			echo html_writer::nonempty_tag("div", $resume, array("style" => "width:20%"));
			
			//table
			echo html_writer::nonempty_tag("div", html_writer::table($attendancestable), array( "style" => " margin-top: 15px;"));
			
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($backbuttonurl, get_string('backtocourse', 'local_paperattendance')), array("align" => "left"));
	}
	if ($action == "requestattendance"){
		$requestform->display();
	}
	
}
//Ends Student's view
else{
	print_error(get_string('error', 'local_paperattendance'));
}

echo $OUTPUT->footer();