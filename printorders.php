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
* @copyright  2016 Matías Queirolo (mqueirolo@alumnos.uai.cl)
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');

global $DB, $OUTPUT, $USER;
// User must be logged in.
require_login();
if (isguestuser()) {
	print_error(get_string('notallowedupload', 'local_paperattendance'));
	die();
}
$courseid = optional_param('courseid',1, PARAM_INT);
$category = optional_param('categoryid', 1, PARAM_INT);
$action = optional_param('action', 'viewform', PARAM_TEXT);

if($courseid > 1){
	if($course = $DB->get_record("course", array("id" => $courseid))){
		$context = context_coursecat::instance($course->category);
	}
}else if($category > 1){
	$context = context_coursecat::instance($category);
}else{
	$context = context_system::instance();
}

$contextsystem = context_system::instance();

if (! has_capability('local/paperattendance:printorders', $context) && ! has_capability('local/paperattendance:printorders', $contextsystem)) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
}
// This page url.
$url = new moodle_url('/local/paperattendance/printorders.php', array(
		'courseid' => $courseid,
		"categoryid" => $category
));
if($courseid && $courseid != 1){
	$courseurl = new moodle_url('/course/view.php', array(
			'id' => $courseid,
			"categoryid" => $category
	));
	$PAGE->navbar->add($course->fullname, $courseurl );
}
$PAGE->navbar->add(get_string('uploadtitle', 'local_paperattendance'));
$PAGE->navbar->add(get_string('header', 'local_paperattendance'),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

echo $OUTPUT->header();

echo $OUTPUT->footer();
