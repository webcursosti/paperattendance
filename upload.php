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
global $DB, $OUTPUT,$COURSE;

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
    'course' => $courseid));

$pagetitle = get_string('uploadtitle', 'local_paperattendance');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('incourse');
$PAGE->set_heading(get_site()->fullname);
$PAGE->set_title($pagetitle);

// Add the upload form for the course.
$addform = new upload_form ();
// If the form is cancelled, refresh the instante.
if ($addform->is_cancelled()) {
    redirect($url);
    die();
} 
else if ($data = $addform->get_data()) {
	// If not cancelled
	$content = $data->get_file_content('file');
	$name = $data->get_new_filename('file');
	$file = $data->save_stored_file('file', $coursecontext->id, 'paperattendance', 'tmpupload', $courseid, '/', $name);
	// Validate that file was correctly uploaded.
	if (!$file) {
		print_error('Could not upload file');
	}

}
// If there is no data or is it not cancelled show the header, the tabs and the form.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
// Display the form.
$addform->display();
echo $OUTPUT->footer();