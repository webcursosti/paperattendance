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
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/forms/upload_form.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
global $DB, $OUTPUT,$COURSE, $USER;

// User must be logged in.
require_login();
if (isguestuser()) {
    //die();
}
$courseid = required_param('courseid', PARAM_INT);
// We are in the course context.
$context = context_system::instance();
// And have viewcostreport capability.
if (! has_capability('local/paperattendance:upload', $context)) {
    // TODO: Log invalid access to upload attendance.
    print_error(get_string('notallowedupload', 'local_paperattendance'));
   //	 die();
}
// This page url.
$url = new moodle_url('/local/paperattendance/upload.php', array(
    'courseid' => $courseid));

$pagetitle = get_string('uploadtitle', 'local_paperattendance');
$course = $DB ->get_record("course", array("id" =>$courseid));

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_site()->fullname);
$PAGE->set_title($pagetitle . " " . $course -> fullname);

// Add the upload form for the course.
$addform = new upload_form (null, array("courseid" => $courseid));
// If the form is cancelled, refresh the instante.
if ($addform->is_cancelled()) {
    redirect($url);
    die();
} 
if ($addform->get_data()) {
	require_capability('local/paperattendance:upload', $context);
	
	$data = $addform -> get_data();
	$teacherid = $data -> teacher;
	
	$path = $CFG -> dataroot. "/temp/local/paperattandace/";
	if (!file_exists($path . "/unread/")) {
			mkdir($path . "/unread/", 0777, true);
		}	
	// Save file
	$filename = $addform->get_new_filename('file');
	$file = $addform->save_file('file', $path."/unread/".$filename, false);
	// Validate that file was correctly uploaded.
	
	$transaction = $DB->start_delegated_transaction();
	// Insert the record that associates a digitized file with a set of answers.
	$pdfinsert = new stdClass();
	$pdfinsert->id = "NULL";
	$pdfinsert->courseid = $courseid;
	$pdfinsert->teacherid = $teacherid;
	$pdfinsert->uploaderid = $USER-> id;
	$pdfinsert->pdf = $filename;
	$pdfinsert->status = 0;
	$pdfinsert->lastmodified = time();
	$pdfinsert->id = $DB->insert_record('paperattendance_session', $pdfinsert);
	
	if (!$file) {
		print_error('Could not upload file');
		$e = new exception('Failed to create file in moodle filesystem');
		$DB->rollback_delegated_transaction($transaction, $e);
	}
	else{
	// Display confirmation page before moving out.
	$DB->commit_delegated_transaction($transaction);
	redirect($url, get_string('uploadsuccessful', 'local_paperattendance'), 3);
	//die();
	}
}
// If there is no data or is it not cancelled show the header, the tabs and the form.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle. " " . $course->shortname . " " . $course->fullname);
// Display the form.
$addform->display();
echo $OUTPUT->footer();