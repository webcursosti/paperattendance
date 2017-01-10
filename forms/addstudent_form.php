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

class paperattendance_addstudent_form extends moodleform {
	public function definition(){
		$mform = $this->_form;
		$instance = $this->_customdata;
		$courseid = $instance["courseid"];
		$attendanceid = $instance["attendanceid"];

		$insertby = ["email"=> get_string("mail", "local_paperattendance"), "idnumber"=> get_string("idnumber", "local_paperattendance")];
		$status = [get_string("absentattendance", "local_paperattendance"), get_string("presentattendance", "local_paperattendance")];
		$mform->addElement("select", "insertby", get_string("insertby", "local_paperattendance"), $insertby);
		$mform->addElement("text", "filter", get_string("insertstudentinfo", "local_paperattendance"));
		$mform->setType("filter", PARAM_TEXT);
		$mform->addHelpButton('filter', 'filter', 'local_paperattendance');
		$mform->addElement("select", "status", get_string("studentstatus", "local_paperattendance"), $status);
		$mform->addElement("hidden", "action", "insertstudent");
		$mform->setType("action", PARAM_TEXT);
		$mform->addElement("hidden", "courseid", $courseid);
		$mform->setType("courseid", PARAM_INT);
		$mform->addElement("hidden", "attendanceid", $attendanceid);
		$mform->setType("attendanceid", PARAM_INT);
		$this->add_action_buttons(true);
	}
	public function validation($data, $files){
		global $DB;
		$errors = array();
		$insertby = $data["insertby"];
		$filter = $data["filter"];
		$sessionid = $data["attendanceid"];
		if($insertby == "idnumber"){
			if(!isset($filter) || empty($filter) || $filter == "" || $filter == null){
				$errors["filter"] = get_string("iderror", "local_paperattendance");
			}
		}
		else{
			if(!isset($filter) || empty($filter) || $filter == "" || $filter == null){
				$errors["filter"] = get_string("mailerror", "local_paperattendance");
			}
		}
		if(empty($errors["filter"])){
			$user = $DB->get_record("user", array($insertby => $filter));
			if(!$user){
					$errors["filter"] = get_string("nonexiststudent", "local_paperattendance");
			}
			else{
				if($DB->record_exists("paperattendance_presence", array("userid"=>$user->id, "sessionid"=>$sessionid))){
					$errors["filter"] = get_string("alreadyinsertedstudent", "local_paperattendance");
				}
				else{
					$email = explode("@", $user->email);
					if($email[1] != "alumnos.uai.cl"){
						$errors["filter"] = get_string("nonexiststudent", "local_paperattendance");
					}
				}
			}
		}
		return $errors;
	}
}