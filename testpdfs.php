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

print(class_exists('imagick'));
var_dump(class_exists('imagick'));
/*
$imagick = new Imagick();
$imagick->readImage('papperattendance_3.pdf');
$imagick->writeImages('attendance.jpg', false);
*/

echo $OUTPUT->footer();