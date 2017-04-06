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
* @copyright  2016 Jorge Cabané (jcabane@alumnos.uai.cl)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
require_once (dirname(dirname(dirname(__FILE__))) . "/config.php");
require_once ($CFG->dirroot . "/local/paperattendance/forms/print_form.php");
require_once ($CFG->libdir . '/pdflib.php');
require_once ($CFG->dirroot . '/mod/assign/feedback/editpdf/fpdi/fpdi.php');
require_once ($CFG->dirroot . "/mod/assign/feedback/editpdf/fpdi/fpdi_bridge.php");
//require_once ($CFG->dirroot . "/mod/emarking/lib/openbub/ans_pdf_open.php");
//require_once ($CFG->dirroot . "/mod/emarking/print/locallib.php");
require_once ("locallib.php");
global $DB, $PAGE, $OUTPUT, $USER, $CFG;
require_login();
if (isguestuser()) {
	print_error("ACCESS DENIED");
	die();
}
$courseid = required_param("courseid", PARAM_INT);
$action = optional_param("action", "add", PARAM_TEXT);
$category = optional_param('categoryid', 1, PARAM_INT);

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
$urlprint = new moodle_url("/local/paperattendance/print.php", array(
		"courseid" => $courseid,
		"categoryid" => $category
));
// Page navigation and URL settings.
$pagetitle = get_string('printtitle', 'local_paperattendance');
$PAGE->set_context($context);
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin ( 'ui' );
$PAGE->requires->jquery_plugin ( 'ui-css' );
$PAGE->set_url($urlprint);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($pagetitle);

$course = $DB->get_record("course",array("id" => $courseid));

// Breadcrumb for navigation
$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', array("id" => $courseid)));
$PAGE->navbar->add(get_string('printtitle', 'local_paperattendance'), new moodle_url("/local/paperattendance/print.php", array("courseid" => $courseid)));

