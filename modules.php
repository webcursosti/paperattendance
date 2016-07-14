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
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
<<<<<<< HEAD
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
require_once($CFG->dirroot . '/local/paperattendance/forms/modules_form.php');
global $CFG, $DB, $OUTPUT,$COURSE, $USER, $PAGE;
=======
require_once($CFG->dirroot . '/local/paperattendance/forms/modules_form.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
global $DB, $OUTPUT,$COURSE, $USER, $PAGE;
>>>>>>> refs/remotes/webcursosuai/master

// User must be logged in.
require_login();
if (isguestuser()) {
    //die();
}
$context = context_system::instance();
<<<<<<< HEAD
$courseid = optional_param('courseid',null, PARAM_INT);
=======
>>>>>>> refs/remotes/webcursosuai/master

if (! has_capability('local/paperattendance:modules', $context)) {
    // TODO: Log invalid access to modify modules.
    print_error(get_string('notallowedmodules', 'local_paperattendance'));
   //	 die();
}

<<<<<<< HEAD
$url = new moodle_url('/local/paperattendance/modules.php');

if($courseid){
	$courseurl = new moodle_url('/course/view.php', array(
			'id' => $courseid));
	$course = $DB ->get_record("course", array("id" =>$courseid));
	$PAGE->navbar->add($course->fullname, $courseurl );
}

$PAGE->navbar->add(get_string('uploadtitle', 'local_paperattendance'));
$PAGE->navbar->add(get_string('modulestitle', 'local_paperattendance'),$url);
=======
$url = new moodle_url('/local/paperattendance/modules.php', array(
    'courseid' => $courseid));

$PAGE->navbar->add(get_string('uploadtitle', 'local_paperattendance'),$url);
>>>>>>> refs/remotes/webcursosuai/master
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

// Action = { view, edit, delete, create }, all page options.
$action = optional_param("action", "view", PARAM_TEXT);
$idmodule = optional_param("idmodule", null, PARAM_INT);
$sesskey = optional_param("sesskey", null, PARAM_ALPHANUM);

if ($action == "view") {
    $modules = $DB->get_records("paperattendance_module");
    $modulestable = new html_table();
    if (count($modules) > 0) {
        $modulestable->head = array(
            get_string("modulename", "local_paperattendance"),
            get_string("initialtime", "local_paperattendance"),
            get_string("endtime", "local_paperattendance"));
        foreach ($modules as $module) {
            $deleteurlmodule = new moodle_url("/local/paperattendance/modules.php",
                    array(
                        "action" => "delete",
                        "idmodule" => $module->id,
                        "sesskey" => sesskey()));
            $deleteiconmodule = new pix_icon("t/delete", get_string("delete", "local_paperattendance"));
            $deleteactionmodule = $OUTPUT->action_icon($deleteurlmodule, $deleteiconmodule,
                    new confirm_action(get_string("doyouwantdeletemodule", "local_paperattendance")));
            $editurlmodule = new moodle_url("/local/paperattendance/modules.php",
                    array(
                        "action" => "edit",
                        "idmodule" => $module->id,
                        "sesskey" => sesskey()));
            $editiconmodule = new pix_icon("i/edit", get_string("edit", "local_paperattendance"));
            $editactionmodule = $OUTPUT->action_icon($editurlmodule, $editiconmodule,
                    new confirm_action(get_string("doyouwanteditmodule", "local_paperattendance")));
            $modulestable->data [] = array(
                $module->name,
                $module->initialtime,
            	$module->endtime,
                $deleteactionmodule . $editactionmodule);
        }
    }
    $buttonurl = new moodle_url("/local/paperattendance/modules.php", array(
        "action" => "add"));
<<<<<<< HEAD
    
    $PAGE->set_title(get_string("viewmodules", "local_paperattendance"));
    $PAGE->set_heading(get_string("viewmodules", "local_paperattendance"));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("modulestitle", "local_paperattendance"));
    if (count($modules) == 0) {
    	echo html_writer::nonempty_tag("h4", get_string("nomodules", "local_paperattendance"), array(
    			"align" => "center"));
    } else {
    	echo html_writer::table($modulestable);
    }
    echo html_writer::nonempty_tag("div", $OUTPUT->single_button($buttonurl, get_string("addmoduletitle", "local_paperattendance")),
    		array(
    				"align" => "center"));
    
=======
>>>>>>> refs/remotes/webcursosuai/master
}
if ($action == "add") {
	$addform = new paperattendance_addmodule_form();
	if ($addform->is_cancelled()) {
		$action = "view";
<<<<<<< HEAD
		
		$url = new moodle_url('/local/paperattendance/modules.php');
		redirect($url);
=======
>>>>>>> refs/remotes/webcursosuai/master
	} else if ($creationdata = $addform->get_data()) {
		$record = new stdClass();
		$record->name = $creationdata->name;
		$record->initialtime = $creationdata->initialtime;
		$record->endtime = $creationdata->endtime;
		$DB->insert_record("paperattendance_module", $record);
		$action = "view";
<<<<<<< HEAD
		
		$url = new moodle_url('/local/paperattendance/modules.php');
		redirect($url);
	}
	
	$PAGE->set_title(get_string("addmodule", "local_paperattendance"));
	$PAGE->set_heading(get_string("addmodule", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("addmoduletitle", "local_paperattendance"));
	$addform->display();
=======
	}
>>>>>>> refs/remotes/webcursosuai/master
}
if ($action == "edit") {
	if ($idmodule == null) {
		print_error(get_string("moduledoesnotexist", "local_attendance"));
		$action = "view";
	} else {
		if ($module = $DB->get_record("paperattendance_module", array(
				"id" => $idmodule))) {
				$editform = new paperattendance_editmodule_form(null, array(
						"idmodule" => $idmodule));
				$defaultdata = new stdClass();
				$defaultdata->name = $module->name;
				$defaultdata->initialtime = $module->initialtime;
				$defaultdata->endtime = $module->endtime;
				$editform->set_data($defaultdata);
				if ($editform->is_cancelled()) {
					$action = "view";
<<<<<<< HEAD
					
					$url = new moodle_url('/local/paperattendance/modules.php');
					redirect($url);
=======
>>>>>>> refs/remotes/webcursosuai/master
				} else if ($editform->get_data() && $sesskey == $USER->sesskey) {
					$record = new stdClass();
					$record->id = $editform->get_data()->idmodule;
					$record->name = $editform->get_data()->name;
					$record->initialtime = $editform->get_data()->initialtime;
					$record->endtime = $editform->get_data()->endtime;
					$DB->update_record("paperattendance_module", $record);
					$action = "view";
<<<<<<< HEAD
					
					$url = new moodle_url('/local/paperattendance/modules.php');
					redirect($url);
=======
>>>>>>> refs/remotes/webcursosuai/master
				}
		} else {
			print_error(get_string("moduledoesnotexist", "local_paperattendance"));
			$action = "view";
<<<<<<< HEAD
			$url = new moodle_url('/local/paperattendance/modules.php');
			redirect($url);
		}

	}
	
	$PAGE->set_title(get_string("editmodule", "local_paperattendance"));
	$PAGE->set_heading(get_string("editmodule", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("editmoduletitle", "local_paperattendance"));
	$editform->display();
=======
		}
	}
