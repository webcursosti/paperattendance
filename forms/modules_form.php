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
defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");

class paperattendance_editmodule_form extends moodleform {
	
	public function definition() {
	global $CFG, $DB;
	
	$mform = $this->_form;
	
	$instance = $this->_customdata;
	$idmodule = $instance ["idmodule"];
	$mform->addElement("text", "name", get_string("modulename", "local_paperattendance"));
	$mform->setType("name", PARAM_TEXT);
	$mform->addRule("name", get_string("required", "local_paperattendance"), "required", null, "client");
	$mform->addElement("text", "initialtime", get_string("initialtime", "local_paperattendance"));
	$mform->setType("initialtime", PARAM_TEXT);
	$mform->addRule("initialtime", get_string("required", "local_paperattendance"), "required", null, "client");
	$mform->addElement("text", "endtime", get_string("endtime", "local_paperattendance"));
	$mform->setType("endtime", PARAM_TEXT);
	$mform->addRule("endtime", get_string("required", "local_paperattendance"), "required", null, "client");
	$mform->addElement("hidden", "action", "edit");
	$mform->setType("action", PARAM_TEXT);
	$mform->addElement("hidden", "idmodule", $idmodule);
	$mform->setType("idmodule", PARAM_INT);
	
	$this->add_action_buttons(true);
	}
	
	public function validation($data, $files) {
		global $DB;
		
		$errors = array();
		$name = $data ["name"];
		$initialtime = $data ["initialtime"];
		$endtime = $data ["endtime"];
		if (isset($data ["name"]) && ! empty($data ["name"]) && $data ["name"] != "" && $data ["name"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " name = ?", array($name))) {
				$errors ["name"] = get_string("nameexist", "local_paperattendance");
			}
		}
		else {
			$errors ["name"] = get_string("required", "local_paperattendance");
		}
		if (isset($data ["initialtime"]) && ! empty($data ["initialtime"]) && $data ["initialtime"] != "" && $data ["initialtime"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " initialtime = ?", array($initialtime))) {
				$errors ["initialtime"] = get_string("initialtimeexist", "local_paperattendance");
			}

		}
		else {
			$errors ["initialtime"] = get_string("required", "local_paperattendance");
		}
		if (isset($data ["endtime"]) && ! empty($data ["endtime"]) && $data ["endtime"] != "" && $data ["endtime"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " endtime = ?", array($endtime))) {
				$errors ["endtime"] = get_string("endtimeexist", "local_paperattendance");
			}
		}
		else {
			$errors ["endtime"] = get_string("required", "local_paperattendance");
		}
		
		return $errors;
	}
}

class paperattendance_addmodule_form extends moodleform {
	
	public function definition() {
		global $DB;
		
		$mform = $this->_form;
		
		$mform->addElement("text", "name", get_string("modulename", "local_paperattendance"));
		$mform->setType("name", PARAM_TEXT);
		$mform->addRule("name", get_string("required", "local_paperattendance"), "required", null, "client");
		$mform->addElement("text", "initialtime", get_string("initialtime", "local_paperattendance"));
		$mform->setType("initialtime", PARAM_TEXT);
		$mform->addRule("initialtime", get_string("required", "local_paperattendance"), "required", null, "client");
		$mform->addElement("text", "endtime", get_string("endtime", "local_paperattendance"));
		$mform->setType("endtime", PARAM_TEXT);
		$mform->addRule("endtime", get_string("required", "local_paperattendance"), "required", null, "client");
		$mform->addElement("hidden", "action", "add");
		$mform->setType("action", PARAM_TEXT);
		
		$this->add_action_buttons(true);
	}
	
	public function validation($data, $files) {
		global $DB;
		
		$errors = array();
		$name = $data ["name"];
		$initialtime = $data ["initialtime"];
		$endtime = $data ["endtime"];
		if (isset($data ["name"]) && ! empty($data ["name"]) && $data ["name"] != "" && $data ["name"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " name = ?", array($name))) {
				$errors ["name"] = get_string("nameexist", "local_paperattendance");
			}
		}
		else {
			$errors ["name"] = get_string("required", "local_paperattendance");
		}
		if (isset($data ["initialtime"]) && ! empty($data ["initialtime"]) && $data ["initialtime"] != "" && $data ["initialtime"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " initialtime = ?", array($initialtime))) {
				$errors ["initialtime"] = get_string("initialtimeexist", "local_paperattendance");
			}
		} 
		else {
			$errors ["initialtime"] = get_string("required", "local_paperattendance");
		}
		if (isset($data ["endtime"]) && ! empty($data ["endtime"]) && $data ["endtime"] != "" && $data ["endtime"] != null) {
			if (! $DB->get_recordset_select("paperattendance_module", " endtime = ?", array($endtime))) {
				$errors ["endtime"] = get_string("endtimeexist", "local_paperattendance");
			}
		} 
		else {
			$errors ["endtime"] = get_string("required", "local_paperattendance");
		}
		
		return $errors;
	}
	
}