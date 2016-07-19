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

$myurl = 'b4.pdf[0]';
$image = new Imagick($myurl);
$image->setResolution(100,100);
$image->setImageFormat( "png" );
$image->writeImage('b4.png');

//check if there's a qr on the top right corner
$imagickqrtop = new Imagick();
$imagickqrtop->setResolution(100,100);
$imagickqrtop->readImage('b4.png');
$imagickqrtop->setImageType( imagick::IMGTYPE_GRAYSCALE );

$height = $imagickqrtop->getImageHeight();
$width = $imagickqrtop->getImageWidth();

$imagickqrtop->cropImage($width*0.25, $height*0.14, $width*0.652, $height*0.014);
//StartX : width -> 844  *  0,652   :    550
//StartY : height -> 1096  *  0,014 :    15

$imagickqrtop->writeImage('delcorte.png');

// QR
$qrcodetop = new QrReader('delcorte.png');
$texttop = $qrcodetop->text(); //return decoded text from QR Code

echo "<br> Qr top".$texttop;

//check if there's a qr on the bottom right corner
$imagickqrbottom = new Imagick();
$imagickqrbottom->setResolution(100,100);
$imagickqrbottom->readImage('b4.png');
$imagickqrbottom->setImageType( imagick::IMGTYPE_GRAYSCALE );

$heightbottom = $imagickqrbottom->getImageHeight();
$widthbottom = $imagickqrbottom->getImageWidth();

$imagickqrbottom->cropImage($widthbottom*0.25, $heightbottom*0.14, $widthbottom*0.652, $heightbottom*0.846);
//StartX : width -> 844  *  0,652   :    550
//StartY : height -> 1096  *  0,014 :    15

$imagickqrbottom->writeImage('delcortebottom.png');

// QR
$qrcodebottom = new QrReader('delcortebottom.png');
$textbottom = $qrcodebottom->text(); //return decoded text from QR Code

echo "<br>Qr bottom".$textbottom;

echo $OUTPUT->footer();