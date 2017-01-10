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
//Pertenece al plugin PaperAttendance
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");

class paperattendance_response_form extends moodleform {
	public function definition(){
		$mform = $this->_form;
		$instance = $this->_customdata;
		$courseid = $instance["courseid"];
		$discussionid = $instance["discussionid"];
		$result = [get_string("pleaseselectattendance","local_paperattendance"), get_string("mantainabsent","local_paperattendance"), get_string("changetopresent","local_paperattendance")];

		$mform->addElement("text", "response", get_string("response", "local_paperattendance"));
		$mform->setType("response", PARAM_TEXT);
		$mform->addElement("select", "result", get_string("result", "local_paperattendance"), $result);
		$mform->addElement("hidden", "action", "response");
		$mform->setType("action", PARAM_TEXT);
		$mform->addElement("hidden", "courseid", $courseid);
		$mform->setType("courseid", PARAM_INT);
		$mform->addElement("hidden", "discussionid", $discussionid);
		$mform->setType("discussionid", PARAM_INT);
		$this->add_action_buttons(true);
	}
	public function validation($data, $files){
		global $DB;
		$errors = array();
		$result = $data["result"];
		if($result == 0){
			$errors["result"] = get_string("required", "local_paperattendance");
		}
		return $errors;
	}
}