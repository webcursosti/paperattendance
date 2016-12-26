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
* @copyright  2016 Matías Queirolo (mqueirolo@alumnos.uai.cl)  					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace local_paperattendance\task;

class deletepdf extends \core\task\scheduled_task {
	
	public function get_name() {
		return get_string('task', 'local_paperattendance');
	}

	public function execute() {
		global $CFG, $DB;
		require_once ($CFG->dirroot . '/local/paperattendance/locallib.php');
		$path = $CFG -> dataroot. "/temp/local/paperattendance/print/";
		
		//call de function to delete the files from the print folder in moodledata
		if (file_exists($path)) {
			paperattendance_recursiveRemoveDirectory($path);
		
		}

	}
}	

