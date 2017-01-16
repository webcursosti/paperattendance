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
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Pertenece al plugin PaperAttendance
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");

class paperattendance_export_form extends moodleform {
	public function definition(){
		$mform = $this->_form;
		$instance = $this->_customdata;
		$courseid = $instance["courseid"];
		$types = paperattendance_returnattendancedescription(true);
		$typesarray = array();
		$default = array();
		foreach($types as $type){
			$typesarray[] =& $mform->createElement("checkbox",$type["name"],"", $type["string"]);
			$default["sesstype[".$type["name"]."]"] = true;
		}
		$mform->addGroup($typesarray, 'sesstype', get_string('sesstype', 'local_paperattendance'), array('<br />'), true);
		$mform->setDefaults($default);
		$mform->addElement("checkbox", "alldates", get_string("allsessions", "local_paperattendance"), get_string("yes","local_paperattendance"));
		$mform->setDefault("alldates", true);
		$mform->addElement('date_selector', 'initdate', get_string("initdate","local_paperattendance"));
		$mform->disabledIf('initdate', 'alldates', 'checked');
		$mform->addElement('date_selector', 'enddate', get_string("enddate","local_paperattendance"));
		$mform->disabledIf('enddate', 'alldates', 'checked');
		$mform->addElement("hidden", "courseid", $courseid);
		$mform->setType("courseid", PARAM_INT);
		$this->add_action_buttons(true);
	}
	public function validation($data, $files){
		global $DB;
		$errors = array();
		if(!isset($data["sesstype"])){
			$errors["sesstype"] = get_string("selectsesstype","local_paperattendance");
		}
		return $errors;
	}
}