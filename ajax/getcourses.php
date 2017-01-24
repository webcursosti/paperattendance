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

//define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $DB;

$data = required_param('result', PARAM_TEXT);
$path = required_param('path', PARAM_INT);

require_login();

$context = context_system::instance();
$contextsystem = context_system::instance();

if (! has_capability('local/paperattendance:printsearch', $context) && ! has_capability('local/paperattendance:printsearch', $contextsystem)) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
}

$filter = array("%/".$path."%", $data."%", $data."%", $data."%");
$sqlcourses = "SELECT c.id, 
			c.fullname, 
			cat.name, 
			CONCAT( u.firstname, ' ', u.lastname) as teacher
			FROM {user} AS u
			INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
			INNER JOIN {context} ct ON (ct.id = ra.contextid)
			INNER JOIN {course} c ON (c.id = ct.instanceid)
			INNER JOIN {role} r ON (r.id = ra.roleid AND r.id IN ( 3, 4))
			INNER JOIN {course_categories} as cat ON (cat.id = c.category)
			WHERE (cat.path like ?) AND (u.firstname like ? OR u.lastname like ? OR c.fullname like ?)
			GROUP BY c.id";
$courses = $DB->get_records_sql($sqlcourses, $filter);

echo json_encode($courses);
