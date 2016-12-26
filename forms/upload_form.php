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
* @copyright  2016 Hans Jeria (hansjeria@gmail.com) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . "/formslib.php");

class paperattendance_upload_form extends moodleform {

	public function definition() {
		global $CFG, $DB;
		
		$mform = $this->_form;

		//retrieve course id
		$instance = $this ->_customdata;
		$courseid = $instance['courseid'];
		$categoryid = $instance['categoryid'];
		
		//max file size 8388608 default (in bytes)
		$maxbytes = $CFG->paperattendance_maxfilesize;
		
		//header
		$mform->addElement('header', 'header', get_string('header', 'local_paperattendance'));
		//filepicker
		$mform->addElement('filepicker', 'file', get_string('uploadfilepicker', 'local_paperattendance'), null, array('maxbytes' => $maxbytes, 'accepted_types' =>array('*.pdf')));	
		$mform->setType('file', PARAM_FILE);
		$mform->addRule('file', get_string('uploadrule', 'local_paperattendance'), 'required', null, 'client');
		
		//Courseid and categoryid
		$mform->addElement('hidden', 'courseid', $courseid);
		$mform->setType('courseid', PARAM_INT);
		$mform->addElement('hidden', 'categoryid', $categoryid);
		$mform->setType('categoryid', PARAM_INT);
		$this->add_action_buttons(true);
	}
	public function validation($data, $files) {

		$errors = array();
		$realfilename = $data ['file'];
		// checking this to see if any file has been uploaded
	    if($realfilename ==''){
           $errors ['upload'] = get_string('uploadplease', 'local_paperattendance');
        }
		return $errors;
	}
}