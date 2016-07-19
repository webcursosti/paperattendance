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
$imagick->cropImage($width*0.25, $height*0.14, $width*0.652, $height*0.014);

/* Export the image pixels */

$imagick->writeImage('delcorte.png');

// QR
$qrcode = new QrReader('delcorte.png');
$text = $qrcode->text(); //return decoded text from QR Code

echo "<br>".$text;
/*
$imagick->readImage('b2.pdf[0]');
//$imagick->writeImages('attendance.jpg', false);
$imagick->setImageFormat('png');
$imagick->writeImage('b2.png');
*/
echo $OUTPUT->footer();