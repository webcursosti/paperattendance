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

global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();
$url = new moodle_url("/local/paperattendance/history.php");
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_pagelayout("standard");

// Possible actions -> view, scan or asistencia alumnos . Standard is view mode
$action = optional_param("action", "view", PARAM_TEXT);
$idattendance = optional_param("idattendance", null, PARAM_INT);
$idcurso = required_param('courseid', PARAM_INT);

require_login();
if (isguestuser()){
	die();
}

$PAGE->set_title("Historial de Asistencia");
$PAGE->set_heading("HISTORIAL DE ASISTENCIA");

echo $OUTPUT->header();


// Lists all records in the database
if ($action == "view"){
	$sql = "SELECT s.id, sm.date, CONCAT( m.initialtime, ' - ', m.endtime) AS hour, s.pdf
	FROM {paperattendance_session} AS s INNER JOIN {paperattendance_sessmodule} AS sm ON (s.id = sm.sessionid) 
	INNER JOIN {paperattendance_module} AS m ON (sm.moduleid = m.id) GROUP BY s.id ";
	
	$attendances = $DB->get_records_sql($sql);
	
	$attendancestable = new html_table();

	if (count($attendances) > 0){
		$attendancestable->head = array(
				"#",
				"Fecha",
				"Hora",
				"Scan",
				"Asistencia alumnos"
		);
		

		foreach ($attendances as $attendance){
			// Define scan icon and url
			$scanurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
					"action" => "scan",
					"$idattendance" => $attendance->id,
			));
			$scanicon_attendance = new pix_icon("e/new_document", "Ver");
			$scanaction_attendance = $OUTPUT->action_icon(
					$scanurl_attendance,
					$scanicon_attendance,
					new confirm_action("Confirme")
					);

			// Define Asistencia alumnos icon and url
			$asistenciaalumnosurl_attendance = new moodle_url("/local/paperattendance/history.php", array(
					"action" => "asistenciaalumnos",
					"$idattendance" => $attendance->id
			));
			$asistenciaalumnosicon_attendance = new pix_icon("e/preview", "Ver Alumnos");
			$asistenciaalumnosaction_attendance = $OUTPUT->action_icon(
					$asistenciaalumnosurl_attendance,
					$asistenciaalumnosicon_attendance,
					new confirm_action("Confirme")
					);
			
			$attendancestable->data[] = array(
					$attendance->id,
					date("d-m-Y", $attendance->date),
					$attendance->hour,
					$scanaction_attendance,
					$asistenciaalumnosaction_attendance
			);
		}
	}
	
	$buttonurl = new moodle_url("/course/view.php", array("id" => $idcurso));
	
}	

// Displays all the records and options
if ($action == "view"){

	if (count($attendances) == 0){
		echo html_writer::nonempty_tag("h4", "No existen registros", array("align" => "left"));
	}else{
		echo html_writer::table($attendancestable);
	}
	echo html_writer::nonempty_tag("div", $OUTPUT->single_button($buttonurl, "Volver al Curso"), array("align" => "left"));
}

echo $OUTPUT->footer();







