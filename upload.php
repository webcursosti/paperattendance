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
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/forms/upload_form.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
require_once ($CFG->dirroot . "/mod/emarking/lib/openbub/ans_pdf_open.php");
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi.php");
global $DB, $OUTPUT, $USER;
// User must be logged in.
require_login();
if (isguestuser()) {
    print_error(get_string('notallowedupload', 'local_paperattendance'));
    die();
}
$courseid = optional_param('courseid', null, PARAM_INT);
$category = optional_param('categoryid', 1, PARAM_INT);

if($course = $DB->get_record("course", array("id" => $courseid))){
	if($category == 1){
		$category = $course->category;
	}
}

$context = context_coursecat::instance($category);

if (! has_capability('local/paperattendance:upload', $context)) {
    print_error(get_string('notallowedupload', 'local_paperattendance'));
}
// This page url.
$url = new moodle_url('/local/paperattendance/upload.php', array(
    'courseid' => $courseid));
if($courseid && $courseid != 1){
	$courseurl = new moodle_url('/course/view.php', array(
			'id' => $courseid			
	));
	$PAGE->navbar->add($course->fullname, $courseurl );
}
$PAGE->navbar->add(get_string('uploadtitle', 'local_paperattendance'));
$PAGE->navbar->add(get_string('header', 'local_paperattendance'),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
// Add the upload form for the course.
$addform = new paperattendance_upload_form (null, array("courseid" => $courseid));
// If the form is cancelled, refresh the instante.
if ($addform->is_cancelled()) {
	$courseurl = new moodle_url('/course/view.php', array(
			'id' => $courseid));
    redirect($courseurl);
    die();
} 
if ($addform->get_data()) {
	require_capability('local/paperattendance:upload', $context);
	
	$data = $addform -> get_data();
	
	$path = $CFG -> dataroot. "/temp/local/paperattendance";
	if (!file_exists($path . "/unread/")) {
			mkdir($path . "/unread/", 0777, true);
	}	
	// Save file
	$filename = $addform->get_new_filename('file');
	$file = $addform->save_file('file', $path."/unread/".$filename, false);
	$time = strtotime(date("d-m-Y H:s:i"));
	// Validate that file was correctly uploaded.
	$attendancepdffile = $path . "/unread/paperattendance_".$courseid."_".$time.".pdf";
	
	//first check if there's a readable QR code 
	//if(paperattendance_get_qr_text($path."/unread/", "paperattendance_".$courseid."_".$time.".pdf") == "error"){
	if(paperattendance_get_qr_text($path."/unread/", $filename) == "error"){
		$courseurl = new moodle_url('/course/view.php', array(
				'id' => $courseid));
		redirect($courseurl, get_string('couldntreadqrcode', 'local_paperattendance'), 3);
		die();
	}
	
	//read pdf and rewrite it 
	$pdf = new FPDI();
	// get the page count
	if($pagecount = $pdf->setSourceFile($path."/unread/".$filename)){
		// iterate through all pages
		for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
		    // import a page
		    $templateid = $pdf->importPage($pageno);
		    // get the size of the imported page
		    $size = $pdf->getTemplateSize($templateid);
		
		    // create a page (landscape or portrait depending on the imported page size)
		    if ($size['w'] > $size['h']) {
		        $pdf->AddPage('L', array($size['w'], $size['h']));
		    } else {
		        $pdf->AddPage('P', array($size['w'], $size['h']));
		    }
		
		    // use the imported page
		    $pdf->useTemplate($templateid);
		}
		$pdf->Output($attendancepdffile, "F"); // Se genera el nuevo pdf.
		
		$fs = get_file_storage();
		
		$file_record = array(
				'contextid' => $context->id,
				'component' => 'local_paperattendance',
				'filearea' => 'draft',
				'itemid' => 0,
				'filepath' => '/',
				'filename' => "paperattendance_".$courseid."_".$time.".pdf",
				'timecreated' => time(),
				'timemodified' => time(),
				'userid' => $USER->id,
				'author' => $USER->firstname." ".$USER->lastname,
				'license' => 'allrightsreserved'
		);
		
		// If the file already exists we delete it
		if ($fs->file_exists($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$time.".pdf")) {
			$previousfile = $fs->get_file($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$time.".pdf");
			$previousfile->delete();
		}
		
		// Info for the new file
		$fileinfo = $fs->create_file_from_pathname($file_record, $attendancepdffile);
		
		//rotate pages of the pdf if necessary
		paperattendance_rotate($path."/unread/", "paperattendance_".$courseid."_".$time.".pdf");
		
		//read pdf and save session and sessmodules
		$pdfprocessed = paperattendance_read_pdf_save_session($path."/unread/", "paperattendance_".$courseid."_".$time.".pdf");
		
		if($pdfprocessed == "Perfect"){		
			//delete unused pdf
			unlink($path."/unread/".$filename);		
			// Display confirmation page before moving out.
			redirect($url, get_string('uploadsuccessful', 'local_paperattendance'), 3);
			//die();
		}
		else{			
			//delete unused pdf
			unlink($path."/unread/".$filename);		
			// Display confirmation page before moving out.
			redirect($url, $pdfprocessed, 3);
		}
	}
	else{
		//delete unused pdf
		unlink($path."/unread/".$filename);
		
		print_error(get_string("pdfextensionunrecognized", "local_paperattendance"));
		die();
	}
}
// If there is no data or is it not cancelled show the header, the tabs and the form.
echo $OUTPUT->header();
if($courseid && $courseid != 1){
	echo $OUTPUT->heading("Subir lista escaneada " . $course->shortname . " " . $course->fullname);
}else{
	echo $OUTPUT->heading("Subir lista escaneada ");
}
// Display the form.
$addform->display();

echo $OUTPUT->footer();