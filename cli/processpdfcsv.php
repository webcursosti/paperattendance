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
* @copyright  2017 Jorge Cabané (jcabane@alumnos.uai.cl) 					
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('CLI_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->dirroot . "/repository/lib.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->libdir . '/clilib.php'); 
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");

global $DB;

// Now get cli options
list($options, $unrecognized) = cli_get_params(array(
		'help' => false,
		'debug' => false,
), array(
		'h' => 'help',
		'd' => 'debug'
));
if($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}
// Text to the paperattendance console
if($options['help']) {
	$help =
	// Todo: localize - to be translated later when everything is finished
	"Process pdf located at folder unread on moodle file system.
	Options:
	-h, --help            Print out this help
	Example:
	\$sudo /usr/bin/php /local/paperattendance/cli/processpdf.php";
	echo $help;
	die();
}
//heading
cli_heading('Paper Attendance pdf processing'); // TODO: localize
echo "\nSearching for unread pdfs\n";
echo "\nStarting at ".date("F j, Y, G:i:s")."\n";

$initialtime = time();
$read = 0;
$found = 0;

$DB->execute('SET SESSION wait_timeout = 28800');
$DB->execute('SET SESSION interactive_timeout = 28800');

mtrace("Start - line 74: ". memory_get_usage() . "\n");

// Sql that brings the unread pdfs names
$sqlunreadpdfs = "SELECT  id, filename AS name, uploaderid AS userid
	FROM {paperattendance_unprocessed}
	ORDER BY lastmodified ASC";

// Read the pdfs if there is any unread, with readpdf function
if($resources = $DB->get_records_sql($sqlunreadpdfs, array())){
	$path = $CFG -> dataroot. "/temp/local/paperattendance/unread";
	mtrace("Query find data correctly");
	mtrace("$sqlunreadpdfs - line 85: ". memory_get_usage() . "\n");
	foreach($resources as $pdf){
		$found++;
		mtrace("Found ".$found." pdfs");
		$uploaderobj = $DB->get_record("user", array("id" => $pdf-> userid));
		$process = paperattendance_runcsvproccessing($path, $pdf-> name, $uploaderobj); 
		mtrace("each pdf - line 91: ". memory_get_usage() . "\n");
		
 		if($process){
 			mtrace("Pdf ".$found." correctly processed");
 			$read++;
 			$DB->delete_records("paperattendance_unprocessed", array('id'=> $pdf-> id)); 
 			mtrace("Pdf ".$found." deleted from unprocessed table");
 			//TODO: unlink al pdf grande y viejo y ya no utilizado
 			mtrace("pdf procesasdo correctamente - line 99: ". memory_get_usage() . "\n");
 		}
 		else{
 			mtrace("problem reading the csv or with the pdf");
 		}
	}
	mtrace("Fin foreach - line 105: ". memory_get_usage() . "\n");
	
	echo $found." PDF found. \n";
	echo $read." PDF processed. \n";
	
	// Displays the time required to complete the process
	$finaltime = time();
	$executiontime = $finaltime - $initialtime;
	
	echo "Execution time: ".$executiontime." seconds. \n";
}else{
	echo $found." pdfs found. \n";
}

mtrace("Fin CLI - line 119: ". memory_get_usage() . "\n");

exit(0);
