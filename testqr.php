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
require_once (dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . '/local/paperattendance/phpdecoder/QrReader.php');
require_once ("locallib.php");
global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();

$urlprint = new moodle_url("/local/paperattendance/testqr.php");
// Page navigation and URL settings.
$pagetitle = "TEST imagick";
$PAGE->set_context($context);
$PAGE->set_url($urlprint);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

echo $OUTPUT->header();

	$pdffile = "paperattendance_3_1469115554.pdf";
	function read_pdf_save_session($path, $pdffile){
	//path must end with "/"	
	
	$qrtext = get_qr_text($path, $pdffile);
	if($qrtext != "error"){
	//if there's a readable qr
	
	$qrtextexplode = explode("*",$qrtext);
	$courseid = $qrtextexplode[0];
	$requestorid = $qrtextexplode[1];
	$arraymodules = $qrtextexplode[2];
	$time = $qrtextexplode[3];
	$page = $qrtextexplode[4];
	
	$verification = check_session_modules($arraymodules, $courseid, $time);
	if($verification == "perfect"){
	$pos = substr_count($arraymodules, ':');
	if ($pos == 0) {
		$module = $arraymodules;
		$sessionid = insert_session($courseid, $requestorid, $USER-> id, $pdffile);
	    $verification = insert_session_module($module, $sessionid, $time);
		    if($verification == true){
		    	echo "<br> Perfect";
		    }
		    else{
		    	echo "<br> Error";
		    }
	} 
	else {
		$modulesexplode = explode(":",$arraymodules);
		
		for ($i = 0; $i <= $pos; $i++) {
			
			//for each module inside $arraymodules, save records.
		    $module = $modulesexplode[$i];
		    
		    $sessionid = insert_session($courseid, $requestorid, $USER-> id, $pdffile);
		    $verification = insert_session_module($module, $sessionid, $time);
			    if($verification == true){
			    	echo "<br> Perfect";
			    }
			    else{
			    	echo "<br> Error";
			    }
		}
	}
	}
	else{
		//couldnt read qr
		echo "Couldn´t read qr";
		echo "<br> Orientation is: " get_orientation($path, $pdffile, "0");
		echo "<br> Please make sure pdf is straight, without tilt and header on top";
	}
	}
	else{
		echo "<br> Session already exists";
	}
	}
	
// $filename = "paperattendance_2.pdf";

// $document = new Imagick($filename);
// $pdftotalpages = $document->getNumberImages();
// var_dump($pdftotalpages);
// $document->clear();

// for ($pdfpage = 0; $pdfpage < $pdftotalpages; $pdfpage++) {
// 	//get pdf page orientation
// 	$orientation = get_orientation($filename,"$pdfpage");
// 	echo "<br>".$orientation;
	
// 	//rotate pdf page if necessary
// 	if($orientation == "rotated"){
// 		$rotate = rotate($filename,$pdfpage, $pdftotalpages);
// 		echo "<br>".$rotate;
// 	}
// 	else {
// 		echo "page ".$pdfpage." is straight";
// 	}
// }
echo $OUTPUT->footer();