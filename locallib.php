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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
/**
 * @package    local
 * @subpackage paperattendance
 * @copyright  2016 Hans Jeria (hansjeria@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Define whether the pdf has been processed or not 
define('PAPERATTENDANCE_STATUS_UNREAD', 0); 	//not processed
define('PAPERATTENDANCE_STATUS_PROCESSED', 1); 	//already processed
define('PAPERATTENDANCE_STATUS_SYNC', 2); 		//already synced with omega

/**
* Creates a QR image based on a string
*
* @param unknown $qrstring
* @return multitype:string
*/
function paperattendance_create_qr_image($qrstring , $path){
		global $CFG;
		require_once ($CFG->dirroot . '/local/paperattendance/phpqrcode/phpqrcode.php');

		if (!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		
		$filename = "qr".substr( md5(rand()), 0, 4).".png";
		$img = $path . "/". $filename;
		QRcode::png($qrstring, $img);
		
		return $filename;
}

/**
 * Get all students from a course, for list.
 *
 * @param unknown_type $courseid
 */
function paperattendance_get_students_for_printing($course) {
	global $DB;
	
	$query = 'SELECT u.id, 
			u.idnumber, 
			u.firstname, 
			u.lastname, 
			GROUP_CONCAT(e.enrol) AS enrol
			FROM {user_enrolments} ue
			INNER JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = ?)
			INNER JOIN {context} c ON (c.contextlevel = 50 AND c.instanceid = e.courseid)
			INNER JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.roleid = 5 AND ra.userid = ue.userid)
			INNER JOIN {user} u ON (ue.userid = u.id)
			GROUP BY u.id
			ORDER BY lastname ASC';
	$params = array($course->id);
	$rs = $DB->get_recordset_sql($query, $params);
	
	return $rs;
}

/**
 * Get the student list
 * 
 * @param int $course
 *            Id course
 */
function paperattendance_students_list($contextid, $course){
	global $CFG;
	//TODO: Add enrolments for omega, Remember change "manual".
	$enrolincludes = array("manual");
	$filedir = $CFG->dataroot . "/temp/emarking/$contextid";
	$userimgdir = $filedir . "/u";
	$students = paperattendance_get_students_for_printing($course);
	
	$studentinfo = array();
	// Fill studentnames with student info (name, idnumber, id and picture).
	foreach($students as $student) {
		$studentenrolments = explode(",", $student->enrol);
		// Verifies that the student is enrolled through a valid enrolment and that we haven't added her yet.
		if (count(array_intersect($studentenrolments, $enrolincludes)) == 0 || isset($studentinfo[$student->id])) {
			continue;
		}
		// We create a student info object.
		$studentobj = new stdClass();
		$studentobj->name = substr("$student->lastname, $student->firstname", 0, 65);
		$studentobj->idnumber = $student->idnumber;
		$studentobj->id = $student->id;
		//$studentobj->picture = emarking_get_student_picture($student, $userimgdir);
		// Store student info in hash so every student is stored once.
		$studentinfo[$student->id] = $studentobj;
	}
	
	return $studentinfo;
}


/**
 * Draws a table with a list of students in the $pdf document
 *
 * @param unknown $pdf
 *            PDF document to print the list in
 * @param unknown $logofilepath
 *            the logo
 * @param unknown $downloadexam
 *            the exam
 * @param unknown $course
 *            the course
 * @param unknown $studentinfo
 *            the student info including name and idnumber
 */
