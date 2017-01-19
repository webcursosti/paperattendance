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

namespace local_paperattendance\task;

class paperattendance_processpdf extends \core\task\scheduled_task {
	
	public function get_name() {
		return get_string('taskpresence', 'local_paperattendance');
	}

	public function execute() {
		global $CFG, $DB;
		require_once ($CFG->dirroot . '/local/paperattendance/locallib.php');
		
		//select the last sessionid from cronlog
		$sqllastverified = "SELECT MAX(id) AS id,
							result
							FROM {paperattendance_cronlog}
							WHERE task = ?";
		if($resultverified = $DB->get_record_sql($sqllastverified, array("presence"))){
			//if this task has already run at least once
			$lastsessionid = $resultverified->result;
		}
		else{
			//just check all sessions
			$lastsessionid = 0;
		}
		
		$sqlsessions = "SELECT id, courseid FROM {paperattendance_session} WHERE id > ?";
		
		if($sessionstoverify = $DB->get_records_sql($sqlsessions, array($lastsessionid))){
			//if there is at least one session, check if there is a student enrolled but not on the list
			foreach ($sessionstoverify as $session){
				$sessionid = $session->id;
				$courseid = $session->courseid;
				
				$enrolincludes = explode("," ,$CFG->paperattendance_enrolmethod);
				list($enrolmethod, $paramenrol) = $DB->get_in_or_equal($enrolincludes);
				$parameters = array_merge(array($courseid), $paramenrol, array($sessionid));
				
				$querystudentsnotinlist = "SELECT u.id
				FROM {user_enrolments} ue
				INNER JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = ?)
				INNER JOIN {context} c ON (c.contextlevel = 50 AND c.instanceid = e.courseid)
				INNER JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.roleid = 5 AND ra.userid = ue.userid)
				INNER JOIN {user} u ON (ue.userid = u.id)
				WHERE e.enrol $enrolmethod AND u.id NOT IN (SELECT userid FROM  {paperattendance_presence} WHERE sessionid = ?)
				GROUP BY u.id
				ORDER BY lastname ASC";
				
				if($studentsnotinlist = $DB->get_records_sql($querystudentsnotinlist, $parameters)){
					foreach ($studentsnotinlist as $student){
						paperattendance_save_student_presence($sessionid, $student->id, '0');
					}
				}
				paperattendance_cronlog("presence", $session->id, time());
			}
		}
	}
}