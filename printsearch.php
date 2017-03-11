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
* @copyright  2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2016 Matías Queirolo (mqueirolo@alumnos.uai.cl)
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
//Pertenece al plugin PaperAttendance
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/paperattendance/locallib.php');

global $DB, $OUTPUT, $USER;
// User must be logged in.
require_login();
if (isguestuser()) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
	die();
}
$courseid = optional_param('courseid',1, PARAM_INT);
$categoryid = optional_param('categoryid', 1, PARAM_INT);
$action = optional_param('action', 'viewform', PARAM_TEXT);
//Page
$page = optional_param('page', 0, PARAM_INT);
$perpage = 26;

if($courseid > 1){
	if($course = $DB->get_record("course", array("id" => $courseid))){
		$context = context_coursecat::instance($course->category);
		$path = $course->category;
	}
}else if($categoryid > 1){
	$context = context_coursecat::instance($categoryid);
	$path = $categoryid;
}else{
	$context = context_system::instance();
}

$contextsystem = context_system::instance();

if (! has_capability('local/paperattendance:printsearch', $context) && ! has_capability('local/paperattendance:printsearch', $contextsystem)) {
	print_error(get_string('notallowedprint', 'local_paperattendance'));
}
// This page url.
$url = new moodle_url('/local/paperattendance/printsearch.php', array(
		'courseid' => $courseid,
		"categoryid" => $categoryid
));
if($courseid && $courseid != 1){
	$courseurl = new moodle_url('/course/view.php', array(
			'id' => $courseid,
			"categoryid" => $categoryid
	));
	$PAGE->navbar->add($course->fullname, $courseurl );
}
$PAGE->navbar->add(get_string('printtitle', 'local_paperattendance'));
$PAGE->navbar->add(get_string('printtitle', 'local_paperattendance'),$url);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
// Require jquery for modal.
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
//string for print
$print = get_string("downloadprint", "local_paperattendance");
// Creating tables and adding columns header.
$table = new html_table();
$table->head = array(get_string('hashtag', 'local_paperattendance'),
		get_string('course', 'local_paperattendance'),
		get_string('teacher', 'local_paperattendance'),
		get_string('category', 'local_paperattendance')
);
$table->id = "fbody";
$sqlcourses = "SELECT c.id,
			c.fullname,
			cat.name,
			CONCAT( u.firstname, ' ', u.lastname) as teacher
			FROM {role} r
			INNER JOIN {role_assignments} ra ON (r.id = ra.roleid AND r.id IN ( 3, 4))
			INNER JOIN {context} ct ON (ct.id = ra.contextid)
			INNER JOIN {course} c ON (c.id = ct.instanceid)
			INNER JOIN {course_categories} as cat ON (cat.id = c.category)
			INNER JOIN {user} u ON (ra.userid = u.id)
			WHERE (cat.path like ?)
			GROUP BY c.id";
$ncourses = count($DB->get_records_sql($sqlcourses, array("%/".$path,"%")));
$courses = $DB->get_records_sql($sqlcourses, array("%/".$path,"%"),$page*$perpage, $perpage);
$coursecount = $page*$perpage+1;
foreach($courses as $course){
	$printurl = new moodle_url('/local/paperattendance/print.php', array(
			'courseid' => $course->id,
			"categoryid" => $path
	));
	$table->data[] = array(
			$coursecount,
			$course->fullname,
			$course->teacher,
			$course->name,
			html_writer::nonempty_tag("a", $print, array("href"=>$printurl))
	);
	$coursecount++;
}
echo $OUTPUT->header();
echo html_writer::div(get_string("searchprinthelp","local_paperattendance"),"alert alert-info", array("role"=>"alert"));
echo html_writer::empty_tag("input", array( "id"=>"filter", "type"=>"text", "style"=>"width:25%"));
if ($ncourses>0){
	if ($ncourses>30){
		$ncourses = 30;
	}
	echo html_writer::table($table);
	echo $OUTPUT->paging_bar($ncourses, $page, $perpage, $url);
}
echo $OUTPUT->footer();

?>
<script type="text/javascript">
	var filter = $('#filter');
	var $table = $("#fbody").find("tbody");
	var $paging = $(".paging");
	filter.keyup(function(event){
		if(this.value.length >= 3 ){
			$table.find("tr").not(".ajaxtr").hide();
			$paging.hide();
		    var data = this.value;
		    var path = <?php echo $path;?>;
		    var print = <?php echo json_encode($print);?>;
		    var courseid = <?php echo $courseid; ?>;
			var categoryid = <?php echo $categoryid; ?>;
		    callAjax(data, path, print, courseid, categoryid);
		}
		else{
			$table.find("tr").not(".ajaxtr").show();
			$paging.show();
		}
		$(".ajaxtr").remove();
	});
	function callAjax(data, path, print, courseid, categoryid) {
		var count = 1;
		$.getJSON("ajax/ajaxquerys.php?result="+data+"&path="+path+"&courseid="+courseid+"&category="+categoryid+"&action=getcourses", function(result){
			$(".ajaxtr").remove();
	        $.each(result, function(i, field){
	        	var printicon = "<a href='print.php?courseid="+field['id']+"&categoryid="+path+"'>"+print+"</a>"; 
	        	$table.append("<tr class='ajaxtr'><td>"+count+"</td><td>"+field['fullname']+"</td><td>"+field['teacher']+"</td><td>"+field['name']+"</td><td>"+printicon+"</td></tr>");
				count++;
	        });
    	});
	}

</script>
