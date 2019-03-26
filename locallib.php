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
 * @copyright  2017 Jorge Cabané (jcabane@alumnos.cl) 
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
	
	$query = "SELECT u.id, 
			u.idnumber, 
			u.firstname, 
			u.lastname, 
			u.email,
			GROUP_CONCAT(e.enrol) AS enrol
			FROM {user_enrolments} ue
			INNER JOIN {enrol} e ON (e.id = ue.enrolid AND e.courseid = ?)
			INNER JOIN {context} c ON (c.contextlevel = ? AND c.instanceid = e.courseid)
			INNER JOIN {role_assignments} ra ON (ra.contextid = c.id AND ra.userid = ue.userid)
			INNER JOIN {user} u ON (ue.userid = u.id)
			INNER JOIN {role} r ON (r.id = ra.roleid)
			WHERE ".$DB->sql_like('r.shortname', '?', $casesensitive = false, $accentsensitive = false, $notlike = false)."
			GROUP BY u.id
			ORDER BY lastname ASC";
	$params = array($course->id, 50, 'student');
	$rs = $DB->get_recordset_sql($query, $params);
	
	return $rs;
}

/**
 * Get the student list
 * 
 * @param int $contextid
 *            Context of the course 
 * @param int $course
 *            Id course
 */
function paperattendance_students_list($contextid, $course){
	global $CFG;
	
	$enrolincludes = explode("," ,$CFG->paperattendance_enrolmethod);
//	$filedir = $CFG->dataroot . "/temp/emarking/$contextid";
//	$userimgdir = $filedir . "/u";
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
	$students->close();
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
function paperattendance_draw_student_list($pdf, $logofilepath, $course, $studentinfo, $requestorinfo, $modules, $qrpath, $qrstring, $webcursospath, $sessiondate, $description, $printid) {
	global $DB, $CFG;
	
	$modulecount = 1;
	// Pages should be added automatically while the list grows.
	$pdf->SetAutoPageBreak(false);
	$pdf->AddPage();
	$pdf->SetFont('Helvetica', '', 8);
	// Top QR
	$qrfilename = paperattendance_create_qr_image($qrstring.$modulecount."*".$description."*".$printid, $qrpath);
	$goodcirlepath = $CFG->dirroot . '/local/paperattendance/img/goodcircle.png';
	$pdf->Image($qrpath."/".$qrfilename, 153, 5, 35);
	// Botton QR, messege to fill the circle and Webcursos Logo
	$pdf->Image($webcursospath, 18, 265, 35);

	$pdf->SetXY(70,264);
	$pdf->Write(1, "Recuerde NO utilizar Lápiz mina ni destacador,");
	$pdf->SetXY(70,268);
	$pdf->Write(1, "de lo contrario la  asistencia no quedará valida.");
	$pdf->SetXY(70,272);
	$pdf->Write(1, "Se recomienda rellenar así");
	//$pdf->Image($goodcirlepath, 107, 272, 5);
	$pdf->Image($goodcirlepath, 107, 272, 5, 5, "PNG", 0);
	
	$pdf->Image($qrpath."/".$qrfilename, 153, 256, 35);
	unlink($qrpath."/".$qrfilename);
	
	// If we have a logo we draw it.
	$left = 20;
	if ($logofilepath) {
		$pdf->Image($logofilepath, $left, 15, 50);
		$left += 55;
	}
	
	// Write the attendance description
	$pdf->SetFont('Helvetica', '', 12);
	$pdf->SetXY(20, 31);
	$descriptionstr = trim_text(paperattendance_returnattendancedescription(false, $description),20);
	$pdf->Write(1, core_text::strtoupper($descriptionstr));
	
	// We position to the right of the logo.
	$top = 7;
	$pdf->SetFont('Helvetica', 'B', 12);
	$pdf->SetXY($left, $top);

	// Write course name.
	$coursetrimmedtext = trim_text($course->shortname,30);
	$top += 6;
	$pdf->SetFont('Helvetica', '', 8);
	$pdf->SetXY($left, $top);
	$pdf->Write(1, core_text::strtoupper(get_string('course') . ': ' . $coursetrimmedtext));
	
	$teachersquery = "SELECT u.id, 
					e.enrol,
					CONCAT(u.firstname, ' ', u.lastname) AS name
					FROM {user} u
					INNER JOIN {user_enrolments} ue ON (ue.userid = u.id)
					INNER JOIN {enrol} e ON (e.id = ue.enrolid)
					INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
					INNER JOIN {context} ct ON (ct.id = ra.contextid)
					INNER JOIN {course} c ON (c.id = ct.instanceid AND e.courseid = c.id)
					INNER JOIN {role} r ON (r.id = ra.roleid)
					WHERE ct.contextlevel = '50' AND r.id = 3 AND c.id = ? AND e.enrol = 'database'
					GROUP BY u.id";

	$teachers = $DB->get_records_sql($teachersquery, array($course->id));
	
	$teachersnames = array();
	foreach($teachers as $teacher) {
		$teachersnames[] = $teacher->name;
	}
	$teacherstring = implode(',', $teachersnames);
	$schedule = explode("*", $modules);
	$stringmodules = $schedule[1]." - ".$schedule[2];
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
	$pdf->Cell(20, 8, core_text::strtoupper(""), 0, 0, 'L');
	$pdf->Cell(90, 8, core_text::strtoupper(get_string('name')), 0, 0, 'L');
	$pdf->Cell(20, 8, core_text::strtoupper(get_string('pdfattendance','local_paperattendance')), 0, 0, 'L');
	$pdf->Ln();
	$top += 8;
	
	$circlepath = $CFG->dirroot . '/local/paperattendance/img/circle.png';
	paperattendance_drawcircles($pdf);
	
	// Write each student.
	$current = 1;
	$pdf->SetFillColor(228, 228, 228);
	$studentlist = array();
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
		
		if($current%26 == 0 && $current != 0 && count($studentinfo) > $current){
			$pdf->AddPage();
			paperattendance_drawcircles($pdf);
			
			$top = 41;
			$modulecount++;
			
			// Write the attendance description
			$pdf->SetFont('Helvetica', '', 12);
			$pdf->SetXY(20, 31);
			$pdf->Write(1, core_text::strtoupper($descriptionstr));
				
			// Logo UAI and Top QR
			$pdf->Image($logofilepath, 20, 15, 50);
			// Top QR
			$qrfilename = paperattendance_create_qr_image($qrstring.$modulecount."*".$description."*".$printid, $qrpath);
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
			$pdf->Write(1, core_text::strtoupper(get_string("date") . ': ' . date("d-m-Y", $sessiondate)));
			// Write modules.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper("Modulos" . ': ' . $modulestrimmedtext));
			// Write number of students.
			$topprovisional += 4;
			$pdf->SetXY($leftprovisional, $topprovisional);
			$pdf->Write(1, core_text::strtoupper(get_string('students') . ': ' . count($studentinfo)));
			// Write the table header.
			$left = 20;
			$topprovisional+= 8;
			$pdf->SetXY($left, $topprovisional);
			$pdf->Cell(8, 8, "N°", 0, 0, 'C');
			$pdf->Cell(25, 8, core_text::strtoupper(get_string('idnumber')), 0, 0, 'L');
			$pdf->Cell(20, 8, core_text::strtoupper(""), 0, 0, 'L');
			$pdf->Cell(90, 8, core_text::strtoupper(get_string('name')), 0, 0, 'L');
			$pdf->Cell(20, 8, core_text::strtoupper(get_string('pdfattendance','local_paperattendance')), 0, 0, 'L');
			$pdf->Ln();
			
			// Botton QR, messege to fill the circle and Logo Webcursos
			$pdf->Image($webcursospath, 18, 265, 35);
			
			$pdf->SetXY(70,264);
			$pdf->Write(1, "Recuerde NO utilizar Lápiz mina ni destacador,");
			$pdf->SetXY(70,268);
			$pdf->Write(1, "de lo contrario la  asistencia no quedará valida.");
			$pdf->SetXY(70,272);
			$pdf->Write(1, "Se recomienda rellenar así");
			$pdf->Image($goodcirlepath, 107, 272, 5, 5, "PNG", 0);
			
			$pdf->Image($qrpath."/".$qrfilename, 153, 256, 35);
			unlink($qrpath."/".$qrfilename);
		}
		
		$student = new stdClass();
		$student->printid = $printid;
		$student->userid = $stlist->id;
		$student->listposition = $current;
		$student->timecreated = time();
		
		$studentlist[] = $student;
		
		$top += 8;
		$current++;
	}
	
	$DB->insert_records('paperattendance_printusers', $studentlist);
	
	$pdf->line(20, $top, (20+8+25+20+90+20), $top);
}

