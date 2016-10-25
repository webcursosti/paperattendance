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


namespace local_paperattendance\task;

class processpdf extends \core\task\scheduled_task {
	
	public function get_name() {
		return get_string('task', 'local_paperattendance');
	}

	public function execute() {
		
		require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php');
		require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
		
		global $DB;
		
			// Sql that brings the unread pdfs names
			$sqlunreadpdfs = "SELECT  id, pdf AS name, courseid
			FROM {paperattendance_session}
			WHERE status = ?
			ORDER BY lastmodified ASC";
		
			// Parameters for the previous query
			$params = array(PAPERATTENDANCE_STATUS_UNREAD);
		
			// Read the pdfs if there is any unread, with readpdf function
			if($resources = $DB->get_records_sql($sqlunreadpdfs, $params)){
				$path = $CFG -> dataroot. "/temp/local/paperattendance/unread";
				foreach($resources as $pdf){
					$process = paperattendance_readpdf($path, $pdf-> name, $pdf->courseid);
					if($process){
						$pdf->status = 1;
						$DB->update_record("paperattendance_session", $pdf);
					}
				}
			}
	}
}