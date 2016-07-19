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

//StartX : width -> 844  *  0,652   :    550
//StartY : height -> 1096  *  0,014 :    15
//aux var orientation {straight, rotated, error}



get_orientation("b4.pdf","0");


echo $OUTPUT->footer();

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
	$qrtop->writeImage($qrpath);
	
	// QR
	$qrcodetop = new QrReader($qrpath);
	$texttop = $qrcodetop->text(); //return decoded text from QR Code

	if($texttop == "" || $texttop == " " || empty($texttop)){
		
		//check if there's a qr on the bottom right corner
		$qrbottom = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.846);
		$qrbottom->writeImage($qrpath);
		
		// QR
		$qrcodebottom = new QrReader($qrpath);
		$textbottom = $qrcodebottom->text(); //return decoded text from QR Code
		
			if($textbottom == "" || $textbottom == " " || empty($textbottom)){
				
				//check if there's a qr on the top left corner
				$qrtopleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.355, $height*0.014);
				$qrtopleft->writeImage($qrpath);
		
				// QR
				$qrcodetopleft = new QrReader($qrpath);
				$texttopleft = $qrcodetopleft->text(); //return decoded text from QR Code
				
				if($texttopleft == "" || $texttopleft == " " || empty($texttopleft)){
					
					//check if there's a qr on the top left corner
					$qrbottomleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.355, $height*0.846);
					$qrbottomleft->writeImage($qrpath);
					
					// QR
					$qrcodebottomleft = new QrReader($qrpath);
					$textbottomleft = $qrcodebottomleft->text(); //return decoded text from QR Code
					
					if($textbottomleft == "" || $textbottomleft == " " || empty($textbottomleft)){
						echo "error";
					}
					else{
						echo "rotated";
					}
				}
				else{
					echo "rotated";
				}
			}
			else{
				echo "straight";
			}
	}
	else{
		echo "straight";
	}
}