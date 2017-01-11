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
* @copyright  2016 Hans Jeria <hansjeria@gmail.com>
* @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_paperattendance\task;

class omegasync extends \core\task\scheduled_task {
	
	public function get_name() {
		return get_string('task', 'local_paperattendance');
	}

	public function execute() {	
		global $CFG, $DB;
		if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
			require_once ($CFG->dirroot . '/local/paperattendance/locallib.php');
			
			// Sql that brings the unsynced sessions (with omega)
			$sqlunsynced = "SELECT sess.id AS id, sess.courseid AS courseid
	 				FROM {paperattendance_session} AS sess
					WHERE sess.status = ?
					ORDER BY sess.lastmodified ASC";
			// Parameters for the previous query
			$params = array(PAPERATTENDANCE_STATUS_PROCESSED);
		
			// syn students with synctask function
			if($resources = $DB->get_records_sql($sqlunsynced, $params)){
				$path = $CFG -> dataroot. "/temp/local/paperattendance/unread";
				foreach($resources as $session){
					if($process = paperattendance_synctask($session->courseid, $session->id)){
						$session->status = 2;
						$DB->update_record("paperattendance_session", $session);
					}
				}
			}
		}
	}
}