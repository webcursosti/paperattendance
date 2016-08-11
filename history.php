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
* @copyright  2016 Matías Queirolo (mqueirolo@alumnos.uai.cl) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Pertenece al plugin PaperAttendance

require_once (dirname(dirname(dirname(__FILE__)))."/config.php");
require_once ($CFG->dirroot."/local/paperattendance/forms/history_form.php");

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_course::instance($COURSE->id);
$url = new moodle_url("/local/paperattendance/history.php");
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

// Possible actions -> view, scan or asistencia alumnos . Standard is view mode
$action = optional_param("action", "view", PARAM_TEXT);
$idattendance = optional_param("idattendance", null, PARAM_INT);
$idpresence = optional_param("idpresence", null, PARAM_INT);
$idcourse = required_param('courseid', PARAM_INT);

require_login();
if (isguestuser()){
	die();
}
/*For the moment the capabilities isn't working right
if( !has_capability("local/paperattendance:history", $context) ){
	print_error("ACCESS DENIED");
}
*/
/////////Begins Teacher's View
	
if( has_capability("local/paperattendance:teacherview", $context)) {
	
	// action-> Students Attendance
	if ($action == "studentsattendance"){
		
		$getstudentsattendance = 'SELECT 
	                u.lastname,
	                u.firstname,
	                u.email,
					p.status,
					p.id AS idp
	                FROM {course} AS c
	                INNER JOIN {context} AS ct ON (c.id = ct.instanceid)
	                INNER JOIN {role_assignments} AS ra ON (ra.contextid = ct.id)
	                INNER JOIN {user} AS u ON (u.id = ra.userid)
	                INNER JOIN {role} AS r ON (r.id = ra.roleid)
					INNER JOIN {paperattendance_presence} AS p ON (u.id = p.userid)				
	                WHERE c.id = ? AND r.archetype = "student" AND p.sessionid = ?  ';
		
		$attendances = $DB->get_records_sql($getstudentsattendance, array($idcourse, $idattendance));
		
		$attendancestable = new html_table();
		
		//Check if we have at least one attendance in the selected session
		if (count($attendances) > 0){
			$attendancestable->head = array(
					"#",
					"Alumno",
					"Correo",
					"Asistencia",
					"Ajustes"
			);
			//A mere counter for de number of records in the table
			$counter = 1;
			foreach ($attendances as $attendance){
		
				$urlattendance = new moodle_url("#");
				
				//Define presente or ausente icon and url
				$presenticon = new pix_icon("i/valid", "Presente");
		
				$presenticonaction = $OUTPUT->action_icon(
						$urlattendance,
						$presenticon
						);
		
				$absenticon = new pix_icon("i/invalid", "Ausente");
		
				$absenticonaction = $OUTPUT->action_icon(
						$urlattendance,
						$absenticon
						);
				
				// Define edition icon and url
				$editurlattendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "edit",
						"idpresence" => $attendance->idp,
						"idattendance" => $idattendance,
						"courseid" => $idcourse
				));
				$editiconattendance = new pix_icon("i/edit", "Editar");
				$editactionasistencia = $OUTPUT->action_icon(
						$editurlattendance,
						$editiconattendance
						);
				
				$name = ($attendance->firstname.' '.$attendance->lastname);
				
				//Now we check if the student is present or not
				if ($attendance->status == 1){
						
					$attendancestable->data[] = array(
							$counter,
							$name,
							$attendance->email,
							$presenticonaction,
							$editactionasistencia
					);
				}
				else {
						
					$attendancestable->data[] = array(
							$counter,
							$name,
							$attendance->email,
							$absenticonaction,
							$editactionasistencia
					);
				}
				$counter++;
			}
		}
		
		$viewbackbutton = new moodle_url("/local/paperattendance/history.php", array("action" => "view", "courseid" => $idcourse));			
	}	
	// Edits an existent record for the students attendance view
	if($action == "edit"){
		if($idpresence == null){
			print_error("Estudiante no seleccionado");
			$canceled = new moodle_url("/local/paperattendance/history.php", array(
							"action" => "studentsattendance",
							"idattendance" => $idattendance,
							"courseid" => $idcourse
					));
			redirect($canceled);
		}
		else{
			
			if($attendance = $DB->get_record("paperattendance_presence", array("id" => $idpresence)) ){
			
				$editform = new editattendance(null, array(
						"idattendance" => $idattendance,
						"courseid" => $idcourse,
						"idpresence" => $idpresence
				));
	
				if($editform->is_cancelled()){
					$canceled = new moodle_url("/local/paperattendance/history.php", array(
							"action" => "studentsattendance",
							"idattendance" => $idattendance,
							"courseid" => $idcourse
					));
					redirect($canceled);
	
				}
				else if($editform->get_data()){
	
					$record = new stdClass();
					$record->id = $idpresence;
					$record->lastmodified = time();
					
					if ($editform->get_data()->status == 1){
						$record->status = 1;
					}
					else {
						$record->status = 0;
					}
							
					$DB->update_record("paperattendance_presence", $record);
					
					$backurl = new moodle_url("/local/paperattendance/history.php", array(
							"action" => "studentsattendance",
							"idattendance" => $idattendance,
							"courseid" => $idcourse
					));
					redirect($backurl);										
				}
			}
			else{
				print_error("Estudiante no existe");
				$canceled = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "studentsattendance",
						"idattendance" => $idattendance,
						"courseid" => $idcourse
				));
				redirect($canceled);
			
			}
		}
	}
	
	//Scan view
	if($action == "scan"){
		
		$backurl = new moodle_url("/local/paperattendance/history.php", array(
				"action" => "view",
				"idattendance" => $idattendance,
				"courseid" => $idcourse
				));
		
		$viewbackbutton = html_writer::nonempty_tag(
				"div",
				$OUTPUT->single_button($backurl, "Volver"),
				array("align" => "left"
				));
		
		$getpdfname = 'SELECT
	            pdf
				FROM {paperattendance_session} AS ps
	            WHERE ps.id = ?';
		
		$pdfname = $DB->get_record_sql($getpdfname, array($idattendance));
		
		var_dump($context->id);
		//Context id as 1 because the var gets the number 6 , check it later
		$url = moodle_url::make_pluginfile_url(1, 'local_paperattendance', 'draft', 0, '/', $pdfname->pdf);
	
		$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
				"src" => $url,
				"style" => "height:75vh; width:60vw"
		));
	}
	
	// Lists all records in the database
	if ($action == "view"){
		$getattendances = "SELECT s.id, sm.date, CONCAT( m.initialtime, ' - ', m.endtime) AS hour, s.pdf
		FROM {paperattendance_session} AS s 
		INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid) 
		INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id) 
		WHERE s.courseid = ? 
		ORDER BY sm.date ASC";
		
		$attendances = $DB->get_records_sql($getattendances, array($idcourse));
		
		$attendancestable = new html_table();
		//we check if we have attendances for the selected course
		if (count($attendances) > 0){
			$attendancestable->head = array(
					"#",
					"Fecha",
					"Hora",
					"Scan",
					"Asistencia alumnos"
			);
			//A mere counter for the number of records
			$counter = 1;
			foreach ($attendances as $attendance){
				// Define scan icon and url
				$scanurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "scan",
						"idattendance" => $attendance->id,
						"courseid" => $idcourse
						
				));
				$scanicon_attendance = new pix_icon("e/new_document", "Ver");
				$scanaction_attendance = $OUTPUT->action_icon(
						$scanurl_attendance,
						$scanicon_attendance
						);
	
				// Define Asistencia alumnos icon and url
				$studentsattendanceurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
						"action" => "studentsattendance",
						"idattendance" => $attendance->id,
						"courseid" => $idcourse
				));
				$studentsattendanceicon_attendance = new pix_icon("e/preview", "Ver Alumnos");
				$studentsattendanceaction_attendance = $OUTPUT->action_icon(
						$studentsattendanceurl_attendance,
						$studentsattendanceicon_attendance
						);
				
				$attendancestable->data[] = array(
						$counter,
						date("d-m-Y", $attendance->date),
						$attendance->hour,
						$scanaction_attendance,
						$studentsattendanceaction_attendance
				);
				$counter++;
			}
		}
		
		$buttonurl = new moodle_url("/course/view.php", array("id" => $idcourse));
		
	}	
	
	$PAGE->set_title("Historial de Asistencia");
	$PAGE->set_heading("HISTORIAL DE ASISTENCIA");
	
	echo $OUTPUT->header();
	
	// Displays Students Attendance view
	if ($action == "studentsattendance"){
		
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", "No existen registros", array("align" => "left"));
		}
		else{
			echo html_writer::table($attendancestable);
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($viewbackbutton, "Atrás"), array("align" => "left"));
		
	}
	
	// Displays the form to edit a record
	if( $action == "edit" ){
		$editform->display();
	}
	
	//Displays the scan file
	if($action == "scan"){
	
		// Donwload and back buttons
		echo $OUTPUT->action_icon($url, new pix_icon('i/grades', "descargar"), null, array("target" => "_blank"));
		echo html_writer::nonempty_tag("h7", "Descargar asistencia", array("align" => "left"));
		//echo "Descargar lista de asistencia";
	
		echo $viewbackbutton;
	
		// Preview PDF
		echo $viewerpdf;
	}
	
	// Displays all the records and options
	if ($action == "view"){
	
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", "No existen registros", array("align" => "left"));
		}
		else{
			echo html_writer::table($attendancestable);
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($buttonurl, "Volver al Curso"), array("align" => "left"));
	}

}	