/**
 * Draw the framing for the pdf, so formscanner can detect the inside
 *
 * @param resource the pdf to frame
 */
function paperattendance_drawcircles($pdf){
	
	$w = $pdf -> GetPageWidth();
	$h = $pdf -> GetPageHeight();
	
	$top = 5;
	$left = 5;
	$width = $w - 10;
	$height = $h - 10;
	
	$fillcolor = array(0,0,0);
	$borderstyle = array("all" => "style");
	
	//top left
	$pdf -> Rect($left, $top, 4, 12, 'F', $borderstyle, $fillcolor);
	$pdf -> Rect($left, $top, 12, 4, 'F', $borderstyle, $fillcolor);
	
	//top right
	$pdf -> Rect($left + $width -2, $top, 4, 12, 'F', $borderstyle, $fillcolor);
	$pdf -> Rect($left + $width -2, $top, -8, 4, 'F', $borderstyle, $fillcolor);
	
	//bottom left
	$pdf -> Rect($left, $top + $height -4, 4, -8, 'F', $borderstyle, $fillcolor);
	$pdf -> Rect($left, $top + $height -4, 12, 4, 'F', $borderstyle, $fillcolor);
	
	//bottom right
	$pdf -> Rect($left + $width -2, $top + $height -4, 4, -8, 'F', $borderstyle, $fillcolor);
	$pdf -> Rect($left + $width + 2, $top + $height -4, -12, 4, 'F', $borderstyle, $fillcolor);
	$pdf->SetFillColor(228, 228, 228);

}

/**
 * Unused function to process a pdf 
 *
 * @param varchar $path
 *			Full path of the pdf
 * @param varchar $filename
 * 			Full name of the pdf
 * @param int $course
 * 			Course id
 */
function paperattendance_readpdf($path, $filename, $course){
	global $DB, $CFG;
	
	$return = array();
	$return["result"] = "false";
	$return["synced"] = "false";

	$context = context_course::instance($course);
	$objcourse = new stdClass();
	$objcourse -> id = $course;
	
	$studentlist = paperattendance_get_printed_students($context ->id, $objcourse);	
	$sessid = paperattendance_get_sessionid($filename);
	
	// pre process pdf
	$pdf = new Imagick($path."/".$filename);
	$pdftotalpages = (int)$pdf->getNumberImages();	
	$pdfpages = array();
	
	//$debugpath = $CFG -> dirroot. "/local/paperattendance/test/";
	for($numpage = 0; $numpage < $pdftotalpages; $numpage++){
		$page = new Imagick();
		$page->setResolution( 300, 300);
		$page->readImage($path."/".$filename."[$numpage]");
		if(PHP_MAJOR_VERSION < 7){
			$page = $page->flattenImages(); 
		}else{
			$page->setImageBackgroundColor('white');
			$page->setImageAlphaChannel(11);
			$page->mergeImageLayers(imagick::LAYERMETHOD_FLATTEN);
		}
		$page->setImageType( imagick::IMGTYPE_GRAYSCALE );
		$page->setImageFormat('png');
		//$page->writeImage($debugpath."pdf_$numpage.pdf");
		$pdfpages[] = $page;
	}
	
	$countstudent = 1;
	$numberpage = 0;
	$factor = 0;
	$arrayalumnos = array();
	
	foreach ($studentlist as $student){
		$return["result"] = "true";
		
		// Page size
		$height = $pdfpages[$numberpage]->getImageHeight();
		$width = $pdfpages[$numberpage]->getImageWidth();
		
		if($numberpage == 0){
			$attendancecircle = $pdfpages[$numberpage]->getImageRegion(
					$width * 0.028,
					$height * 0.019,
					$width * 0.773,
					$height * (0.182 + 0.02640 * $factor)
			);
			//$attendancecircle->writeImage($debugpath.'student_'.$countstudent.' * '.$student->name.'.png');
			//echo "<br> Pagina 1: $numberpage estudiante $countstudent ".$student->name;
	
		}else{
			$attendancecircle = $pdfpages[$numberpage]->getImageRegion(
					$width * 0.028,
					$height * 0.0195,
					$width * 0.771,
					$height * (0.160 + 0.02640 * $factor)
			);
			//$attendancecircle->writeImage($debugpath.'student_'.$countstudent.' * '.$student->name.'.png');	
			//echo "<br> Pagina 2: $numberpage estudiante $countstudent ".$student->name;
		}
		
		$line = array();
		$line['emailAlumno'] = paperattendance_getusername($student->id);
		$line['resultado'] = "true";
		
		$graychannel = $attendancecircle->getImageChannelMean(Imagick::CHANNEL_GRAY);
		if($graychannel["mean"] < $CFG->paperattendance_grayscale){
			paperattendance_save_student_presence($sessid, $student->id, '1', $graychannel["mean"]);
			$line['asistencia'] = "true";
		}
		else{
			paperattendance_save_student_presence($sessid, $student->id, '0', $graychannel["mean"]);
			$line['asistencia'] = "false";
		}
		
		$arrayalumnos[] = $line;
		
		// 26 student per each page
		$numberpage = floor($countstudent/26);
		$attendancecircle->destroy();
		
		if($countstudent%26 == 0 && $countstudent != 1){
			$factor = $factor - 26;
		}
		
		$countstudent++;
		$factor++;
	}
	
	if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
		if(paperattendance_omegacreateattendance($course, $arrayalumnos, $sessid)){
			$return["synced"] = "true";
		}
	}
	
	return $return;
}

/**
 * Function get the id of a session given the pdffilename
 *
 * @param varchar $pdffile
 *            Name of the pdf
 */
function paperattendance_get_sessionid($pdffile){
	global $DB;
	
	$query = "SELECT sess.id AS id
			FROM {paperattendance_session} AS sess
			WHERE pdf = ? ";
	$resultado = $DB->get_record_sql($query, array($pdffile));
	
	return $resultado -> id;
}

/**
 * Function to insert a student presence inside a session
 *
 * @param int $sessid
 *            Session id
 * @param int $studentid
 *            Student id
 * @param boolean $status
 *            Presence 1 or 0
 * @param int $grayscale (unused)
 *           Number of grayscale found to debug 
 */
function paperattendance_save_student_presence($sessid, $studentid, $status, $grayscale = NULL){
	global $DB;
	
	$sessioninsert = new stdClass();
	$sessioninsert->sessionid = $sessid;
	$sessioninsert->userid = $studentid;
	$sessioninsert->status = $status;
	$sessioninsert->lastmodified = time();
	$sessioninsert->grayscale = $grayscale;
	$lastinsertid = $DB->insert_record('paperattendance_presence', $sessioninsert, false);
}

/**
 * Function to decrypt a QR code
 *
 * @param int $path
 *            Full path of the pdf file
 * @param int $pdf
 *            Full pdf name
 */