function paperattendance_draw_student_list($pdf, $logofilepath, $course, $studentinfo, $requestorinfo, $modules, $qrpath, $qrstring, $webcursospath, $sessiondate) {
	global $CFG;
	// Pages should be added automatically while the list grows.
	$pdf->SetAutoPageBreak(false);
	$pdf->AddPage();
	// If we have a logo we draw it.
	$left = 20;
	if ($logofilepath) {
		$pdf->Image($logofilepath, $left, 15, 50);
		$left += 55;
	}
	
	// Top QR
	$qrfilename = paperattendance_create_qr_image($qrstring.$pdf->PageNo(), $qrpath);
	$pdf->Image($qrpath."/".$qrfilename, 153, 5, 35);
	// Botton QR and Logo Webcursos
	$pdf->Image($webcursospath, 18, 265, 35);
	$pdf->Image($qrpath."/".$qrfilename, 153, 256, 35);
	unlink($qrpath."/".$qrfilename);
	
	// We position to the right of the logo.
	$top = 7;
	$pdf->SetFont('Helvetica', 'B', 12);
	$pdf->SetXY($left, $top);

	// Write course name.
	$coursetrimmedtext = trim_text($course->fullname." - ".$course->shortname,30);
	$top += 6;
	$pdf->SetFont('Helvetica', '', 8);
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string('course') . ': ' . $coursetrimmedtext));
	
	$teachers = get_enrolled_users(context_course::instance($course->id), 'mod/emarking:supervisegrading');
	$teachersnames = array();
	foreach($teachers as $teacher) {
		$teachersnames[] = $teacher->firstname . ' ' . $teacher->lastname;
	}
	$teacherstring = implode(',', $teachersnames);
	$stringmodules = "";
	foreach ($modules as $key => $value){
		if($value == 1){
			$schedule = explode("*", $key);
			if($stringmodules == ""){
				$stringmodules .= $schedule[1]." - ".$schedule[2];
			}else{
				$stringmodules .= " / ".$schedule[1]." - ".$schedule[2];
			}
		}
	}
	// Write teacher name.
	$teachertrimmedtext = trim_text($teacherstring,30);
	$top += 4;
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string('teacher', 'mod_emarking') . ': ' . $teachertrimmedtext));
	// Write requestor.
	$requestortrimmedtext = trim_text($requestorinfo->firstname." ".$requestorinfo->lastname,30);
	$top += 4;
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string("requestor", 'local_paperattendance') . ': ' . $requestortrimmedtext));
	// Write date.
	$top += 4;
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string("date") . ': ' . date("d-m-Y", $sessiondate)));
	// Write modules.
	$modulestrimmedtext = trim_text($stringmodules,30);
	$top += 4;
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string("modulescheckbox", 'local_paperattendance') . ': ' . $modulestrimmedtext));
	// Write number of students.
	$top += 4;
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string('students') . ': ' . count($studentinfo)));
	// Write the table header.
	$left = 20;
	$top += 8;
	$pdf->SetXY($left, $top);
	$pdf->Cell(8, 8, "N°", 0, 0, 'C');
	$pdf->Cell(25, 8, core_text::strtoupper(get_string('idnumber')), 0, 0, 'L');
	$pdf->Cell(20, 8, core_text::strtoupper(get_string('photo', 'mod_emarking')), 0, 0, 'L');
	$pdf->Cell(90, 8, core_text::strtoupper(get_string('name')), 0, 0, 'L');
	$pdf->Cell(20, 8, core_text::strtoupper(get_string('pdfattendance','local_paperattendance')), 0, 0, 'L');
	$pdf->Ln();
	$top += 8;
	
	$circlepath = $CFG->dirroot . '/local/paperattendance/img/circle.png';
	
	// Write each student.
	$current = 1;
	$pdf->SetFillColor(228, 228, 228);
	foreach($studentinfo as $stlist) {

		$pdf->SetXY($left, $top);
		// Cell color
		if($current%2 == 0){
			$fill = 1;
		}else{
			$fill = 0;
		}
		// Number
		$pdf->Cell(8, 8, $current, 0, 0, 'L', $fill);
		// ID student
		$pdf->Cell(25, 8, $stlist->idnumber, 0, 0, 'L', $fill);
		// Profile image
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->Cell(20, 8, "", 0, 0, 'L', $fill);
//		$pdf->Image($stlist->picture, $x + 5, $y, 8, 8, "PNG", $fill);
		// Student name
		$pdf->Cell(90, 8, core_text::strtoupper($stlist->name), 0, 0, 'L', $fill);
		// Attendance
		$x = $pdf->GetX();
		$y = $pdf->GetY();
		$pdf->Cell(20, 8, "", 0, 0, 'C', 0);
		$pdf->Image($circlepath, $x + 5, $y+1, 6, 6, "PNG", 0);
		
		$pdf->line(20, $top, (20+8+25+20+90+20), $top);
		$pdf->Ln();
		
		if($current%26 == 0 && $current != 0){
			$pdf->AddPage();
			$top = 35;
			
			// Logo UAI and Top QR
			$pdf->Image($logofilepath, 20, 15, 50);
			// Top QR
			$qrfilename = paperattendance_create_qr_image($qrstring.$pdf->PageNo(), $qrpath);
			//echo $qrfilename."  ".$qrpath."<br>";
			$pdf->Image($qrpath."/".$qrfilename, 153, 5, 35);
			
			// Attendance info
			// Write teacher name.
			$leftprovisional = 75;
			$topprovisional = 7;
			$pdf->SetFont('Helvetica', 'B', 12);
			$pdf->SetXY($leftprovisional, $topprovisional);
			// Write course name.
			$topprovisional += 6;
			$pdf->SetFont('Helvetica', '', 8);
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper(get_string('course') . ': ' . $coursetrimmedtext));
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper(get_string('teacher', 'mod_emarking') . ': ' . $teachertrimmedtext));
			// Write requestor.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper("Solicitante" . ': ' . $requestortrimmedtext));
			// Write date.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper(get_string("date") . ': ' . date("h:s d-m-Y", time())));
			// Write modules.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper("Modulos" . ': ' . $modulestrimmedtext));
			// Write number of students.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper(get_string('students') . ': ' . count($studentinfo)));
			
			// Botton QR and Logo Webcursos
			$pdf->Image($webcursospath, 18, 265, 35);
			$pdf->Image($qrpath."/".$qrfilename, 153, 256, 35);
			unlink($qrpath."/".$qrfilename);
		}
		
		$top += 8;
		$current++;
	}
	$pdf->line(20, $top, (20+8+25+20+90+20), $top);
}