>>>>>>> refs/remotes/webcursosuai/master
}
if ($action == "delete") {
	if ($idmodule == null) {
		print_error(get_string("moduledoesnotexist", "local_paperattendance"));
		$action = "view";
	} else {
		if ($module = $DB->get_record("paperattendance_module", array(
				"id" => $idmodule))) {
				if ($sesskey == $USER->sesskey) {
					$DB->delete_records("paperattendance_module", array(
							"id" => $module->id));
<<<<<<< HEAD
=======
					$DB->delete_records_select("paperattendance_session_module", "moduleid = ?", array(
							$module->id));
>>>>>>> refs/remotes/webcursosuai/master
					$action = "view";
				} else {
					print_error(get_string("usernotloggedin", "local_paperattendance"));
				}
		} else {
			print_error(get_string("moduledoesnotexist", "local_paperattendance"));
			$action = "view";
		}
	}
<<<<<<< HEAD
	$url = new moodle_url('/local/paperattendance/modules.php');
	redirect($url);
}


=======
}
if ($action == "add") {
	$PAGE->set_title(get_string("addmodule", "local_paperattendance"));
	$PAGE->set_heading(get_string("addmodule", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("addmodule", "local_paperattendance"));
	$addform->display();
}
if ($action == "edit") {
	$PAGE->set_title(get_string("editmodule", "local_paperattendance"));
	$PAGE->set_heading(get_string("editmodule", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("editmodule", "local_paperattendance"));
	$editform->display();
}
if ($action == "view") {
	$PAGE->set_title(get_string("viewmodules", "local_paperattendance"));
	$PAGE->set_heading(get_string("viewmodules", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("viewmodules", "local_paperattendance"));
	if (count($modules) == 0) {
		echo html_writer::nonempty_tag("h4", get_string("nomodules", "local_paperattendance"), array(
				"align" => "center"));
	} else {
		echo html_writer::table($modulestable);
	}
	echo html_writer::nonempty_tag("div", $OUTPUT->single_button($buttonurl, get_string("addmodule", "local_paperattendance")),
			array(
					"align" => "center"));
}
>>>>>>> refs/remotes/webcursosuai/master
echo $OUTPUT->footer();