function paperattendance_get_qr_text($path, $pdf){
	global $CFG, $DB;

	$pdfexplode = explode(".",$pdf);
	$pdfname = $pdfexplode[0];
	$qrpath = $pdfname.'qr.png';

	//Cleans up the pdf
	$myurl = $pdf.'[0]';
	$imagick = new Imagick();
	$imagick->setResolution(100,100);
	$imagick->readImage($path.$myurl);
	// hay que probar si es mas util hacerle el flatten aqui arriba o abajo de reduceNoiseImage()
	/*if(PHP_MAJOR_VERSION < 7){
		$imagick->flattenImages();
	}else{
		$imagick->setImageBackgroundColor('white');
		$imagick->setImageAlphaChannel(11);
		$imagick->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
	}*/
//	$imagick->despeckleImage();
	//$imagick->deskewImage(0.5);
//	$imagick->trimImage(2);
//	$imagick->enhanceImage();
	$imagick->setImageFormat( 'png' );
	$imagick->setImageType( Imagick::IMGTYPE_GRAYSCALE );
//	$imagick->normalizeImage($channel  = Imagick::CHANNEL_ALL );
//	$imagick->sharpenimage(0, 1, $channel);

	$height = $imagick->getImageHeight();
	$width = $imagick->getImageWidth();
	
	//$recortey = ($height - 2112)/2;
	//$recortex = ($width- 1272)/2;
	//$hashtime = time();
	//$imagick->writeImage( $path.'originalmihail'.$hashtime.'.png' );
	//$crop = $imagick->getImageRegion(1272, 2112, $recortex, $recortey);
	
	//$crop->writeImage( $path.'cropmihail'.$hashtime.'.png' );
	//$crop->trimImage(2);
	//esta es solamente para debuggiar, despues hay que borrarla por que no sirve
	//$crop->writeImage( $path.'trimmihail'.$hashtime.'.png' );
	//return "error";
	$qrtop = $imagick->getImageRegion(300, 300, $width*0.6, 0);
	//$qrtop->trimImage(2);
	$qrtop->writeImage($path."topright".$qrpath);
	
	// QR
	$qrcodetop = new QrReader($qrtop, QrReader::SOURCE_TYPE_RESOURCE);
	$texttop = $qrcodetop->text(); //return decoded text from QR Code
	unlink($CFG -> dataroot. "/temp/local/paperattendance/unread/topright".$qrpath);
	
	if($texttop == "" || $texttop == " " || empty($texttop)){

		//check if there's a qr on the bottom right corner
		$qrbottom = $imagick->getImageRegion($width*0.14, $height*0.098, $width*0.710, $height*0.866);
		$qrbottom->trimImage(2);
//		$qrbottom->writeImage($path."bottomright".$qrpath);
		
		// QR
		$qrcodebottom = new QrReader($qrbottom, QrReader::SOURCE_TYPE_RESOURCE);
		$textbottom = $qrcodebottom->text(); //return decoded text from QR Code
		$imagick->clear();
//		unlink($CFG -> dataroot. "/temp/local/paperattendance/unread/bottomright".$qrpath);
		if($textbottom == "" || $textbottom == " " || empty($textbottom)){
			return "error";
		}
		else {
			return $textbottom;
		}
	}
	else {
		$imagick->clear();
		return $texttop;
	}
}

/**
 * Function to insert a new session
 *
 * @param int $courseid
 *            Course id
 * @param int $requestorid
 *            Teacher or assistant requestor id
 * @param int $userid
 *            Uploader id
 * @param varchar $pdffile
 *            Full name of the pdf
 * @param int $description
 *            Description of the session
 * @param int $type
 *            Description of the type of assitance
 *            0 -> for paper
 *            1 -> for digital
 */
function paperattendance_insert_session($courseid, $requestorid, $userid, $pdffile, $description, $type){
	global $DB;

	//mtrace("courseid: ".$courseid. " requestorid: ".$requestorid. " userid: ".$userid." pdffile: ".$pdffile. " description: ".$description);
	$sessioninsert = new stdClass();
	$sessioninsert->id = "NULL";
	$sessioninsert->courseid = $courseid;
	$sessioninsert->teacherid = $requestorid;
	$sessioninsert->uploaderid = $userid;
	$sessioninsert->pdf = $pdffile;
	$sessioninsert->status = 0;
	$sessioninsert->lastmodified = time();
	$sessioninsert->description = $description;
	$sessioninsert->type = $type;
	if($sessionid = $DB->insert_record('paperattendance_session', $sessioninsert)){
		//var_dump($sessionid);
	return $sessionid;
	}else{
		mtrace("sessionid fail");
	}
}

/**
 * Function to insert the session module
 *
 * @param int $moduleid
 *            Id of the module
 * @param int $sessionid
 *            Session if
 * @param timestamp $time
 *            Date of the session
 */
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

/**
 * Function to check if the session given the modules and date already exists
 *
 * @param array $arraymodules
 *            Array of the modules of the session
 * @param int $courseid
 *            Course id
 * @param timestamp $time
 *            Date of the session
 */
function paperattendance_check_session_modules($arraymodules, $courseid, $time){
	global $DB;

	$verification = 0;
	$modulesexplode = explode(":",$arraymodules);
	list ( $sqlin, $parametros1 ) = $DB->get_in_or_equal ( $modulesexplode );
	
	$parametros2 = array($courseid, $time);
	$parametros = array_merge($parametros1,$parametros2);
	
	$sessionquery = "SELECT sess.id AS papersessionid,
			sessmodule.id
			FROM {paperattendance_session} AS sess
			INNER JOIN {paperattendance_sessmodule} AS sessmodule ON (sessmodule.sessionid = sess.id)
			WHERE sessmodule.moduleid $sqlin AND sess.courseid = ?  AND sessmodule.date = ? ";
	
	$resultado = $DB->get_records_sql ($sessionquery, $parametros );
	if(count($resultado) == 0){
		return "perfect";
	}
	else{
		if( is_array($resultado) ){
			$resultado = array_values($resultado);
			return $resultado[0]->papersessionid;
		}
		else{
			return $resultado->papersessionid;
		}
	}
}

/**
 * Unused function to read a pdf and save the session
 *
 * @param varchar $path
 *            Input of the desired text to trim
 * @param varchar $pdffile
 *            Full name of the pdf
 * @param varchar $qrtext
 *            Text decripted of the QR
 */
function paperattendance_read_pdf_save_session($path, $pdffile, $qrtext){
	
	//path must end with "/"
	global $USER;

	if($qrtext != "error"){
		//if there's a readable qr

		$qrtextexplode = explode("*",$qrtext);
		$courseid = $qrtextexplode[0];
		$requestorid = $qrtextexplode[1];
		$arraymodules = $qrtextexplode[2];
		$time = $qrtextexplode[3];
		$page = $qrtextexplode[4];
		$description = $qrtextexplode[5];

		$verification = paperattendance_check_session_modules($arraymodules, $courseid, $time);
		if($verification == "perfect"){
			$pos = substr_count($arraymodules, ':');
			if ($pos == 0) {
				$module = $arraymodules;
				$sessionid = paperattendance_insert_session($courseid, $requestorid, $USER-> id, $pdffile, $description, 0);
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

					$sessionid = paperattendance_insert_session($courseid, $requestorid, $USER-> id, $pdffile, $description, 0);
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
			//couldnt save session
			$return = get_string("couldntsavesession", "local_paperattendance");
			return $return;
		}
	}
	else{
			//couldnt read qr
			$return = get_string("couldntreadqrcode", "local_paperattendance");
			return $return;
	}
}

/**
 * Unused function to rotate a pdf if it doesnt come straigth
 *
 * @param varchar $path
 *            Path of the pdf
 * @param varchar $pdfname
 *            Pdf full name
 */
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

/**
 * Function to trim text so it fills between the space in the list
 *
 * @param varchar $input
 *            Input of the desired text to trim
 * @param int $length
 *            Max length
 * @param boolean $ellipses
 *            Ellipse mode
 * @param boolean $strip_html
 *            Strip html mode
 */
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

/**
 * Function to delete all inside of a folder
 *
 * @param varchar $directory
 *            Path of the directory
 */
function paperattendance_recursiveremovedirectory($directory)
{
	foreach(glob("{$directory}/*") as $file)
	{
		if(is_dir($file)) {
			paperattendance_recursiveremovedirectory($file);
		} else {
			unlink($file);
		}
	}
	
	//this comand delete the folder of the path, in this case we only want to delete the files inside the folder
	//rmdir($directory);
}

/**
 * Function to Delete all the pngs inside of a folder
 *
 * @param varchar $directory
 *            Directory path
 */
function paperattendance_recursiveremovepng($directory)
{
	foreach(glob("{$directory}/*.jpg") as $file)
	{
		if(is_dir($file)) {
			paperattendance_recursiveremovepng($file);
		} else {
			unlink($file);
		}
	}
	//this comand delete the folder of the path, in this case we only want to delete the files inside the folder
	//rmdir($directory);
}

/**
 * Function to Delete all the csv inside of a folder
 *
 * @param varchar $directory
 *            Directory path
 */
function paperattendance_recursiveremovecsv($directory)
{
	foreach(glob("{$directory}/*.csv") as $file)
	{
		if(is_dir($file)) {
			paperattendance_recursiveremovecsv($file);
		} else {
			unlink($file);
		}
	}
	//this comand delete the folder of the path, in this case we only want to delete the files inside the folder
	//rmdir($directory);
}

/**
 * Function to convert a date to langs
 *
 * @param timestamp $i
 *            Timestamp of date
 */
function paperattendance_convertdate($i){
	//arrays of days and months
	$days = array(get_string('sunday', 'local_paperattendance'),get_string('monday', 'local_paperattendance'), get_string('tuesday', 'local_paperattendance'), get_string('wednesday', 'local_paperattendance'), get_string('thursday', 'local_paperattendance'), get_string('friday', 'local_paperattendance'), get_string('saturday', 'local_paperattendance'));
	$months = array("",get_string('january', 'local_paperattendance'), get_string('february', 'local_paperattendance'), get_string('march', 'local_paperattendance'), get_string('april', 'local_paperattendance'), get_string('may', 'local_paperattendance'), get_string('june', 'local_paperattendance'), get_string('july', 'local_paperattendance'), get_string('august', 'local_paperattendance'), get_string('september', 'local_paperattendance'), get_string('october', 'local_paperattendance'), get_string('november', 'local_paperattendance'), get_string('december', 'local_paperattendance'));
	
	$dateconverted = $days[date('w',$i)].", ".date('d',$i).get_string('of', 'local_paperattendance').$months[date('n',$i)].get_string('from', 'local_paperattendance').date('Y',$i);
	return $dateconverted;
}

/**
 * Function to get the teacher from a course
 *
 * @param int $courseid
 *            Course id
 * @param int $userid
 *            Id of the Teacher
 */
function paperattendance_getteacherfromcourse($courseid, $userid){
	global $DB;
	$sqlteacher = "SELECT u.id
			FROM {user} AS u
			INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
			INNER JOIN {context} ct ON (ct.id = ra.contextid)
			INNER JOIN {course} c ON (c.id = ct.instanceid AND c.id = ?)
			INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname IN ( ?, ?))
			WHERE u.id = ?";

	$teacher = $DB->get_record_sql($sqlteacher, array($courseid, 'teacher', 'editingteacher', $userid));

	if(!isset($teacher->id)){
		$teacher = $DB->get_record_sql($sqlteacher, array($courseid, 'profesoreditor', 'ayudante', $userid));
	}

	return $teacher;
}