function paperattendance_readpdf($path, $filename, $course){
	global $DB, $CFG;
	
	$pdf = new Imagick();
	$pdf->setResolution( 100, 100 );
	$pdf->readImage( $path."/".$filename );
	$pdf->setImageType( imagick::IMGTYPE_GRAYSCALE );
	
	$pdftotalpages = $pdf->getNumberImages();
	
	$context = context_course::instance($course);
	$objcourse = new stdClass();
	$objcourse -> id = $course;
	
	$studentlist = paperattendance_students_list($context ->id, $objcourse);
	
	$sessid = paperattendance_get_sessionid($filename);
	
	$countstudent = 1;
	foreach ($studentlist as $student){
			
		if($countstudent == 1){
			$page = new Imagick( $path."/".$filename."[0]" );
			$page->setResolution( 100, 100);
			$page = $page->flattenImages();
			$page->setImageType( imagick::IMGTYPE_GRAYSCALE );
			$page->setImageFormat('png');
//			$page->writeImage('pdf_'.$countstudent.'.png');
			$height = $page->getImageHeight();
			$width = $page->getImageWidth();
			
			$attendancecircle = $page->getImageRegion($width*0.0285, $height*0.022, $width*0.767, $height*(0.18+0.02625*$countstudent));
//			$attendancecircle->writeImage('student_'.$countstudent.'.png');
	
		}else if($countstudent%26 == 0){
			$page->destroy();
			
			$numberpage = ceil($countstudent/26);
			$pagetwo = new Imagick( $path."/".$filename."[".$numberpage."]" );
			$pagetwo->setResolution( 100, 100);
			$pagetwo = $pagetwo->flattenImages();
			$pagetwo->setImageType( imagick::IMGTYPE_GRAYSCALE );
			$pagetwo->setImageFormat('png');
//			$pagetwo->writeImage('pdf_'.$countstudent.'.png');
			$height = $pagetwo->getImageHeight();
			$width = $pagetwo->getImageWidth();
			
			$attendancecircle = $pagetwo->getImageRegion($width*0.0285, $height*0.022, $width*0.767, $height*(0.18+0.02625*$countstudent));
//			$attendancecircle->writeImage('student_'.$countstudent.'.png');	
		}
		else if($countstudent%52 == 0){
			$page->destroy();
				
			$numberpage = ceil($countstudent/26);
			$pagethree = new Imagick( $path."/".$filename."[".$numberpage."]" );
			$pagethree->setResolution( 100, 100);
			$pagethree = $pagethree->flattenImages();
			$pagethree->setImageType( imagick::IMGTYPE_GRAYSCALE );
			$pagethree->setImageFormat('png');
//			$pagethree->writeImage('pdf_'.$countstudent.'.png');
			$height = $pagethree->getImageHeight();
			$width = $pagethree->getImageWidth();
				
			$attendancecircle = $pagethree->getImageRegion($width*0.0285, $height*0.022, $width*0.767, $height*(0.18+0.02625*$countstudent));
			//			$attendancecircle->writeImage('student_'.$countstudent.'.png');
		}
		else if($countstudent%78 == 0){
			$page->destroy();
		
			$numberpage = ceil($countstudent/26);
			$pagefour = new Imagick( $path."/".$filename."[".$numberpage."]" );
			$pagefour->setResolution( 100, 100);
			$pagefour = $pagefour->flattenImages();
			$pagefour->setImageType( imagick::IMGTYPE_GRAYSCALE );
			$pagefour->setImageFormat('png');
//			$pagefour->writeImage('pdf_'.$countstudent.'.png');
			$height = $pagefour->getImageHeight();
			$width = $pagefour->getImageWidth();
		
			$attendancecircle = $pagefour->getImageRegion($width*0.0285, $height*0.022, $width*0.767, $height*(0.18+0.02625*$countstudent));
			//			$attendancecircle->writeImage('student_'.$countstudent.'.png');
		}
		
		$graychannel = $attendancecircle->getImageChannelMean(Imagick::CHANNEL_GRAY);
//		echo "<br>Imagen $countstudent media ".$graychannel["mean"]." desviacion ".$graychannel["standardDeviation"];
		if($graychannel["mean"] < $CFG->paperattendance_greyscale){
			//echo "Alumno".$countstudent ." presente";
			paperattendance_save_student_presence($sessid, $student->id, '1');
		}
		else{
			//echo "Alumno".$countstudent ." ausente";
			paperattendance_save_student_presence($sessid, $student->id, '0');
		}

		$countstudent++;
	}
}

