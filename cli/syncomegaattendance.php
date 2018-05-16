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
* @copyright  2017 Mihail Pozarski (mpozarski944@gmail.com)				
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

define('CLI_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
require_once ($CFG->libdir . '/clilib.php'); 

global $DB;

// Now get cli options
list($options, $unrecognized) = cli_get_params(array(
		'help' => false,
		'debug' => false,
		'initialdate' => null,
		'enddate' => null,
        'course' => null
), array(
		'h' => 'help',
		'd' => 'debug',
		'i' => 'initialdate',
		'e' => 'enddate',
        'c' => 'course'
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
cli_heading('Paper Attendance attendance sync with omega');
echo "\nStarting at ".date("F j, Y, G:i:s")."\n";
$initialtime = time();

if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
    if(!is_null($options['initialdate']) && !is_null($options['enddate'])){
        $sessionssql= "SELECT * FROM {paperattendance_session} where lastmodified > ? AND lastmodified < ?";
        $sessions = $DB->get_records_sql($sessionssql,array($options['initialdate'],$options['enddate']));
    }
    if(!is_null($options['course'])){
        $sessionssql= "SELECT * FROM {paperattendance_session} where courseid = ?";
        $sessions = $DB->get_records_sql($sessionssql,array($options['course']));
    }
    echo "query success\n";
    if(count($sessions) > 0){
        $countsessions = 0;
        $syncedsessions = 0;
        $countstudents = 0;
        $syncedstudents = 0;
        foreach ($sessions as $session){
            $countsessions++;
            $studentssql = "SELECT * FROM {paperattendance_presence} WHERE sessionid = ?";
            $students = $DB->get_records_sql($studentssql,array($session->id));
            
            $arrayalumnos = array();
            
            $count = 0;
            foreach ($students as $student){
                $count++;
                $line = array();
                $line['emailAlumno'] = paperattendance_getusername($student->userid);
                $line['resultado'] = "true";
                if($student->status == 1){
                    $line['asistencia'] = "true";
                }else{
                    $line['asistencia'] = "false";
                }
                $arrayalumnos[] = $line;
            }
            $countstudents += $count;
            if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
                if(paperattendance_omegacreateattendance($session->courseid, $arrayalumnos, $session->id)){
                    $syncedsessions++;
                    $syncedstudents += $count;
                    $update = new stdClass();
                    $update->id = $session->id;
                    $update->status = 1;
                    $DB->update_record("paperattendance_session", $update);
                }else{
                    echo "omega sync failed... check php code \n";
                }
            }else{
                echo "Omega api disconected... trying next session \n";
            }
        }
        echo "total sessions: $countsessions \n";
        echo "synced sessions: $syncedsessions \n";
        echo "total students: $countstudents \n";
        echo "synced students: $syncedstudents \n";
    }else{
        echo "No sessions for this dates \n";
    }

   /* $attendancesql = "Select * from {paperattendance_presence} where lastmodified > ? AND lastmodified < ?";
    $attendance = $DB->get_records_sql($attendancesql,array($options['initialdate'],$options['enddate']));
    
    $url =  $CFG->paperattendance_omegaupdateattendanceurl;
    $token =  $CFG->paperattendance_omegatoken;
    $updates = 0;
    $errors = 0;
    foreach($attendance as $precense){
        $curl = curl_init();
        $fields = array(
            "token" => $token,
            "asistenciaId" => $precense->omegaid,
            "asistencia" => $precense->status
        );
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
        $result = json_decode(curl_exec ($curl));
        curl_close ($curl);
        if($result->resultadoStr == 'ERROR: asistenciaId=0'){
            $errors++;
            echo "Precense $precense->id failed to update";
        }else{
            $updates++;
            echo "precense $precense->id correctly updated with omega id: $precense->omegaid and status: $precense->status";
        }
        echo $result->resultadoStr."\n";
        
    }
    echo "updated $updates precenses \n";
    echo "$errors precenses failed to update\n";*/
}else{
	echo "No Omega webapi connected \n";
}
$finaltime = time();
$executiontime = $finaltime - $initialtime;
echo "Execution time: ".$executiontime." seconds. \n";

exit(0);