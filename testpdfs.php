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
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
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

$path = $CFG -> dataroot. "/temp/local/paperattendance/unread";
//$process = paperattendance_readpdf($path, "paperattendance_2_1469479057.pdf", 2);

$imagick = new Imagick();
$imagick->setResolution(300,300);
$imagick->readImage($path.'/paperattendance_2_1469479057.pdf[0]');
$imagick = $imagick->flattenImages();
//$imagick->resizeImage(844, 1096, Imagick::FILTER_BOX, 0, false);
$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );

$height = $imagick->getImageHeight();
$width = $imagick->getImageWidth();
//echo $height." ".$width;

for ($countstudent = 0; $countstudent < 26; $countstudent++){

	$frame = $imagick->getImageRegion($width*0.031, $height*0.02, $width*0.799, $height*(0.169 + 0.02692*$countstudent));
	$frame->writeImage('student_'.$countstudent.'.png');
	$x = $frame->getImageChannelMean(Imagick::CHANNEL_GRAY);
	//echo "<br>Imagen $countstudent media ".$x["mean"]." desviacion ".$x["standardDeviation"];
	if($x["mean"] < 62900){
		echo "Alumno".$countstudent ." presente";
	}
	else{
		echo "Alumno".$countstudent ." ausente";
	}
echo "<br>";
}

echo $OUTPUT->footer();