function paperattendance_get_sessionid($pdffile){
	global $DB;
	
	$query = "SELECT sess.id AS id
			FROM {paperattendance_session} AS sess
			WHERE pdf = ? ";
	$resultado = $DB->get_record_sql($query, array($pdffile));
	
	return $resultado -> id;
}

function paperattendance_save_student_presence($sessid, $studentid, $status){
	global $DB;
	
	$sessioninsert = new stdClass();
	$sessioninsert->id = "NULL";
	$sessioninsert->sessionid = $sessid;
	$sessioninsert->userid = $studentid;
	$sessioninsert->status = $status;
	$sessioninsert->lastmodified = time();
	$lastinsertid = $DB->insert_record('paperattendance_presence', $sessioninsert, false);
}


// //returns orientation {straight, rotated, error}
// //pdf = pdfname + extension (.pdf)
function paperattendance_get_orientation($path, $pdf, $page){
	//TODO: la pagina donde se utiliza la funcion debe incluir el require_once
	global $CFG;
	require_once ($CFG->dirroot . '/local/paperattendance/phpdecoder/QrReader.php');

	$pdfexplode = explode(".",$pdf);
	$pdfname = $pdfexplode[0];
	$qrpath = $pdfname.'qr.png';

	//save the pdf page as a png
	$myurl = $pdf.'['.$page.']';
	$image = new Imagick($path.$myurl);
	$image->setResolution(100,100);
	$image->setImageFormat( 'png' );
	$image->writeImage( $path.$pdfname.'.png' );
	$image->clear();

	//check if there's a qr on the top right corner
	$imagick = new Imagick();
	$imagick->setResolution(100,100);
	$imagick->readImage( $path.$pdfname.'.png' );
	$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );

	$height = $imagick->getImageHeight();
	$width = $imagick->getImageWidth();

	$qrtop = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.014);
	$qrtop->writeImage($path."topright".$qrpath);

	// QR
	$qrcodetop = new QrReader($path."topright".$qrpath);
	$texttop = $qrcodetop->text(); //return decoded text from QR Code

	if($texttop == "" || $texttop == " " || empty($texttop)){

		//check if there's a qr on the bottom right corner
		$qrbottom = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.846);
		$qrbottom->writeImage($path."bottomright".$qrpath);

		// QR
		$qrcodebottom = new QrReader($path."bottomright".$qrpath);
		$textbottom = $qrcodebottom->text(); //return decoded text from QR Code

		if($textbottom == "" || $textbottom == " " || empty($textbottom)){

			//check if there's a qr on the top left corner
			$qrtopleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.1225, $height*0.014);
			$qrtopleft->writeImage($path."topleft".$qrpath);

			// QR
			$qrcodetopleft = new QrReader($path."topleft".$qrpath);
			$texttopleft = $qrcodetopleft->text(); //return decoded text from QR Code

			if($texttopleft == "" || $texttopleft == " " || empty($texttopleft)){
					
				//check if there's a qr on the top left corner
				$qrbottomleft = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.1255, $height*0.846);
				$qrbottomleft->writeImage($path."bottomleft".$qrpath);
					
				// QR
				$qrcodebottomleft = new QrReader($path."bottomleft".$qrpath);
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
	$imagick->clear();
}

