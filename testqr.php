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
require_once ($CFG->dirroot . '/local/paperattendance/phpqrcode/phpqrcode.php');
require_once ($CFG->dirroot . '/local/paperattendance/phpdecoder/QrReader.php');
global $DB, $PAGE, $OUTPUT, $USER;

$context = context_system::instance();

$urlprint = new moodle_url("/local/paperattendance/testpdfs.php");
// Page navigation and URL settings.
$pagetitle = "TEST imagick";
$PAGE->set_context($context);
$PAGE->set_url($urlprint);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

echo $OUTPUT->header();

$filename = "paperattendance_2.pdf";

$document = new Imagick($filename);
$pdftotalpages = $document->getNumberImages();
var_dump($pdftotalpages);

for ($pdfpage = 0; $pdfpage < $pdftotalpages; $pdfpage++) {
	//get pdf page orientation
	$orientation = get_orientation($filename,$pdfpage);
	echo "<br>".$orientation;
	
	//rotate pdf page if necessary
	if($orientation == "rotated"){
		$rotate = rotate($filename,$pdfpage);
		echo "<br>".$rotate;
	}
}


echo $OUTPUT->footer();


//returns orientation {straight, rotated, error}
//pdf = pdfname + extension (.pdf)
function get_orientation($pdf , $page){
	$pdfexplode = explode(".",$pdf);
	$pdfname = $pdfexplode[0];
	$qrpath = $pdfname.'qr.png';
	
	//save the pdf page as a png
	$myurl = $pdf.'['.$page.']';
	$image = new Imagick($myurl);
	$image->setResolution(100,100);
	$image->setImageFormat( 'png' );
	$image->writeImage( $pdfname.'.png' );
	
	//check if there's a qr on the top right corner
	$imagick = new Imagick();
	$imagick->setResolution(100,100);
	$imagick->readImage( $pdfname.'.png' );
	$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );
	
	$height = $imagick->getImageHeight();
	$width = $imagick->getImageWidth();
	
	$qrtop = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.014);
	$qrtop->writeImage("topright".$qrpath);
	
	// QR
	$qrcodetop = new QrReader("topright".$qrpath);
	$texttop = $qrcodetop->text(); //return decoded text from QR Code

	if($texttop == "" || $texttop == " " || empty($texttop)){
		
		//check if there's a qr on the bottom right corner
		$qrbottom = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.846);
		$qrbottom->writeImage("bottomright".$qrpath);
		
		// QR
		$qrcodebottom = new QrReader("bottomright".$qrpath);
		$textbottom = $qrcodebottom->text(); //return decoded text from QR Code
		
			if($textbottom == "" || $textbottom == " " || empty($textbottom)){
				
				//check if there's a qr on the top left corner
				$qrtopleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.1225, $height*0.014);
				$qrtopleft->writeImage("topleft".$qrpath);
		
				// QR
				$qrcodetopleft = new QrReader("topleft".$qrpath);
				$texttopleft = $qrcodetopleft->text(); //return decoded text from QR Code
				
				if($texttopleft == "" || $texttopleft == " " || empty($texttopleft)){
					
					//check if there's a qr on the top left corner
					$qrbottomleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.1255, $height*0.846);
					$qrbottomleft->writeImage("bottomleft".$qrpath);
					
					// QR
					$qrcodebottomleft = new QrReader("bottomleft".$qrpath);
					$textbottomleft = $qrcodebottomleft->text(); //return decoded text from QR Code
					
					if($textbottomleft == "" || $textbottomleft == " " || empty($textbottomleft)){
						return "error";
					}
					else{
						return "rotated";
					}
				}
				else{
					return "rotated";
				}
			}
			else{
				return "straight";
			}
	}
	else{
		return "straight";
	}
}

//pdf = pdfname + extension (.pdf)
function rotate($pdf, $page){
	$myurl = $pdf.'['.$page.']';
	$imagick = new Imagick();
	$imagick->readImage($myurl);
	$angle = 180;
	if($imagick->rotateimage(new ImagickPixel(), $angle)){
	$imagick->setImageFormat("pdf");
	$imagick->writeImage($pdf);
	return "1";
	}
	else{
	return "0";	
	}
}