if($action == "add"){
	// Add the print form
	$addform = new paperattendance_print_form(null, array("courseid" => $courseid));
	// If the form is cancelled, redirect to course.
	if ($addform->is_cancelled()) {
		$backtocourse = new moodle_url("/course/view.php", array('id' => $courseid));
		redirect($backtocourse);
	}
	else if ($data = $addform->get_data()) {
		// Id teacher
		$requestor = $data->requestor;
		$requestorinfo = $DB->get_record("user", array("id" => $requestor));
		// Date for session
		$sessiondate = $data->sessiondate;
		// Array idmodule => {0 = no checked, 1 = checked}
		$modules = $data->modules;
		// Attendance description
		$description = $data->description;

		$path = $CFG -> dataroot. "/temp/local/paperattendance/";
		//list($path, $filename) = paperattendance_create_qr_image($courseid."*".$requestor."*", $path);

		$uailogopath = $CFG->dirroot . '/local/paperattendance/img/uai.jpeg';
		$webcursospath = $CFG->dirroot . '/local/paperattendance/img/webcursos.jpg';
		$timepdf = time();
		$attendancepdffile = $path . "/print/paperattendance_".$courseid."_".$timepdf.".pdf";

		if (!file_exists($path . "/print/")) {
			mkdir($path . "/print/", 0777, true);
		}

		$pdf = new PDF();
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		// Get student for the list
		$studentinfo = paperattendance_students_list($context->id, $course);

		// We validate the number of students as we are filtering by enrolment.
		// Type after getting the data.
		$numberstudents = count($studentinfo);
		if ($numberstudents == 0) {
			throw new Exception('No students to print');
		}
		// Contruction string for QR encode
		foreach ($modules as $key => $value){
			if($value == 1){
				$schedule = explode("*", $key);
				$arraymodule = $schedule[0];
				$stringqr = $courseid."*".$requestor."*".$arraymodule."*".$sessiondate."*";
				
				paperattendance_draw_student_list($pdf, $uailogopath, $course, $studentinfo, $requestorinfo, $key, $path, $stringqr, $webcursospath, $sessiondate, $description);
				
			}
		}

		// Created new pdf
		$pdf->Output($attendancepdffile, "F");

		$fs = get_file_storage();
		$file_record = array(
				'contextid' => $context->id,
				'component' => 'local_paperattendance',
				'filearea' => 'draft',
				'itemid' => 0,
				'filepath' => '/',
				'filename' => "paperattendance_".$courseid."_".$timepdf.".pdf",
				'timecreated' => time(),
				'timemodified' => time(),
				'userid' => $USER->id,
				'author' => $USER->firstname." ".$USER->lastname,
				'license' => 'allrightsreserved'
		);

		// If the file already exists we delete it
		if ($fs->file_exists($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf")) {
			$previousfile = $fs->get_file($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf");
			$previousfile->delete();
		}
		// Info for the new file
		$fileinfo = $fs->create_file_from_pathname($file_record, $attendancepdffile);

		$action = "download";
	}
}

if($action == "download" && isset($attendancepdffile)){

	$button = html_writer::nonempty_tag(
			"div",
			$OUTPUT->single_button($urlprint, get_string('printgoback', 'local_paperattendance')),
			array("align" => "left"
			));

	$url = moodle_url::make_pluginfile_url($context->id, 'local_paperattendance', 'draft', 0, '/', "paperattendance_".$courseid."_".$timepdf.".pdf");
	$viewerpdf = html_writer::nonempty_tag("embed", " ", array(
			"src" => $url,
			"style" => "height:75vh; width:60vw"
	));
}

echo $OUTPUT->header();

if($action == "add"){

	$PAGE->set_heading($pagetitle);

	echo html_writer::nonempty_tag("h2", $course->shortname." - ".$course->fullname);
	$addform->display();
}

if($action == "download" && isset($attendancepdffile)){

	//echo $OUTPUT->action_icon($url, new pix_icon('i/grades', "download"), null, array("target" => "_blank"));
	echo html_writer::div('<button style="margin-left:1%" type="button" class="btn btn-primary print">'.get_string("downloadprint", "local_paperattendance").'</button>');
	// Back button
	echo $button;
	// Preview PDF
	echo $viewerpdf;
}

echo $OUTPUT->footer();
?>
<script>
$(document).ready(function(){$(".print").on("click",function(){window.open("123").print()});$("input[type='checkbox']").parent().attr("style","border:1px solid #c6cbd1;")});
</script>
<script>
$(document).ready(function(){function f(a,c){if(a.getTime()===c.getTime()){$(".nomodulos").remove();h();k(c,"today");var b=l(),d=0;$(".felement").find("span").each(function(a){d++});b==d&&$(".fgroup").first().append('<div class="nomodulos alert alert-warning">No hay m\u00f3dulos disponibles para la fecha seleccionada.</div>')}a<c&&($(".nomodulos").remove(),h(),k(c,"showall"));a>c&&($(".nomodulos").remove(),m(),$(".fgroup").first().append('<div class="nomodulos alert alert-warning">No hay m\u00f3dulos disponibles para la fecha seleccionada.</div>'))}
function h(){$(".felement").find("span").each(function(a){$(this).show()})}function m(){$("form input:checkbox").prop("checked",!1);$(".felement").find("span").each(function(a){$(this).hide()})}function l(){var a=0;$(".felement").find("span").each(function(c){var b=$(this).text().split(":");c=new Date;c.setHours(b[0]);c.setMinutes(b[1]);c=new Date(c);b=new Date;b.setMinutes(b.getMinutes()- <?php echo ($CFG->paperattendance_minuteslate); ?>);c<b&&($(this).hide(),a++)});return a}function k(a,c){dayofweek=a.getDay();$("form input:checkbox").prop("checked",
!1);$.ajax({type:"POST",url:"ajax/ajaxquerys.php",data:{action:"curlgetmoduloshorario",omegaid:"<?php echo ($course -> idnumber); ?>",diasemana:dayofweek,courseid:<?php echo $courseid; ?>,category:<?php echo $category; ?>},success:function(a){var b=$.parseJSON(a);$.each(b,function(a,e){var d=b[a].horaInicio.split(":");horamodulo=d[0]+":"+d[1];n(horamodulo,c)})}})}function n(a,c){$("form input:checkbox").each(function(b){var d=$(this).parent().text().split(":");b=new Date;b.setHours(d[0]);b.setMinutes(d[1]);b=new Date(b);d=new Date;d.setMinutes(d.getMinutes()- <?php echo ($CFG->paperattendance_minuteslate); ?>);a==$(this).parent().text()&&
$(this).prop("checked",!0);"showall"!=c&&b<d&&($(this).parent().hide(),$(this).prop("checked",!1))})}var g=new Date,e=new Date;selectdate=parseFloat($("#id_sessiondate_day option:selected").val());selectmonth=parseFloat($("#id_sessiondate_month option:selected").val())-1;selectyear=parseFloat($("#id_sessiondate_year option:selected").val());e.setDate(selectdate);e.setMonth(selectmonth);e.setFullYear(selectyear);f(g,e);$("#id_sessiondate_day").change(function(){var a=$("#id_sessiondate_day option:selected").val();
e.setDate(a);f(g,e)});$("#id_sessiondate_month").change(function(){var a=$("#id_sessiondate_month option:selected").val();e.setMonth(a-1);f(g,e)});$("#id_sessiondate_year").change(function(){var a=$("#id_sessiondate_year option:selected").val();e.setFullYear(a);f(g,e)})});
</script>
<script>
$(document).ready(function(){function e(a,c){$("form input:checkbox").each(function(b){b=$(this).parent().text().split(":");var d=b[1];a==b[0]&&c!=d&&($(this).prop("checked",!1),$(this).parent().fadeOut("slow"))})}function f(a,c){$("form input:checkbox").each(function(b){b=$(this).parent().text().split(":");var d=b[1];a==b[0]&&c!=d&&$(this).parent().fadeIn()})}$("form input:checkbox").change(function(){var a=$(this).parent().text().split(":"),c=a[0],a=a[1];$(this).prop("checked")?e(c,a):f(c,a)})});
</script>