function paperattendance_get_qr_text($path, $pdf){
	//TODO: la pagina donde se utiliza la funcion debe incluir el require_once
	global $CFG, $DB;
	require_once ($CFG->dirroot . '/local/paperattendance/phpdecoder/QrReader.php');


	$pdfexplode = explode(".",$pdf);
	$pdfname = $pdfexplode[0];
	$qrpath = $pdfname.'qr.png';

	//save the pdf page as a png
	$myurl = $pdf.'[0]';
	$image = new Imagick($path.$myurl);
	$image->setResolution(100,100);
	$image->setImageFormat( 'png' );
	$image->writeImage( $path.$pdfname.'.png' );
	$image->clear();

	//check if there's a qr on the top right corner
	$imagick = new Imagick();
	$imagick->setResolution(100,100);
	$imagick->readImage( $path.$pdfname.'.png' );
	$imagick->setImageType( imagick::IMGTYPE_GRAYSCALE );

	$height = $imagick->getImageHeight();
	$width = $imagick->getImageWidth();

	$qrtop = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.014);
	$qrtop->writeImage($path."topright".$qrpath);

	// QR
	$qrcodetop = new QrReader($path."topright".$qrpath);
	$texttop = $qrcodetop->text(); //return decoded text from QR Code

	if($texttop == "" || $texttop == " " || empty($texttop)){

		//check if there's a qr on the bottom right corner
		$qrbottom = $imagick->getImageRegion($width*0.25, $height*0.14, $width*0.652, $height*0.846);
		$qrbottom->writeImage($path."bottomright".$qrpath);

		// QR
		$qrcodebottom = new QrReader($path."bottomright".$qrpath);
		$textbottom = $qrcodebottom->text(); //return decoded text from QR Code

		if($textbottom == "" || $textbottom == " " || empty($textbottom)){
			return "error";
		}
		else {
			//delete unused png
			unlink($path."bottomright".$qrpath);
			return $textbottom;
		}
	}
	else {
		//delete unused png
		unlink($path."topright".$qrpath);
		return $texttop;
	}
	$imagick->clear();
}


function paperattendance_insert_session($courseid, $requestorid, $userid, $pdffile){
	global $DB;

	$sessioninsert = new stdClass();
	$sessioninsert->id = "NULL";
	$sessioninsert->courseid = $courseid;
	$sessioninsert->teacherid = $requestorid;
	$sessioninsert->uploaderid = $userid;
	$sessioninsert->pdf = $pdffile;
	$sessioninsert->status = 0;
	$sessioninsert->lastmodified = time();
	$sessionid = $DB->insert_record('paperattendance_session', $sessioninsert);
	
	return $sessionid;
}


function paperattendance_insert_session_module($moduleid, $sessionid, $time){
	global $DB;

	$sessionmoduleinsert = new stdClass();
	$sessionmoduleinsert->id = "NULL";
	$sessionmoduleinsert->moduleid = $moduleid;
	$sessionmoduleinsert->sessionid = $sessionid;
	$sessionmoduleinsert->date = $time;
	
	if($DB->insert_record('paperattendance_sessmodule', $sessionmoduleinsert)){
		return true;
	}
	else{
		return false;
	}
}

//returns {perfect, repited}
function paperattendance_check_session_modules($arraymodules, $courseid, $time){
	global $DB;

	$verification = 0;
	$modulesexplode = explode(":",$arraymodules);
	list ( $sqlin, $parametros1 ) = $DB->get_in_or_equal ( $modulesexplode );
	
	$parametros2 = array($courseid, $time);
	$parametros = array_merge($parametros1,$parametros2);
	
	$sessionquery = "SELECT sess.id,
			sessmodule.id
			FROM {paperattendance_session} AS sess
			INNER JOIN {paperattendance_sessmodule} AS sessmodule ON (sessmodule.sessionid = sess.id)
			WHERE sessmodule.moduleid $sqlin AND sess.courseid = ?  AND sessmodule.date = ? ";
	
	$resultado = $DB->get_records_sql ($sessionquery, $parametros );
	if(count($resultado) == 0){
		return "perfect";
	}
	else{
		return "repited";
	}
}

