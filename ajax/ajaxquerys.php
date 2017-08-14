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
* @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
define('AJAX_SCRIPT', true);
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');

global $CFG, $DB, $USER, $PAGE, $OUTPUT;

require_login();
if (isguestuser()) {
	die();
}

$action = required_param('action', PARAM_ALPHA);
$omegaid = optional_param('omegaid', null, PARAM_TEXT);
$diasemana = optional_param('diasemana', null, PARAM_TEXT);
$data = optional_param('result', null, PARAM_TEXT);
$paths = optional_param('path', null, PARAM_TEXT);
$courseid = optional_param("courseid", 1, PARAM_INT);
$begin = optional_param("begin", 1, PARAM_INT);
$category = optional_param('category', 1, PARAM_INT);
$teacherid = optional_param("teacherid", 1, PARAM_INT);
$setstudentpresence = optional_param("setstudentpresence", 1, PARAM_INT);
$presenceid = optional_param("presenceid", 1, PARAM_INT);
$module = optional_param("module", null, PARAM_TEXT);
$date = optional_param("date", null, PARAM_TEXT);
$sessinfo = optional_param_array('sessinfo', array(), PARAM_INT);
$studentsattendance = optional_param_array('studentsattendance', array(), PARAM_INT);

switch ($action) {
	case 'curlgetmoduloshorario' :
		require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
		$token = $CFG->paperattendance_omegatoken;
		$url = $CFG->paperattendance_omegagetmoduloshorariosurl;
		if($courseid > 1){
			if($course = $DB->get_record("course", array("id" => $courseid)) ){
				if($course->idnumber != NULL){
					$context = context_coursecat::instance($course->category);
				}
			}
			else{
				$context = context_system::instance();
			}
		}else if($category > 1){
			$context = context_coursecat::instance($category);
		}else{
			$context = context_system::instance();
		}
		$isteacher = paperattendance_getteacherfromcourse($courseid, $USER->id);
		if(!has_capability("local/paperattendance:printsecre", $context) && !$isteacher && !is_siteadmin($USER) && !has_capability("local/paperattendance:print", $context)){
			print_error(get_string('notallowedprint', 'local_paperattendance'));
		}
		$curl = curl_init();

		$fields = array (
				"diaSemana" => $diasemana,
				"seccionId" => $omegaid,
				"token" => $token
		);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$result = curl_exec ($curl);
		curl_close ($curl);

		echo  json_encode($result);
		break;
	case 'getcourses' :
		if($category > 1){
			$context = context_coursecat::instance($category);
		}else{
			$context = context_system::instance();
		}
	
		$contextsystem = context_system::instance();
		if (! has_capability('local/paperattendance:printsearch', $context) && ! has_capability('local/paperattendance:printsearch', $contextsystem)) {
			print_error(get_string('notallowedprint', 'local_paperattendance'));
		}
		if(is_siteadmin()){
			$year = strtotime("1 January".(date('Y')));
			$filter = array($year, "%".$data."%", $data."%");
			$sqlcourses = "SELECT c.id,
						c.fullname,
						cat.name,
						u.id as teacherid,
						CONCAT( u.firstname, ' ', u.lastname) as teacher
						FROM {user} AS u
						INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
						INNER JOIN {context} ct ON (ct.id = ra.contextid)
						INNER JOIN {course} c ON (c.id = ct.instanceid)
						INNER JOIN {role} r ON (r.id = ra.roleid AND r.id IN ( 3, 4))
						INNER JOIN {course_categories} as cat ON (cat.id = c.category)
						WHERE (c.timecreated > ? AND c.idnumber > 0 ) AND (CONCAT( u.firstname, ' ', u.lastname) like ? OR c.fullname like ?)
						GROUP BY c.id
						ORDER BY c.fullname";
		}else{
			$paths = unserialize(base64_decode($paths));
			$pathscount = count($paths);
			$like = "";
			$counter = 1;
			foreach ($paths as $path){
				if($counter==$pathscount)
					$like.= "cat.path like '%/".$path."/%' OR cat.path like '%/".$path."'";
					else
						$like.= "cat.path like '%/".$path."/%' OR cat.path like '%/".$path."' OR ";
						$counter++;
			}
			$filter = array("%".$data."%", $data."%");
			$sqlcourses = "SELECT c.id,
						c.fullname,
						cat.name,
						u.id as teacherid,
						CONCAT( u.firstname, ' ', u.lastname) as teacher
						FROM {user} AS u
						INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
						INNER JOIN {context} ct ON (ct.id = ra.contextid)
						INNER JOIN {course} c ON (c.id = ct.instanceid)
						INNER JOIN {role} r ON (r.id = ra.roleid AND r.id IN ( 3, 4))
						INNER JOIN {course_categories} as cat ON (cat.id = c.category)
						WHERE ($like AND c.idnumber > 0 ) AND (CONCAT( u.firstname, ' ', u.lastname) like ? OR c.fullname like ?)
						GROUP BY c.id
						ORDER BY c.fullname";
		}
		$courses = $DB->get_records_sql($sqlcourses, $filter);
	
		echo json_encode($courses);
		break;
	case 'cartlist':
		require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
		$return = array();
		$course = $DB->get_record("course", array("id" => $courseid));
		if($teacherid!=1){
			$return['courseid'] = $courseid;
			$return['course'] = $course->fullname;
			$return['descriptionid'] = 0;
			$return['description'] = paperattendance_returnattendancedescription(false, 0);

			$requestorinfo = $DB->get_record("user", array("id" => $teacherid));
			$return['requestor'] = $requestorinfo->firstname." ".$requestorinfo->lastname;
			$return['requestorid'] = $teacherid;
		}
			
		if (paperattendance_checktoken($CFG->paperattendance_omegatoken)){
			//CURL get modulos horario
			$curl = curl_init();

			$url = $CFG->paperattendance_omegagetmoduloshorariosurl;
			$token = $CFG->paperattendance_omegatoken;

			$fields = array (
					"diaSemana" => $diasemana,
					"seccionId" => $course -> idnumber,
					"token" => $token
			);

			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($curl, CURLOPT_POST, TRUE);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
			curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
			$result = curl_exec ($curl);
			curl_close ($curl);

			$modules = array();
			$modules = json_decode($result);
			if(count($modules) == 0){
				$return['modules'] = false;
			}
			else{
				$return['modules'] = $modules;
			}

			echo json_encode($return);
		}
		break;
		case 'getliststudentspage':
			require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
			
			$return = array();
			
			$date = explode("-",$date);
			if(checkdate($date[1],$date[0],$date[2])){
			
				if($DB->get_record("paperattendance_module", array("initialtime" => $module))){
					
					if($course = $DB->get_record("course", array("shortname" => $data))){
					
						$context = context_course::instance($course->id);
						$studentlist = paperattendance_students_list($context->id, $course);
						
						if(count($studentlist) >= $begin){
							$arrayalumnos = array();
							$count = 1;
							$end = $begin + 25;
							foreach ($studentlist as $student){
								if($count>=$begin && $count<=$end){
									$studentobject = $DB->get_record("user", array("id" => $student->id));
									$line = array();
									$line["studentid"] = $student->id;
									$line["username"] = $studentobject->lastname.", ".$studentobject->firstname;
									//$line["username"] = paperattendance_getusername($student->id);
									$arrayalumnos[] = $line;
								}
								$count++;
							}
							$return["error"] = 0;
							$return["alumnos"] = $arrayalumnos;
							echo json_encode($return);
						}
						else{
							$return["error"] = "Inicio de lista incorrecto";
							echo json_encode($return);
						}
					}
					else{
						$return["error"] = "No existe curso";
						echo json_encode($return);
					}
				}else{
					$return["error"] = "Inicio de modulo incorrecto";
					echo json_encode($return);
				}
			}else{
				$return["error"] = "Fecha incorrecta";
				echo json_encode($return);
			}
		break;
		case 'changestudentpresence':
			require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
			
			if($attendance = $DB->get_record("paperattendance_presence", array("id" => $presenceid)) ){
				
				$record = new stdClass();
				$record->id = $presenceid;
				$record->lastmodified = time();
				$record->status = $setstudentpresence;
				
				$DB->update_record("paperattendance_presence", $record);
				
				if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
				
					$modifieduserid = $attendance -> userid;
					$omegaid = $attendance -> omegaid;
					
					$curl = curl_init();
					
					$url =  $CFG->paperattendance_omegaupdateattendanceurl;
					$token =  $CFG->paperattendance_omegatoken;
					
					if($data->status == 1){
						$status = "true";
					}
					else{
						$status = "false";
					}
					
					$fields = array (
							"token" => $token,
							"asistenciaId" => $omegaid,
							"asistencia" => $status
					);
					
					curl_setopt($curl, CURLOPT_URL, $url);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
					curl_setopt($curl, CURLOPT_POST, TRUE);
					curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
					curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
					$result = curl_exec ($curl);
					curl_close ($curl);
				}	
			}
			
			echo json_encode(1);
			break;
		case 'savestudentsattendance':
			require_once($CFG->dirroot . '/local/paperattendance/locallib.php');
			
			$return["sesion"] = print_r($sessinfo, true);
			$return["arregloinicialalumnos"] = print_r($studentsattendance, true);
	
			$sesspageid = $sessinfo[0]['sesspageid'];
			$shortname = $sessinfo[0]['shortname'];
			$date = $sessinfo[0]['date'];
			$module = $sessinfo[0]['module'];
			$begin = $sessinfo[0]['begin'];
			
			$numberpage =  ($begin + 25)/26;
			
			$sesspageobject = $DB->get_record("paperattendance_sessionpages", array("id"=> $sesspageid));
			$courseobject = $DB->get_record("course", array("shortname"=> $shortname));
			$moduleobject = $DB->get_record("paperattendance_module", array("initialtime"=> $module));
			
			$sessdoesntexist = paperattendance_check_session_modules($moduleobject->id, $courseobject->id, strtotime($date));
			//mtrace("checkeo de la sesion: ".$sessdoesntexist);
			$stop = true;
			$return = array();
			$return["sesiondos"] = "";
			$return["guardar"] = "";
			$return["omegatoken"] = "";
			$return["omegatoken2"] = "";
			if( $sessdoesntexist == "perfect"){
				//mtrace("no existe");
				$return["sesion"] = "Sesión no existe, ";
				
				//select teacher from course
				$teachersquery = "SELECT u.id AS userid,
							c.id AS courseid,
							e.enrol,
							CONCAT(u.firstname, ' ', u.lastname) AS name
							FROM {user} u
							INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
							INNER JOIN {enrol} e ON (e.id = ue.enrolid)
							INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
							INNER JOIN {context} ct ON (ct.id = ra.contextid)
							INNER JOIN {course} c ON (c.id = ct.instanceid AND e.courseid = c.id)
							INNER JOIN {role} r ON (r.id = ra.roleid)
							WHERE r.id = 3 AND c.id = ? AND e.enrol = 'database'";
				
				$teachers = $DB->get_records_sql($teachersquery, array($courseobject->id));
				
				$enrolincludes = explode("," ,$CFG->paperattendance_enrolmethod);
				
				foreach ($teachers as $teacher){
					
					$enrolment = explode(",", $teacher->enrol);
					// Verifies that the teacher is enrolled through a valid enrolment and that we haven't added him yet.
					if (count(array_intersect($enrolment, $enrolincludes)) == 0 || isset($arrayteachers[$teacher->userid])) {
						continue;
					}
					$requestor = $teacher->userid;
				}
				$description = 0; //0 = for normal class
				$sessid = paperattendance_insert_session($courseobject->id, $requestor, $USER->id, $sesspageobject->pdfname, $description);
				//mtrace("la session id es : ".$sessid);
				paperattendance_insert_session_module($moduleobject->id, $sessid, strtotime($date));
				//paperattendance_save_current_pdf_page_to_session($realpagenum, $sessid, $page, $pdffilename, 1, $uploaderobj->id);
				$pagesession = new stdClass();
				$pagesession->id = $sesspageid;
				$pagesession->sessionid = $sessid;
				$pagesession->pagenum = $sesspageobject->pagenum;
				$pagesession->qrpage = $numberpage;
				$pagesession->pdfname = $sesspageobject->pdfname;
				$pagesession->processed = 1;
				$pagesession->uploaderid = $USER->id;
				$DB->update_record('paperattendance_sessionpages', $pagesession);
			
			}
			else{
				//mtrace("session ya eexiste");
				//$return["sesion"] = "Sesión ya existe, ";
				$sessid = $sessdoesntexist; //if session exist, then $sessdoesntexist contains the session id
				
				//Check if the page already was processed
				if( $DB->record_exists('paperattendance_sessionpages', array('sessionid'=>$sessid,'qrpage'=>$numberpage)) ){
					//mtrace("session ya existe y esta hoja ya fue subida y procesada / el curso ingresado no es el mismo de la sesion existente");
					$return["sesiondos"] = "hoja procesada anteriormente.";
					//Falta eliminar esta pag ya que no sirve para nada y no se debiera volver a mostrar en missing pages
					$stop = false;
				}
				else{
					//paperattendance_save_current_pdf_page_to_session
					$pagesession = new stdClass();
					$pagesession->id = $sesspageid;
					$pagesession->sessionid = $sessid;
					$pagesession->pagenum = $sesspageobject->pagenum;
					$pagesession->qrpage = $numberpage;
					$pagesession->pdfname = $sesspageobject->pdfname;
					$pagesession->processed = 1;
					$pagesession->uploaderid = $USER->id;
					$DB->update_record('paperattendance_sessionpages', $pagesession);
					//mtrace("session ya existe pero esta hoja no habia sido subida ni procesada");
					$return["sesiondos"] = "hoja no procesada antes, ";
					$stop = true;
				}
			}
			if($stop){
				$arrayalumnos = array();
				$init = ($numberpage-1)*26+1;
				$end = $numberpage*26;
				$count = 1; //start at one because init starts at one
				foreach ($studentsattendance as $student){
					if($count>=$init && $count<=$end){
						$line = array();
						$line['emailAlumno'] = paperattendance_getusername($student['userid']);
						$line['resultado'] = "true";
						$line['asistencia'] = "false";
						
						if($student['presence'] == '1'){
							paperattendance_save_student_presence($sessid, $student['userid'], '1', NULL);
							$line['asistencia'] = "true";
						}
						else{
							paperattendance_save_student_presence($sessid, $student['userid'], '0', NULL);
						}
						
						$arrayalumnos[] = $line;
					}
					$count++;
				}
				$return["guardar"] = "asistencia guardada por cada alumno, ";
				$omegasync = false;
				
				if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
					$return["omegatoken"] = "Api aceptó token, ";
					$return["arregloalumnos"] = print_r($arrayalumnos, true);
					$return["idcurso"] = print_r($courseobject->id, true);
					$return["idsesion"] = print_r($sessid,true);
					if(paperattendance_omegacreateattendance($courseobject->id, $arrayalumnos, $sessid)){
						$omegasync = true;
						$return["omegatoken2"] = "se creó la asistencia en Omega. ";
					}
				}
				
				$update = new stdClass();
				$update->id = $sessid;
				if($omegasync){
					$update->status = 2;
				}
				else{
					$update->status = 1;
				}
				$DB->update_record("paperattendance_session", $update);
				
			}
			
			/*if ($stop){
				$return["error"] = "Asistencia correctamente guardada";
			}
			else{
				$return["error"] = "Página subida y procesada anteriormente";
			}*/
			echo json_encode($return);
			break;
}