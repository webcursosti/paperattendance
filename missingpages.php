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
* @copyright  2017 Jorge Cabané (jcabane@alumnos.uai.cl) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
global $CFG, $DB, $OUTPUT, $USER, $PAGE;

// User must be logged in.
require_login();
if (isguestuser()) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
	die();
}

// Action = { view, edit, delete }, all page options.
$action = optional_param('action', 'view', PARAM_TEXT);
$categoryid = optional_param('categoryid', 1, PARAM_INT);
$sesspageid = optional_param('sesspageid', 0, PARAM_INT);
$sesskey = optional_param("sesskey", null, PARAM_ALPHANUM);
//Page
$page = optional_param('page', 0, PARAM_INT);
$perpage = 30;

if(is_siteadmin()){
	//if the user is an admin show everything
	$sqlmissing = "SELECT * 
					FROM {paperattendance_sessionpages}
					WHERE processed = ?";

	$countmissing = count($DB->get_records_sql($sqlmissing, array(0)));
	$missing = $DB->get_records_sql($sqlmissing, array(0), $page*$perpage,$perpage);
}
else{
	//if the user is a secretary show their own uploaded attendances
	$sqlcategory = "SELECT cc.*
					FROM {course_categories} cc
					INNER JOIN {role_assignments} ra ON (ra.userid = ?)
					INNER JOIN {role} r ON (r.id = ra.roleid)
					INNER JOIN {context} co ON (co.id = ra.contextid)
					WHERE cc.id = co.instanceid AND r.shortname = ?";
	$categoryparams = array($USER->id, "secrepaper");
	$category = $DB->get_record_sql($sqlcategory, $categoryparams);
	if($category){
		$categoryid = $category->id;
	}else{
		print_error(get_string('notallowedmissing', 'local_paperattendance'));
	}
	
	$sqlmissing = "SELECT * 
					FROM {paperattendance_sessionpages}
					WHERE processed = ? AND uploaderid = ?";
	$params = array(0, $USER->id);
	
	$countmissing = count($DB->get_records_sql($sqlmissing, $params));
	$missing = $DB->get_record_sql($sqlcategory, $params, $page*$perpage,$perpage);
}

$context = context_coursecat::instance($categoryid);
$contextsystem = context_system::instance();

if (! has_capability('local/paperattendance:missingpages', $context) && ! has_capability('local/paperattendance:missingpages', $contextsystem)) {
	print_error(get_string('notallowedmissing', 'local_paperattendance'));
}

if($countmissing==0){
	print_error(get_string('nothingmissing', 'local_paperattendance'));
}

$url = new moodle_url('/local/paperattendance/missingpages.php');

$PAGE->navbar->add(get_string('missingpages', 'local_paperattendance'));
$PAGE->navbar->add(get_string('missingpages', 'local_paperattendance'), $url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');

if ($action == "view") {
    $missingtable = new html_table();
    if ($countmissing > 0) {
    	$missingtable->head = array(
    			get_string("hashtag", "local_paperattendance"),
        		get_string("scan", "local_paperattendance"),
    			get_string("pagenum", "local_paperattendance"),
        		get_string("uploader", "local_paperattendance"
        				));
    	
    	$counter = $page * $perpage + 1;
    	foreach ($missing as $miss) {
    		
    		//delete action
            $deletemissingurl = new moodle_url("/local/paperattendance/missingpages.php",
                    array(
                        "action" => "delete",
                    	"sesspageid" => $miss->pagenum,
                        "sesskey" => sesskey()                    	 
                    		
                    ));
            $deletemissingicon= new pix_icon("t/delete", get_string("delete", "local_paperattendance"
            		));
            $deleteactionmissing = $OUTPUT->action_icon($deletemissingurl, $deletemissingicon,
                    new confirm_action(get_string("doyouwantdeletemissing", "local_paperattendance")
                    		));
            
            //edit action
            $editurlmissing = new moodle_url("/local/paperattendance/missingpages.php",
                    array(
                        "action" => "edit",
                    	"sesspageid" => $miss->id,
                        "sesskey" => sesskey()
                    		
                    ));
            $editiconmissing = new pix_icon("i/edit", get_string("edit", "local_paperattendance"
            		));
            $editactionmissing = $OUTPUT->action_icon($editurlmissing, $editiconmissing,
                    new confirm_action(get_string("doyouwanteditmissing", "local_paperattendance")
                    		));
                        
            //view scan action
            $scanurl_attendance = new moodle_url("/local/paperattendance/missingpages.php", array(
            		"action" => "scan",
            		"sesspageid" => $miss->id
            ));
            $scanicon_attendance = new pix_icon("e/new_document", get_string('see', 'local_paperattendance'));
            $scanaction_attendance = $OUTPUT->action_icon(
            		$scanurl_attendance,
            		$scanicon_attendance
            		);
            
            //get username
            $username = paperattendance_getusername($miss->uploaderid);
            
            //add data to table
            $missingtable->data [] = array(
            	$counter,	
            	$scanaction_attendance,
            	$miss->pagenum,
            	$username,
                $deleteactionmissing . $editactionmissing);
            
            $counter++;
        }
    }
    
    $PAGE->set_title(get_string("viewmissing", "local_paperattendance"));
    $PAGE->set_heading(get_string("viewmissing", "local_paperattendance"));
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("viewmissingtitle", "local_paperattendance"));
  
    echo html_writer::table($missingtable);  
}