/**
 * Function to get all students of a course
 *
 * @param int $courseid
 *            Course id
 * @param int $userid
 *            Id of the student
 */
function paperattendance_getstudentfromcourse($courseid, $userid){
	global $DB;
	$sqlstudent = "SELECT u.id
			FROM {user} AS u
			INNER JOIN {role_assignments} ra ON (ra.userid = u.id)
			INNER JOIN {context} ct ON (ct.id = ra.contextid)
			INNER JOIN {course} c ON (c.id = ct.instanceid AND c.id = ?)
			INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname = 'student')
			WHERE u.id = ?";

	$student = $DB->get_record_sql($sqlstudent, array($courseid,$userid));

	return $student;
}

/**
 * Function to send a curl to omega to create a session
 *
 * @param int $courseid
 *            Id of a Course
 * @param int $arrayalumnos
 *            Array containinng the user email and its attendance to the session
 * @param int $sessid
 *            Session id
 */
function paperattendance_omegacreateattendance($courseid, $arrayalumnos, $sessid){
	global $DB,$CFG;
	
	if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
		//GET OMEGA COURSE ID FROM WEBCURSOS COURSE ID
		$omegaid = $DB->get_record("course", array("id" => $courseid));
		$omegaid = $omegaid -> idnumber;
		
		//GET FECHA & MODULE FROM SESS ID $fecha, $modulo,
		$sqldatemodule = "SELECT sessmodule.id, FROM_UNIXTIME(sessmodule.date,'%Y-%m-%d') AS sessdate, module.initialtime AS sesstime
						FROM {paperattendance_sessmodule} AS sessmodule
						INNER JOIN {paperattendance_module} AS module ON (sessmodule.moduleid = module.id AND sessmodule.sessionid = ?)";
		$datemodule = $DB->get_record_sql($sqldatemodule, array($sessid));
		//var_dump($datemodule);
		$fecha = $datemodule -> sessdate;
		$modulo = $datemodule -> sesstime;
	    $initialtime = time();
		//CURL CREATE ATTENDANCE OMEGA
		$curl = curl_init();

		$url =  $CFG->paperattendance_omegacreateattendanceurl;
		$token =  $CFG->paperattendance_omegatoken;
		//mtrace("SESSIONID: " .$datemodule->id. "## Formato de fecha: " . $fecha . " Modulo " . $modulo);
		$fields = array (
				"token" => $token,
				"seccionId" => $omegaid,
				"diaSemana" => $fecha,
				"modulos" => array( array("hora" => $modulo) ),
				"alumnos" => $arrayalumnos
		);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$result = curl_exec ($curl);
		curl_close ($curl);
        $executiontime = time() - $initialtime;
        $cron = paperattendance_cronlog($url, $result, time(), $executiontime);
		$alumnos = new stdClass();
		$alumnos = json_decode($result)->alumnos;
		
		$return = false;
		// FOR EACH STUDENT ON THE RESULT, SAVE HIS SYNC WITH OMEGA (true or false)
		for ($i = 0 ; $i < count($alumnos); $i++){
			if($alumnos[$i]->resultado == true){
				$return = true;
				// el estado es 0 por default, asi que solo update en caso de ser verdadero el resultado
					
				// get student id from its username
				$username = $alumnos[$i]->emailAlumno;
				if($studentid = $DB->get_record("user", array("username" => $username))){
					$studentid = $studentid -> id;
						
					$omegasessionid = $alumnos[$i]->asistenciaId;
					//save student sync
					$sqlsyncstate = "UPDATE {paperattendance_presence} SET omegasync = ?, omegaid = ? WHERE sessionid  = ? AND userid = ?";
					$studentid = $DB->execute($sqlsyncstate, array('1', $omegasessionid, $sessid, $studentid));
				}else{
				}
			}
		}
	}
	return $return;
}

/**
 * Function to get a username from its userid
 *
 * @param int $userid
 *            User id
 */
function paperattendance_getusername($userid){
	global $DB;
	$username = $DB->get_record("user", array("id" => $userid));
	$username = $username -> username;
	return $username;
}

/**
 * Function to send a curl to omega to update an attendance
 *
 * @param boolean $update
 *            1 if he attended the session, 0 if not
 * @param int $omegaid
 *            Id omega gives for the students attendance of that session
 */
function paperattendance_omegaupdateattendance($update, $omegaid){
	global $CFG, $DB;
	
	if (paperattendance_checktoken($CFG->paperattendance_omegatoken)){
		//CURL UPDATE ATTENDANCE OMEGA
	
		$url =  $CFG->paperattendance_omegaupdateattendanceurl;
		$token =  $CFG->paperattendance_omegatoken;
	
		if($update == 1){
			$update = "true";
		}
		else{
			$update = "false";
		}
	
		$fields = array (
				"token" => $token,
				"asistenciaId" => $omegaid,
				"asistencia" => $update
		);
	   $initialtime = time();
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($fields));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		$result = curl_exec ($curl);
		curl_close ($curl);
		$executiontime = time() - $initialtime;
		paperattendance_cronlog($url, $result, time(), $executiontime);
	}
}

/**
 * Function to check if the config token exists
 *
 * @param varchar $token
 *            Token to access omega
 */
function paperattendance_checktoken($token){
	if (!isset($token) || empty($token) || $token == "" || $token == null || $token == " ") {
		return false;
	}
	else{
		return true;
	}
}

/**
 * Function to get the count of students synchronized in a session
 *
 * @param int $sessionid
 *            Session id
 */
function paperattendance_getcountstudentssynchronizedbysession($sessionid){
	//Query for the total count of synchronized students
	global $DB;
	$query = 'SELECT
				count(*)
				FROM {paperattendance_session} AS s
				INNER JOIN {paperattendance_presence} AS p ON (s.id = p.sessionid AND p.omegasync = ?)
				WHERE p.sessionid = ?';
	
	$attendancescount = $DB->count_records_sql($query, array(1, $sessionid));
	return $attendancescount;
	
}

/**
 * Function that gets the count of the students in a session
 *
 * @param int $sessionid
 *            Session id
 */
function paperattendance_getcountstudentsbysession($sessionid){
	//Query for the total count of students in a session
	global $DB;
	$query = 'SELECT
				count(*)
				FROM {paperattendance_session} AS s
				INNER JOIN {paperattendance_presence} AS p ON (s.id = p.sessionid)
				WHERE p.sessionid = ?';

	$attendancescount = $DB->count_records_sql($query, array($sessionid));
	return $attendancescount;

}

/**
 * Function to send an email when de session is processed
 *
 * @param int $attendanceid
 *            Session id
  * @param int $courseid
 *            Id of the course in the session given
 * @param int $teacherid
 *            Teacher of the session
 * @param int $uploaderid
 *            The person who uploaded the session
 * @param timestamp $date
 *            Date of the processed session
 * @param varchar $course
 *            Fullname of the course
 * @param varchar $case
 *            For what activity to send an email 
 */
