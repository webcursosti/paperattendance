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
* @copyright  2016 Hans Jeria (hansjeria@gmail.com) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");

class print_form extends moodleform {

	public function definition() {
		global $DB;
		
		$mform = $this->_form;
		$instance = $this->_customdata;		
		$courseid = $instance["courseid"];
		
		$sqlteachers = "SELECT u.id, CONCAT (u.firstname, ' ', u.lastname)AS name
					FROM {user} u
					INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
					INNER JOIN {context} ct ON (ct.id = ra.contextid)
					INNER JOIN {course} c ON (c.id = ct.instanceid AND c.id = ?)
					INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname IN ('teacher', 'editingteacher'))";
		$teachers = $DB->get_records_sql($sqlteachers, array($courseid));
		
		$arrayteachers = array();
		$arrayteachers["no"] = "Seleccione el profesor";
		foreach ($teachers as $teacher){
			$arrayteachers[$teacher->id] = $teacher->name;
		}
		$mform->addElement("select", "teacher", "Profesor", $arrayteachers);
		$mform->addElement("date_selector", "sessiondate", "Fecha de Asistencia");
		
		$modules = $DB->get_records("paperattendance_module");
		$arraymodules = array();
		foreach ($modules as $module){
			$arraymodules[] = $mform->createElement('advcheckbox', $module->id , '',$module->initialtime);	
		}
		$mform->addGroup($arraymodules, 'modules', "Modulos");
		$mform->addElement("hidden", "courseid", $courseid);
		$mform->setType( "courseid", PARAM_INT);
		
		$this->add_action_buttons(true);
		
	}
	
	public function validation($data, $files) {
		
		$errors = array();
		
		$teacher = $data["teacher"];
		$sessiondate = $data["sessiondate"];
		$modules = $data["modules"];
		
		if($teacher == "no"){
			$errors["teacher"] = "Debe seleccionar un profesor.";
		}
		
		$actualtime = strtotime(date("d-m-Y"));
		//echo strtotime(date("d-m-Y"))." select".$sessiondate;
		if($sessiondate < $actualtime){
			$errors["sessiondate"] = "Debe seleccionar una fecha valida.";
		}
		
		$count = 0;
		foreach ($modules as $module){
			if($module == 1){
				$count++;
			}
		}
		if($count == 0){
			$errors["modules"] = "Debe seleccionar al menos un modulo.";
		}

		return $errors;
	}
}