function paperattendance_read_pdf_save_session($path, $pdffile){
	//path must end with "/"
	global $USER;

	$qrtext = paperattendance_get_qr_text($path, $pdffile);
	if($qrtext != "error"){
		//if there's a readable qr

		$qrtextexplode = explode("*",$qrtext);
		$courseid = $qrtextexplode[0];
		$requestorid = $qrtextexplode[1];
		$arraymodules = $qrtextexplode[2];
		$time = $qrtextexplode[3];
		$page = $qrtextexplode[4];

		$verification = paperattendance_check_session_modules($arraymodules, $courseid, $time);
		if($verification == "perfect"){
			$pos = substr_count($arraymodules, ':');
			if ($pos == 0) {
				$module = $arraymodules;
				$sessionid = paperattendance_insert_session($courseid, $requestorid, $USER-> id, $pdffile);
				$verification = paperattendance_insert_session_module($module, $sessionid, $time);
				if($verification == true){
					return "Perfect";
				}
				else{
					return "Error";
				}
			}
			else {
				$modulesexplode = explode(":",$arraymodules);

				for ($i = 0; $i <= $pos; $i++) {
						
					//for each module inside $arraymodules, save records.
					$module = $modulesexplode[$i];

					$sessionid = paperattendance_insert_session($courseid, $requestorid, $USER-> id, $pdffile);
					$verification = paperattendance_insert_session_module($module, $sessionid, $time);
					if($verification == true){
						return "Perfect";
					}
					else{
						return "Error";
					}
				}
			}
		}
		else{
			//couldnt read qr
			$return = "Couldn´t read qr";
			$return += "<br> Orientation is: " .paperattendance_get_orientation($path, $pdffile, "0");
			$return += "<br> Please make sure pdf is straight, without tilt and header on top";
			return $return;
		}
	}
	else{
		return "Session already exists";
	}
}

// //pdf = pdfname + extension (.pdf)
function paperattendance_rotate($path, $pdfname){
	
	//read pdf and rewrite it 
	$pdf = new FPDI();
	// get the page count
	$pageCount = $pdf->setSourceFile($path.$pdfname);
	// iterate through all pages
	for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
		//get page orientation
		$orientation = paperattendance_get_orientation($path, $pdfname,$pageNo-1);
	    // import a page
	    $templateId = $pdf->importPage($pageNo);
	    // get the size of the imported page
	    $size = $pdf->getTemplateSize($templateId);
	
	    // create a page (landscape or portrait depending on the imported page size)
	    if($orientation == "rotated"){
		    if ($size['w'] > $size['h']) {
		        $pdf->AddPage('L', array($size['w'], $size['h']),180);
		    } else {
		        $pdf->AddPage('P', array($size['w'], $size['h']),180);
		    }
	    }
	    else{
	    	if ($size['w'] > $size['h']) {
	    		$pdf->AddPage('L', array($size['w'], $size['h']));
	    	} else {
	    		$pdf->AddPage('P', array($size['w'], $size['h']));
	    	}
	    }
	    // use the imported page
	    $pdf->useTemplate($templateId);
	}
	
	if($pdf->Output($path.$pdfname, "F")){
		return true;
	}else{
		return false;
	}
}

function trim_text($input, $length, $ellipses = true, $strip_html = true) {
	//strip tags, if desired
	if ($strip_html) {
		$input = strip_tags($input);
	}

	//no need to trim, already shorter than trim length
	if (strlen($input) <= $length) {
		return $input;
	}

	//find last space within length
	$last_space = strrpos(substr($input, 0, $length), ' ');
	$trimmed_text = substr($input, 0, $last_space);

	//add ellipses (...)
	if ($ellipses) {
		$trimmed_text .= '...';
	}

	return $trimmed_text;
}

//function for deleting files from moodle data print folder
function paperattendance_recursiveRemoveDirectory($directory)
{
	foreach(glob("{$directory}/*") as $file)
	{
		if(is_dir($file)) {
			paperattendance_recursiveRemoveDirectory($file);
		} else {
			unlink($file);
		}
	}
	
	//this comand delete the folder of the path, in this case we only want to delete the files inside the folder
	//rmdir($directory);
}