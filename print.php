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
require_once (dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . "/local/paperattendance/forms/print_form.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
require_once ($CFG->dirroot . "/mod/emarking/lib/openbub/ans_pdf_open.php");
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi.php");
require_once ("locallib.php");
global $DB, $PAGE, $OUTPUT;
require_login();
if (isguestuser()) {
	die();
}

$courseid = required_param('courseid', PARAM_INT);

$context = context_system::instance();
if (! has_capability("local/paperattendance:print", $context)) {
	print_error("ACCESS DENIED");
}

$urlprint = new moodle_url("/local/paperattendance/print.php");
// Page navigation and URL settings.
$pagetitle = "Imprimir lista de asistencia";
$PAGE->set_context($context);
$PAGE->set_url($urlprint);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

// Add the print form 
$addform = new print_form(null, array("courseid" => $courseid));
// If the form is cancelled, redirect to course.
if ($addform->is_cancelled()) {
	$backtocourse = new moodle_url("/course/view.php", array('id' => $courseid));
	redirect($backtocourse);
}
else if ($data = $addform->get_data()) {
	// Create the PDF with the students
	// id teacher
	$teacher = $data->teacher;
	// date for session
	$sessiondate = $data->sessiondate;
	// array idmodule => {0 = no checked, 1 = checked}
	$modules = $data->modules;
	
	list($path, $filename) = paperattendance_create_qr_image($courseid."*".$teacher."*");
	
	$pdf = new FPDI();
	$pdf->AddPage();
	$pdf->SetPrintHeader(true);
	$pdf->SetPrintFooter(true);
	$pdf->Image($path."/".$filename, 160, 10, 35, 35, 'PNG' );
	$pdf->Image($path."/".$filename, 10, 235, 35, 35, 'PNG' );
	
	$attendancepdffile = $path . "/paperattendance_".$courseid.".pdf";
	$pdf->Output($attendancepdffile, "F"); // Se genera el nuevo pdf.
	$pdf = null;
	
	//unlink($attendancepdffile);
	//unlink($path."/".$filename);
}

$course = $DB->get_record("course",array("id" => $courseid));

$PAGE->set_heading($pagetitle);
echo $OUTPUT->header();

echo html_writer::nonempty_tag("h2", $course->shortname." - ".$course->fullname);

$addform->display();

echo $OUTPUT->footer();