if ($action == "edit") {
	if ($sesspageid == null) {
		print_error(get_string("sessdoesnotexist", "local_attendance"));
		$action = "view";
	}
	else {
		if ($session = $DB->get_record("paperattendance_sessionpages", array("id" => $sesspageid))){
			$url = moodle_url::make_pluginfile_url($contextsystem->id, 'local_paperattendance', 'draft', 0, '/', $session->pdfname);
			
			$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
					"src" => $url,
					"style" => "height:75vh; width:60vw"
			));
			
			$inputs = html_writer::div('<label for="course">Curso:</label><input type="text" class="form-control" id="course" placeholder="2113-V-ECO121-1-1-2017">',"form-group", array());
			$inputs += html_writer::div('<label for="date">Fecha:</label><input type="text" class="form-control" id="date" placeholder="01-08-2017">',"form-group", array());
			$inputs += html_writer::div('<label for="module">Hora Módulo:</label><input type="text" class="form-control" id="module" placeholder="16:30">',"form-group", array());
			$inputs += html_writer::div('<label for="begin">Inicio Lista:</label><input type="text" class="form-control" id="begin" placeholder="27">',"form-group", array());
			$inputs += html_writer::div('<button type="submit" class="btn btn-default">Guardar</button>',"form-group", array());
			
		}
		else {
			print_error(get_string("missingpagesdoesnotexist", "local_paperattendance"));
			$action = "view";
			$url = new moodle_url('/local/paperattendance/missingpages.php');
			redirect($url);
		}

	}
	
	$PAGE->set_title(get_string("missingpages", "local_paperattendance"));
	$PAGE->set_heading(get_string("missingpages", "local_paperattendance"));
	echo $OUTPUT->header();
	echo $OUTPUT->heading(get_string("missingpagestitle", "local_paperattendance"));
	
	echo html_writer::div(get_string("missingpageshelp","local_paperattendance"),"alert alert-info", array("role"=>"alert"));
	$pdfarea = html_writer::nonempty_tag("div", $viewerpdf, array( "id"=>"pdfviewer", "type"=>"text", "style"=>"float:left"));
	$inputarea = html_writer::nonempty_tag("div", $inputs, array( "id"=>"inputs", "style"=>"float:right; margin-right:6%"));
	echo html_writer::div($pdfarea .$inputarea, "form");
	
	
	
}

if ($action == "delete") {
	if ($sesspageid== null) {
		print_error(get_string("missingdoesnotexist", "local_paperattendance"));
		$action = "view";
	}
	else {
		if ($session = $DB->get_record("paperattendance_sessionpages", array("id" => $sesspageid))) {
				if ($sesskey == $USER->sesskey) {
					$DB->delete_records("paperattendance_sessionpages", array("id" => $sesspageid));
					$action = "view";
				}
				else {
					print_error(get_string("usernotloggedin", "local_paperattendance"));
				}
		}
		else {
			print_error(get_string("missingdoesnotexist", "local_paperattendance"));
			$action = "view";
		}
	}
	$url = new moodle_url('/local/paperattendance/missingpages.php');
	redirect($url);
}

echo $OUTPUT->footer();