////////Ends Teacher's view


////////Begins Student's view
else {
	
	// Lists all records in the database
	if ($action == "view"){
		$getstudentattendances = "SELECT s.id, sm.date, CONCAT( m.initialtime, ' - ', m.endtime) AS hour, p.status
		FROM {paperattendance_session} AS s 
		INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid)
		INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id) 
		INNER JOIN {paperattendance_presence} AS p ON (s.id = p.sessionid) 
		INNER JOIN {user} AS u ON (u.id = p.userid) 
		WHERE s.courseid = ? AND u.id = ?
		ORDER BY sm.date ASC";
	
		$attendances = $DB->get_records_sql($getstudentattendances, array($idcourse, $USER->id));
	
		$attendancestable = new html_table();
	
		if (count($attendances) > 0){
			$attendancestable->head = array(
					"#",
					"Fecha",
					"Hora",
					"Asistencia"
			);
			//A mere counter for the numbers of records
			$counter = 1;
			foreach ($attendances as $attendance){
				
				$urlattendance = new moodle_url("#");
				
				$presenticon = new pix_icon("i/valid", "Presente");
				
				$presenticonaction = $OUTPUT->action_icon(
						$urlattendance,
						$presenticon
						);
				
				$absenticon = new pix_icon("i/invalid", "Ausente");
				
				$absenticonaction = $OUTPUT->action_icon(
						$urlattendance,
						$absenticon
						);
				//We check if the student is present or not
				if ($attendance->status == 1){
					
						$attendancestable->data[] = array(
						$counter,
						date("d-m-Y", $attendance->date),
						$attendance->hour,
						$presenticonaction
						);
				}
				else {
					
						$attendancestable->data[] = array(
						$counter,
						date("d-m-Y", $attendance->date),
						$attendance->hour,
						$absenticonaction
						);
				}
				$counter++;
			}
		}
	
		$backbuttonurl = new moodle_url("/course/view.php", array("id" => $idcourse));
	
	}
	
	$PAGE->set_title("Historial de Asistencia");
	$PAGE->set_heading("HISTORIAL DE ASISTENCIA");
	echo $OUTPUT->header();
	// Displays all the records and options
	if ($action == "view"){
	
		if (count($attendances) == 0){
			echo html_writer::nonempty_tag("h4", "No existen registros", array("align" => "left"));
		}
		else{
			echo html_writer::table($attendancestable);
		}
		echo html_writer::nonempty_tag("div", $OUTPUT->single_button($backbuttonurl, "Volver al Curso"), array("align" => "left"));
	}
	
}
///////Ends Student's view


echo $OUTPUT->footer();