function paperattendance_sendMail($attendanceid, $courseid, $teacherid, $uploaderid, $date, $course, $case, $errorpage) {
	GLOBAL $CFG, $USER, $DB;
	//var_dump($attendanceid); mtrace($courseid); mtrace($teacherid); mtrace($uploaderid); mtrace($date); mtrace($course); mtrace($case); mtrace($errorpage);
	$teacher = $DB->get_record("user", array("id"=> $teacherid));
	$userfrom = core_user::get_noreply_user();
	$userfrom->maildisplay = true;
	$eventdata = new stdClass();
    if ($case == "processpdf" || $case == "nonprocesspdf"){
    	switch($case){
    		case "processpdf":
    			//subject
    			$eventdata->subject = get_string("processconfirmationbodysubject", "local_paperattendance");
    			//process pdf message
    			$messagehtml = "<html>";
    			$messagehtml .= "<p>".get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",</p>";	
    			$messagehtml .= "<p>".get_string("processconfirmationbody", "local_paperattendance") . "</p>";
    			$messagehtml .= "<p>".get_string("datebody", "local_paperattendance") ." ". $date . "</p>";
    			$messagehtml .= "<p>".get_string("course", "local_paperattendance") ." ". $course . "</p>";
    			$messagehtml .= "<p>".get_string("checkyourattendance", "local_paperattendance")." <a href='" . $CFG->wwwroot . "/local/paperattendance/history.php?action=studentsattendance&attendanceid=". $attendanceid ."&courseid=". $courseid ."'>" . get_string('historytitle', 'local_paperattendance') . "</a></p>";
    			$messagehtml .= "</html>";
    			
    			$messagetext = get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",\n";
    			$messagetext .= get_string("processconfirmationbody", "local_paperattendance") . "\n";
    			$messagetext .= get_string("datebody", "local_paperattendance") ." ". $date . "\n";
    			$messagetext .= get_string("course", "local_paperattendance") ." ". $course . "\n";
    			break;
    		case "nonprocesspdf":
    			//subject
    			$eventdata->subject = get_string("nonprocessconfirmationbodysubject", "local_paperattendance");
    			//process pdf message
    			$messagehtml = "<html>";
    			$messagehtml .= "<p>".get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",</p>";
    			$messagehtml .= "<p>".get_string("nonprocessconfirmationbody", "local_paperattendance");
    			foreach ($attendanceid as $pageid){
    				$messagehtml.= " <a href='" . $CFG->wwwroot . "/local/paperattendance/missingpages.php?action=edit&sesspageid=". $pageid->pageid ."'>" .$pageid->pagenumber. "</a>,";
    			}
    			$messagehtml = rtrim($messagehtml, ', ');
    			$messagehtml .= "</p>";
    			$messagehtml .= get_string("grettings", "local_paperattendance"). "</html>";
    
    			$messagetext = get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",\n";
    			//$messagetext .= get_string("nonprocessconfirmationbody", "local_paperattendance") . $errorpage. "\n";
    			$messagetext .= get_string("nonprocessconfirmationbody", "local_paperattendance");
    			foreach ($attendanceid as $pageid){
    				$messagetext.= $pageid->pagenumber.", ";
    			}
    			$messagetext = rtrim($messagetext, ', ');
    			$messagetext.= "\n". get_string("grettings", "local_paperattendance");
    			break;
    	   }
	}
    else{
        //subject
        $eventdata->subject = get_string("newdiscussionsubject", "local_paperattendance");
        //new discussion message
        $messagehtml1 = "<html>";
        $messagehtml1 .= "<p>".get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",</p>";
        //$messagehtml .= "<p>".get_string("newdiscussion", "local_paperattendance") . "</p>";
        $messagehtml2 = "<p>".get_string("sessiondate", "local_paperattendance") ." ". $date . "</p>";
        $messagehtml2 .= "<p>".get_string("coursebody", "local_paperattendance") ." ". $course . "</p>";
        $messagehtml2 .= "<p>".get_string("checkyourattendance", "local_paperattendance")." <a href='" . $CFG->wwwroot . "/local/paperattendance/discussion.php?action=view&courseid=". $courseid ."'>" . get_string('discussiontitle', 'local_paperattendance') . "</a></p>";
        $messagehtml2 .= "</html>";
        
        $messagetext1 = get_string("dear", "local_paperattendance") ." ". $teacher->firstname . " " . $teacher->lastname . ",\n";
        //$messagetext .= get_string("newdiscussion", "local_paperattendance") . "\n";
        $messagetext2 = get_string("sessiondate", "local_paperattendance") ." ". $date . "\n";
        $messagetext2 .= get_string("coursebody", "local_paperattendance") ." ". $course . "\n";
        switch($case){
            case "newdiscussionteacher":
                $messagehtml = $messagehtml1;
                $messagehtml .= "<p>".get_string("newdiscussion", "local_paperattendance") . "</p>";
                $messagehtml = $messagehtml2;
                
                $messagetext = $messagetext1;
                $messagetext .= get_string("newdiscussion", "local_paperattendance") . "\n";
                $messagetext = $messagetext2;
                break;
            case "newdiscussionstudent":
                $messagehtml = $messagehtml1;
                $messagehtml .= "<p>".get_string("newdiscussionstudent", "local_paperattendance") . "</p>";
                $messagehtml = $messagehtml2;
                
                $messagetext = $messagetext1;
                $messagetext .= get_string("newdiscussionstudent", "local_paperattendance") . "\n";
                $messagetext = $messagetext2;
                break;
            case "newresponsestudent":
                $messagehtml = $messagehtml1;
                $messagehtml .= "<p>".get_string("newresponsestudent", "local_paperattendance") . "</p>";
                $messagehtml = $messagehtml2;
                
                $messagetext = $messagetext1;
                $messagetext .= get_string("newresponsestudent", "local_paperattendance") . "\n";
                $messagetext = $messagetext2;
                break;
            case "newresponseteacher":
                $messagehtml = $messagehtml1;
                $messagehtml .= "<p>".get_string("newresponse", "local_paperattendance") . "</p>";
                $messagehtml = $messagehtml2;
                
                $messagetext = $messagetext1;
                $messagetext .= get_string("newresponse", "local_paperattendance") . "\n";
                $messagetext = $messagetext2;
                break;
        }
    }
    $eventdata->component = "local_paperattendance"; // your component name
    $eventdata->name = "paperattendance_notification"; // this is the message name from messages.php
    $eventdata->userfrom = $userfrom;
    //TODO descomentar cuando se suba a producción para que mande mails al profesor.
    if ($case == "nonprocesspdf"){
        $eventdata->userto = $uploaderid;
    }
    else {
    	$eventdata->userto = $teacherid;
    }
    //$eventdata->userto = $uploaderid;
    $eventdata->fullmessage = $messagetext;
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml = $messagehtml;
    $eventdata->smallmessage = get_string("processconfirmationbodysubject", "local_paperattendance");
    $eventdata->notification = 1; // this is only set to 0 for personal messages between users
    message_send($eventdata);
}

/**
 * Function to upload a pdf to moodledata, and check if its a correct attendance list
 *
 * @param resource $file
 *            Resource of the pdf
 * @param varchar $path
 *            Complete path to the pdf
 * @param varchar $filename
 *            Complete name of the pdf 
 * @param int $context
 *            Context of the uploader
 * @param int $contextsystem
 *            Context on the system of the uploader 
 */
