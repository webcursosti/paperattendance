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


$imagick = new Imagick();
$imagick->setResolution(100,100);
$imagick->readImage('b1.png');
$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );


//$imagick->writeImages('attendance.jpg', false);
//$imagick->setImageFormat('png');
//$imagick->writeImage('b1.png');
$height = $imagick->getImageHeight();
$width = $imagick->getImageWidth();
echo $height." ".$width;

$circle = new Imagick();
$circle->readImage('img/circle.png');
$circle->resizeImage(29, 24, Imagick::FILTER_BOX ,0);

$arraycompare = array();
for ($countstudent = 0; $countstudent < 26; $countstudent++){
	//$imagick->setImagePage(0,0,0,0);
	//$imagick->resizeImage($width, $height, Imagick::FILTER_BOX, 0, false);
	//$imagick->cropImage($width*0.04, $height*0.68, $width*0.765, $height*0.18);

	$frame = $imagick->getImageRegion($width*0.0285, $height*0.022, $width*0.767, $height*(0.18+0.02625*$countstudent));
	//$imagick->cropImage($width*0.04, $height*0.026, $width*0.765, $height*(0.18+0.016*$countstudents));
	$frame->writeImage('student_'.$countstudent.'.png');
	$x = $frame->getImageChannelMean(Imagick::CHANNEL_GRAY);
	echo "<br>Imagen $countstudent media ".$x["mean"]." desviacion ".$x["standardDeviation"];
	/*
	$pixels = $frame->exportImagePixels(0, 0, $width*0.035, $height*0.022, "RBG", Imagick::PIXEL_CHAR);
	$average = array_sum($pixels)/count($pixels);
	echo "<br>Imagen $countstudent ".$average." ancho ".($width*0.035)." alto ".($height*0.022)."<br>";
	*/

	//$arraycompare[] = $frame->compareImages($circle, Imagick::METRIC_MEANSQUAREERROR);
}
/* Export the image pixels */
/*
$image = $imagick->coalesceImages();
foreach ($image as $frame){
	$frame->cropImage($width*0.04, $height*0.026, $width*0.765, $height*(0.18+0.016*$countstudents));
	$frame->setImagePage(0, 0, 0, 0); // Remove canvas
	$imagick->writeImage('student_'.$countstudents.'.png');
}*/

$imagick->writeImage('circles.png');
/*

// QR
$imagick = new Imagick();
$imagick->setResolution(100,100);
$imagick->readImage('1.png');
$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );

$pixel = $imagick->getImagePixelColor(1, 1); 
$colors = $pixel->getColor();
print_r($colors); // produces Array([r]=>255,[g]=>255,[b]=>255,[a]=>1);

$pixel->getColorAsString(); // produces rgb(255,255,255);


var_dump($pixel->getColorAsString());

$height2 = $imagick->getImageHeight();
$width2 = $imagick->getImageWidth();

$pixels = $imagick->exportImagePixels(0, 0, $width2, $height2, "G", Imagick::PIXEL_CHAR);
$average = array_sum($pixels)/count($pixels);
echo "<br>".$average."<br>";
/* Output */
//var_dump($pixels);



$qrcode = new QrReader('delcorte.png');
$text = $qrcode->text(); //return decoded text from QR Code

//echo "<br>".$text;
/*
$imagick->readImage('b2.pdf[0]');
//$imagick->writeImages('attendance.jpg', false);
$imagick->setImageFormat('png');
$imagick->writeImage('b2.png');
*/
echo $OUTPUT->footer();
