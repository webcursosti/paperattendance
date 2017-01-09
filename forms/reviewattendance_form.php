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

class paperattendance_reviewattendance_form extends moodleform {
	public function definition(){
		$mform = $this->_form;
		$instance = $this->_customdata;
		$idcourse = $instance["courseid"];
		$idpresence = $instance["presenceid"];

		$mform->addElement("text", "comment", get_string("comment", "local_paperattendance"));
		$mform->setType("comment", PARAM_TEXT);
		$mform->addElement("hidden", "action", "requestattendance");
		$mform->setType("action", PARAM_TEXT);
		$mform->addElement("hidden", "courseid", $idcourse);
		$mform->setType("courseid", PARAM_INT);
		$mform->addElement("hidden", "idpresence", $idpresence);
		$mform->setType("idpresence", PARAM_INT);
		$this->add_action_buttons(true);
	}
	public function validation($data, $files){
		global $DB;
		$errors = array();
		$comment = $data["comment"];
		if(!isset($comment) || empty($comment) || $comment == "" || $comment == null){
			$errors["comment"] = get_string("required", "local_paperattendance");
		}
		return $errors;
	}
}