function paperattendance_uploadattendances($file, $path, $filename, $context, $contextsystem){
	global $DB, $OUTPUT, $USER;
	$attendancepdffile = $path ."/unread/".$filename;
	$originalfilename = $file->get_filename();
	$file->copy_content_to($attendancepdffile);
	//first check if there's a readable QR code
// 	$qrtext = paperattendance_get_qr_text($path."/unread/", $filename);
// 	if($qrtext == "error"){
// 		//delete the unused pdf
// 		unlink($attendancepdffile);
// 		return $OUTPUT->notification(get_string("filename", "local_paperattendance").$originalfilename."<br>".get_string("couldntreadqrcode", "local_paperattendance"));
// 	}
	//read pdf and rewrite it
	$pdf = new FPDI();
	// get the page count
	$pagecount = $pdf->setSourceFile($attendancepdffile);
	if($pagecount){
		
// 		this is the function to count pages and check if there is any missing page.
// 		$idcourseexplode = explode("*",$qrtext);
// 		$idcourse = $idcourseexplode[0];
		
// 		//now we count the students in course
// 		$course = $DB->get_record("course", array("id" => $idcourse));
// 		$coursecontext = context_coursecat::instance($course->category);
// 		$students = paperattendance_students_list($coursecontext->id, $course);
		
// 		$count = count($students);
// 		$pages = ceil($count/26);
// 		if ($pages != $pagecount){
// 			unlink($attendancepdffile);
// 			return $OUTPUT->notification(get_string("filename", "local_paperattendance").$originalfilename."<br>".get_string("missingpages", "local_paperattendance"));
// 		}
		// iterate through all pages
		
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		
		for ($pageno = 1; $pageno <= $pagecount; $pageno++) {
			// import a page
			$templateid = $pdf->importPage($pageno);
			// get the size of the imported page
			$size = $pdf->getTemplateSize($templateid);
	
			// create a page (landscape or portrait depending on the imported page size)
			if ($size['w'] > $size['h']) {
				$pdf->AddPage('L', array($size['w'], $size['h']));
			} else {
				$pdf->AddPage('P', array($size['w'], $size['h']));
			}
	
			// use the imported page
			$pdf->useTemplate($templateid);
		}
		$pdf->Output($attendancepdffile, "F"); // Se genera el nuevo pdf.
	
		$fs = get_file_storage();
	
		$file_record = array(
				'contextid' => $contextsystem->id,
				'component' => 'local_paperattendance',
				'filearea' => 'draft',
				'itemid' => 0,
				'filepath' => '/',
				'filename' => $filename,
				'timecreated' => time(),
				'timemodified' => time(),
				'userid' => $USER->id,
				'author' => $USER->firstname." ".$USER->lastname,
				'license' => 'allrightsreserved'
		);
	
		// If the file already exists we delete it
		if ($fs->file_exists($contextsystem->id, 'local_paperattendance', 'draft', 0, '/', $filename)) {
			$previousfile = $fs->get_file($context->id, 'local_paperattendance', 'draft', 0, '/', $filename);
			$previousfile->delete();
		}
	
		// Info for the new file
		$fileinfo = $fs->create_file_from_pathname($file_record, $attendancepdffile);
	
		//rotate pages of the pdf if necessary
		//paperattendance_rotate($path."/unread/", "paperattendance_".$courseid."_".$time.".pdf");
	
		//read pdf and save session and sessmodules
//		$pdfprocessed = paperattendance_read_pdf_save_session($path."/unread/", $filename, $qrtext);
		$savepdfquery = new stdClass();
		$savepdfquery->filename= $filename;
		$savepdfquery->lastmodified = time();
		$savepdfquery->uploaderid = $USER->id;
		$DB->insert_record('paperattendance_unprocessed', $savepdfquery);
		
	
		return $OUTPUT->notification(get_string("filename", "local_paperattendance").$originalfilename."<br>".get_string("uploadsuccessful", "local_paperattendance"), "notifysuccess");

	}
	else{
		//delete unused pdf
		unlink($attendancepdffile);
		return $OUTPUT->notification("File name: ".$originalfilename."<br>".get_string("pdfextensionunrecognized", "local_paperattendance"));
	}
}

/**
 * Function to sync unsynced students
 *
 * @param int $courseid
 *            Course of the id reviewed
 * @param int $sessionid
 *            Session id to get the attendance
 */
function paperattendance_synctask($courseid, $sessionid){
	global $DB, $CFG;

	$return = false;

	// Sql that brings the unsynced students
	$sqlstudents = "SELECT p.id, p.userid AS userid, p.status AS status, s.username AS username
	 				FROM {paperattendance_presence} AS p
					INNER JOIN {user} AS s on ( p.userid = s.id AND p.sessionid = ? )";
	
	if($resources = $DB->get_records_sql($sqlstudents, array($sessionid))){
	
		$arrayalumnos = array();
	
		foreach ($resources as $student){
	
			$line = array();
			$line['emailAlumno'] = $student-> username;
			$line['resultado'] = "true";
	
			if($student->status == 1){
				$line['asistencia'] = "true";
			}
			else{
				$line['asistencia'] = "false";
			}
	
			$arrayalumnos[] = $line;
		}
	
		if(paperattendance_omegacreateattendance($courseid, $arrayalumnos, $sessionid)){
			$return = true;
		}
	}
	return $return;
}

/**
 * Function to create the tabs for history
 *
 * @param int $courseid
 *            Int of the course
 */
function paperattendance_history_tabs($courseid) {
	$tabs = array();
	// Create sync
	$tabs[] = new tabobject(
			"attendancelist",
			new moodle_url("/local/paperattendance/history.php", array("courseid"=>$courseid)),
			get_string("historytitle", "local_paperattendance")
			);
	// Records.
	$tabs[] = new tabobject(
			"studentssummary",
			new moodle_url("/local/paperattendance/summary.php", array("courseid"=>$courseid)),
			get_string("summarytitle", "local_paperattendance")
			);
	// Export.
	$tabs[] = new tabobject(
			"export",
			new moodle_url("/local/paperattendance/export.php", array("courseid"=>$courseid)),
			get_string("exporttitle", "local_paperattendance")
			);
	return $tabs;
}

/**
 * Function to return the description given a number or all the list
 *
 * @param boolean $all
 *            True if you want the complete list
 * @param int $descriptionnumber
 *            From 0 to 6, get description
 */
function paperattendance_returnattendancedescription($all, $descriptionnumber=null){
	if(!$all){
	$descriptionsarray = array(get_string('class', 'local_paperattendance'),
			get_string('assistantship', 'local_paperattendance'),
			get_string('extraclass', 'local_paperattendance'),
			get_string('test', 'local_paperattendance'),
			get_string('quiz', 'local_paperattendance'),
			get_string('exam', 'local_paperattendance'),
			get_string('labs', 'local_paperattendance'));
	
	return $descriptionsarray[$descriptionnumber];
	}
	else{
		$descriptionsarray = array(
				array('name'=>'class', 'string'=>get_string('class', 'local_paperattendance')),
				array('name'=>'assistantship', 'string'=>get_string('assistantship', 'local_paperattendance')),
				array('name'=>'extraclass', 'string'=>get_string('extraclass', 'local_paperattendance')),
				array('name'=>'test', 'string'=>get_string('test', 'local_paperattendance')),
				array('name'=>'quiz', 'string'=>get_string('quiz', 'local_paperattendance')),
				array('name'=>'exam', 'string'=>get_string('exam', 'local_paperattendance')),
				array('name'=>'labs', 'string'=>get_string('labs', 'local_paperattendance')));
		
		return $descriptionsarray;
	}
}

/**
 * Function to insert the execution of a task 
 *
 * @param varchar $task
 *            Title of the Task
 * @param varchar $result
 *            Ending result of the Task
 * @param timestamp $timecreated
 *            Time created
 * @param time $executiontime
 *            How much the execution lasted
 */
function paperattendance_cronlog($task, $result = NULL, $timecreated, $executiontime = NULL){
	global $DB;
	$cronlog = new stdClass();
	$cronlog->task = $task;
	$cronlog->result = $result;
	$cronlog->timecreated = $timecreated;
	$cronlog->executiontime = $executiontime;
	$DB->insert_record('paperattendance_cronlog', $cronlog);
	
}

/**
 * Function to export the Attendance's Summary to excel
 *
 * @param varchar $title
 *            Title of the Summary
 * @param array $header
 *            Array containing the header of each row
 * @param varchar $filename
 *            Full name of the excel
 * @param array $data
 *            Array containing the data of each row
 * @param array $description
 *            Array containing the selected descriptions of attendances
 * @param array $dates
 *            Array containing the dates of each session
 * @param array $tabs
 *            Array containing the tabs of the excel
 */
function paperattendance_exporttoexcel($title, $header, $filename, $data, $descriptions, $dates, $tabs){
	global $CFG;
	$workbook = new MoodleExcelWorkbook("-");
	$workbook->send($filename);
	foreach ($tabs as $index=>$tab){
		$attxls = $workbook->add_worksheet($tab);
		$i = 0; //y axis
		$j = 0;//x axis
		$titleformat = $workbook->add_format();
		$titleformat->set_bold(1);
		$titleformat->set_size(12);
		$attxls->write($i,$j,$title,$titleformat);
		$i = 1;
		$j = 3;
		$headerformat = $workbook->add_format();
		$headerformat->set_bold(1);
		$headerformat->set_size(10);
		foreach ($descriptions[$index] as $descr){
			$attxls->write($i, $j, $descr, $headerformat);
			$j++;
		}
		$i = 2;
		$j = 3;
		foreach ($dates[$index] as $date){
			$attxls->write($i, $j, $date, $headerformat);
			$j++;
		}
		$i= 3;
		$j = 0;
		foreach($header[$index] as $cell){
			$attxls->write($i, $j, $cell, $headerformat);
			$j++;
		}
		$i=4;
		$j=0;
		foreach ($data[$index] as $row){
			foreach($row as $cell){
				$attxls->write($i, $j,$cell);
				$i++;
			}
			$j++;
			$i=4;
		}
	}
	$workbook->close();
	exit;
}

/**
 * Processes the CSV, saves session and presences and sync with omega
 *
 * @param resource $file
 *            Csv resource
 * @param varchar $path
 *            Path of the pdf
 * @param varchar $pdffilename
 *            Full name of the pdf
 * @param obj $uploaderobj
 *            Object of the person who uploaded the pdf
 */
function paperattendance_read_csv($file, $path, $pdffilename, $uploaderobj){
	global $DB, $CFG, $USER;

	$omegafailures = array(); //Is not in use
	$fila = 1;
	$return = 0;
	
	$errorpage = null;
	mtrace("linea 1702");
	if (($handle = fopen($file, "r")) !== FALSE) {
		mtrace("linea 1704");
		while(! feof($handle))
  		{
			$data = fgetcsv($handle, 1000, ";");
			$numero = count($data);
			mtrace( $numero." datoss en la línea ".$fila);
			print_r($data);
			$stop = true;
			
			if($fila> 1 && $numero > 26){
				mtrace("linea 1714");
				//$data[27] and $data[28] brings the info of the session
				$qrcodebottom = $data[27];
				$qrcodetop = $data[28];
				if(strpos($qrcodetop, '*') !== false) {
					mtrace("linea 1719");
					$qrcode = $qrcodetop;
				} else {    
					if(strpos($qrcodebottom, '*') !== false) {
						mtrace("linea 1723");
						$qrcode = $qrcodebottom;
					}
					else{
						mtrace("linea 1727");
						$stop = false;
					}
				}
				
				$numpages = paperattendance_number_of_pages($path, $pdffilename);
				if($numpages == 1){
					mtrace("linea 1734");
					$realpagenum = 0;
				}
				else{
					$jpgfilenamecsv = $data[0];
					mtrace("el nombre del jpg recien sacado es: ". $jpgfilenamecsv);
					$oldpdfpagenumber= explode("-",$jpgfilenamecsv);
					$oldpdfpagenumber = $oldpdfpagenumber[1];
					mtrace("el explode es: ".$oldpdfpagenumber);
					$realpagenum = explode(".", $oldpdfpagenumber);
					$realpagenum = $realpagenum[0];
					mtrace("el numero de pagina correspondiente a este pdf es: ".$realpagenum);
				}
				
				if($stop){
					//If stop is not false, it means that we could read one qr
					mtrace("qr correctly found");
					$qrinfo = explode("*",$qrcode);
					//var_dump($qrinfo);
					if(count($qrinfo) == 7){
						//Course id
						$course = $qrinfo[0];
						//Requestor id
						$requestorid = $qrinfo[1];
						//Module id
						$module = $qrinfo[2];
						//Date of the session in unix time
						$time = $qrinfo[3];
						//Number of page
						$page = $qrinfo[4];
						//Description of the session, example : regular
						$description = $qrinfo[5];
						//Print id
						$printid = $qrinfo[6];
							
						$context = context_course::instance($course);
						$objcourse = new stdClass();
						$objcourse -> id = $course;
						$studentlist = paperattendance_get_printed_students($printid);
						//var_dump($studentlist);
						
						$sessdoesntexist = paperattendance_check_session_modules($module, $course, $time);
						mtrace("checkeo de la sesion: ".$sessdoesntexist);
						
						if( $sessdoesntexist == "perfect"){
							mtrace("no existe");
							$sessid = paperattendance_insert_session($course, $requestorid, $uploaderobj->id, $pdffilename, $description, 0);
							mtrace("la session id es : ".$sessid);
							paperattendance_insert_session_module($module, $sessid, $time);
							paperattendance_save_current_pdf_page_to_session($realpagenum, $sessid, $page, $pdffilename, 1, $uploaderobj->id, time());
							
							if($CFG->paperattendance_sendmail == 1){
								$coursename = $DB->get_record("course", array("id"=> $course));
								$moduleobject = $DB->get_record("paperattendance_module", array("id"=> $module));
								$sessdate = date("d-m-Y", $time).", ".$moduleobject->name. ": ". $moduleobject->initialtime. " - " .$moduleobject->endtime;
								paperattendance_sendMail($sessid, $course, $requestorid, $uploaderobj->id, $sessdate, $coursename->fullname, "processpdf", null);
							}
							
						}
						else{
							mtrace("session ya eexiste");
							$sessid = $sessdoesntexist; //if session exist, then $sessdoesntexist contains the session id
							//Check if the page already was processed
							if($DB->record_exists('paperattendance_sessionpages', array('sessionid'=>$sessid,'qrpage'=>$page))){
								mtrace("session ya existe y esta hoja ya fue subida y procesada");
								$return++;
								$stop = false;
							}
							else{
								paperattendance_save_current_pdf_page_to_session($realpagenum, $sessid, $page, $pdffilename, 1, $uploaderobj->id, time());
								mtrace("session ya existe pero esta hoja no habia sido subida ni procesada");
								$stop = true;
							}
						}
						
						if($stop){
							$arrayalumnos = array();
							$init = ($page-1)*26+1;
							$end = $page*26;
							$count = 1; //start at one because init starts at one
							$csvcol = 1;
							foreach ($studentlist as $student){
								if($count>=$init && $count<=$end){
									$line = array();
									$line['emailAlumno'] = paperattendance_getusername($student->id);
									$line['resultado'] = "true";
									$line['asistencia'] = "false";
							
									if($data[$csvcol] == 'A'){
										paperattendance_save_student_presence($sessid, $student->id, '1', NULL);
										$line['asistencia'] = "true";
									}
									else{
										paperattendance_save_student_presence($sessid, $student->id, '0', NULL);
									}
							
									$arrayalumnos[] = $line;
									$csvcol++;
								}
								$count++;
							}
							
							$omegasync = false;
							if(paperattendance_checktoken($CFG->paperattendance_omegatoken)){
								if(paperattendance_omegacreateattendance($course, $arrayalumnos, $sessid)){
									$omegasync = true;
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
				  		$return++;	
					}else{
						mtrace("Error: can't process this page, no readable qr code");
						//$return = false;//send email or something to let know this page had problems
						$sessionpageid = paperattendance_save_current_pdf_page_to_session($realpagenum, null, null, $pdffilename, 0, $uploaderobj->id, time());
						
						if($CFG->paperattendance_sendmail == 1){
							$errorpage = new StdClass();
							$errorpage->pagenumber = $realpagenum+1;
							$errorpage->pageid = $sessionpageid;
						}
						$return++;
					}
				}
	  		else{

	  			mtrace("Error: can't process this page, no readable qr code");
	  			//$return = false;//send email or something to let know this page had problems
	  			$sessionpageid = paperattendance_save_current_pdf_page_to_session($realpagenum, null, null, $pdffilename, 0, $uploaderobj->id, time());
	  			
	  			if($CFG->paperattendance_sendmail == 1){
	  				$errorpage = new StdClass();
	  				$errorpage->pagenumber = $realpagenum+1;
	  				$errorpage->pageid = $sessionpageid;
	  			}
				$return++;
	  			}
			}
			$fila++;
  		}
		fclose($handle);
	}
	
	mtrace("linea 1887");
	$returnarray = array();
	$returnarray[] = $return;
	$returnarray[] = $errorpage;
	unlink($file);
	return $returnarray;
}

/**
 * Inserts the current page of the pdf and session to the database, so its reconstructed later
 *
 * @param int $pagenum
 *            Page number of the pdf
 * @param int $sessid
 *            Session id of the current session
 */
function paperattendance_save_current_pdf_page_to_session($pagenum, $sessid, $qrpage, $pdfname, $processed, $uploaderid, $timecreated){
	global $DB;
	
	$pagesession = new stdClass();
	$pagesession->sessionid = $sessid;
	$pagesession->pagenum = $pagenum;	
	$pagesession->qrpage = $qrpage;
	$pagesession->pdfname = $pdfname;
	$pagesession->processed = $processed;
	$pagesession->uploaderid = $uploaderid;
	$pagesession->timecreated = $timecreated;
	$idsessionpage = $DB->insert_record('paperattendance_sessionpages', $pagesession, true);
	return $idsessionpage;
}


/**
 * Counts the number of pages of a pdf
 *
 * @param varchar $path
 *            Path of the pdf
 * @param varchar $pdffilename
 *            Fullname of the pdf, including extension
 */
function paperattendance_number_of_pages($path, $pdffilename){
	// initiate FPDI
	$pdf = new FPDI();
	// get the page count
	$num = $pdf->setSourceFile($path."/".$pdffilename);
	$pdf->close();
	unset($pdf);
	return $num;
}

/**
 * Transforms pdf found into jpgs, runs shell exec to call formscanner, calls readcsv
 *
 * @param varchar $path
 *            Path of the pdf
 * @param varchar $filename
 *            Fullname of the pdf, including extension 
 * @param obj $uploaderobj
 *            Object of the person who uploaded the pdf
 */
function paperattendance_runcsvproccessing($path, $filename, $uploaderobj){
	global $CFG;
	
	$pagesWithErrors = array();
	
	// convert pdf to jpg
	$pdf = new Imagick();

	$pdf->setResolution( 300, 300);
	$pdf->readImage($path."/".$filename);
	$pdf->setImageFormat('jpeg');
	$pdf->setImageCompression(imagick::COMPRESSION_JPEG);
	$pdf->setImageCompressionQuality(100);

	if ($pdf->getImageAlphaChannel()) {
		
		// Remove alpha channel
		$pdf->setImageAlphaChannel(11);
		
		// Set image background color
		$pdf->setImageBackgroundColor('white');
		
		// Merge layers
		$pdf->mergeImageLayers(imagick::LAYERMETHOD_FLATTEN);
	}
	
	if (!file_exists($path."/jpgs")) {
		mkdir($path."/jpgs", 0777, true);
	}
	//Remove initial pngs in the directory
	paperattendance_recursiveremovepng($path."/jpgs");
	
	$pdfname = explode(".",$filename);
	$pdfname = $pdfname[0];
	
	$pdf->writeImages($path."/jpgs/".$pdfname.".jpg", false);
	$pdf->clear();
	unset($pdf);
	
	if (!file_exists($path."/jpgs/processing")) {
		mkdir($path."/jpgs/processing", 0777, true);
	}
	//Remove initial pngs in the directory
	paperattendance_recursiveremovepng($path."/jpgs/processing");
	//Remove initial csv in the directory
	paperattendance_recursiveremovecsv($path."/jpgs/processing");
	//process jpgs one by one and then delete it
	$countprocessed = 0;
	foreach(glob("{$path}/jpgs/*.jpg") as $file)
	{
		//first move it to the processing folder
		$jpgname = basename($file);
		mtrace("el nombre del jpg recien sacado es: ". $jpgname);
		rename($file, $path."/jpgs/processing/".$jpgname);
		
		//now run the exec command
		//$command = 'timeout 30 java -jar /Datos/formscanner/formscanner-1.1.3-bin/lib/formscanner-main-1.1.3.jar /Datos/formscanner/template.xtmpl /data/data/moodledata/temp/local/paperattendance/unread/jpgs/processing/';	
		$command = "timeout 30 java -jar ".$CFG->paperattendance_formscannerjarlocation." ".$CFG->paperattendance_formscannertemplatelocation." ".$CFG->paperattendance_formscannerfolderlocation;
		
		$lastline = exec($command, $output, $return_var);
		
		//return_var es el que devuelve 124 si es que se alcanza el timeout
		if($return_var != 124){
			mtrace("no se alcanzó el timeout, todo bien");
			mtrace($return_var);
			print_r(scandir($path."/jpgs/processing/"));
			print_r(scandir($path."/jpgs/"));
			//revisar el csv que creó formscanner
			foreach(glob("{$path}/jpgs/processing/*.csv") as $filecsv)
			{
				$arraypaperattendance_read_csv = array();
				$arraypaperattendance_read_csv = paperattendance_read_csv($filecsv, $path, $filename, $uploaderobj);
				mtrace("paperattendance_runcsvproccessing linea 2017: ".$arraypaperattendance_read_csv);
				$processed = $arraypaperattendance_read_csv[0];
				mtrace("paperattendance_runcsvproccessing linea 2019: ".$processed);
				if ($arraypaperattendance_read_csv[1] != null){
					$pagesWithErrors[$arraypaperattendance_read_csv[1]->pagenumber] = $arraypaperattendance_read_csv[1];
					mtrace("linea 2022");
				}
				$countprocessed += $processed;
			}
		}
		else{
			//meaning that the timeout was reached, save that page with status unprocessed
			mtrace("si se alcanzó el timeout, todo mal");
			$numpages = paperattendance_number_of_pages($path, $filename);
			
			if($numpages == 1){
				$realpagenum = 0;
			}
			else{
				$oldpdfpagenumber= explode("-",$jpgname);
				$oldpdfpagenumber = $oldpdfpagenumber[1];
				$realpagenum = explode(".", $oldpdfpagenumber);
				$realpagenum = $oldpdfpagenumber[0];
			}
			
			$sessionpageid = paperattendance_save_current_pdf_page_to_session($realpagenum, null, null, $filename, 0, $uploaderobj->id, time());
			
			if($CFG->paperattendance_sendmail == 1){
				/*
				paperattendance_sendMail($sessionpageid, null, $uploaderobj->id, $uploaderobj->id, null, $filename, "nonprocesspdf", $realpagenum);
				$admins = get_admins();
				foreach ($admins as $admin){
					paperattendance_sendMail($sessionpageid, null, $admin->id, $admin->id, null, $pdffilename, "nonprocesspdf", $realpagenum+1);
				}*/
				$errorpage = new stdClass();
				$errorpage->pageid = $sessionpageid;
				$errorpage->pagenumber = $realpagenum + 1;
				$pagesWithErrors[$errorpage->pagenumber] = $errorpage;
			}
			
			$countprocessed++;
		}
		
		//finally unlink the jpg file
		unlink($path."/jpgs/processing/".$jpgname);
	}
	if (count($pagesWithErrors) > 0){
		if (count($pagesWithErrors) > 1){
			ksort($pagesWithErrors);
		}
		paperattendance_sendMail($pagesWithErrors, null, $uploaderobj->id, $uploaderobj->id, null, "NotNull", "nonprocesspdf", null);
		$admins = get_admins();
		foreach ($admins as $admin){
			paperattendance_sendMail($pagesWithErrors, null, $admin->id, $admin->id, null, "NotNull", "nonprocesspdf", null);
		}
		mtrace("end pages with errors var dump");
	}
	
	if($countprocessed>= 1){
		return true;
	}
	else{
		return false;
	}
}
/**
 * Save in a new table in db the the session printed
 *
 * @param int $courseid
 *            Id course
 * @param int $module
 *            Id module
 * @param int $sessiondate
 * 			  Date of the session
 * @param int $requestor
 * 			  Id requestor
 *            
 */
function paperattendance_print_save($courseid, $module, $sessiondate, $requestor){
	global $DB, $CFG;
	
	$print = new stdClass();
	$print->courseid = $courseid;
	$print->module = $module;
	$print->sessiondate = $sessiondate;
	$print->requestor = $requestor;
	$print->timecreated = time();
	
	return $DB->insert_record('paperattendance_print',$print);
}
/**
 * Get the students printed
 *
 * @param int $printid
 *            Id print

 */
function paperattendance_get_printed_students($printid){
	global $DB;
	
	$query = "SELECT u.id, u.lastname, u.firstname, u.idnumber FROM {paperattendance_print} AS pp
				INNER JOIN {paperattendance_printusers} AS ppu ON (pp.id = ppu.printid AND pp.id = ?)
				INNER JOIN {user} AS u ON (ppu.userid = u.id)";
	
	$students = $DB->get_records_sql($query,array($printid));
	
	$studentinfo = array();
	// Fill studentnames with student info (name, idnumber, id and picture).
	foreach($students as $student) {
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

function paperattendance_get_printed_students_missingpages($moduleid,$courseid,$date){
	global $DB;

	$query = "SELECT u.id, u.lastname, u.firstname, u.idnumber FROM {paperattendance_print} AS pp
				INNER JOIN {paperattendance_printusers} AS ppu ON (pp.id = ppu.printid AND pp.courseid = ? AND pp.module = ? AND pp.sessiondate = ? )
				INNER JOIN {user} AS u ON (ppu.userid = u.id)";

	$students = $DB->get_records_sql($query,array($courseid,$moduleid,$date));

	$studentinfo = array();
	// Fill studentnames with student info (name, idnumber, id and picture).
	foreach($students as $student) {
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
