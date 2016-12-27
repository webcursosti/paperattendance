<?php
/**
 * Strings for component 'paperattendance', language 'en'
 *
 * @package   paperattendance
 */
defined('MOODLE_INTERNAL') || die();

$string['pluginname']="Paper Attendance";
$string['notallowedupload']="Not allowed to upload attendances";
$string['uploadtitle']="PaperAttendance";
$string['uploadsuccessful']="Attendance correctly uploaded";
$string['header']="Upload form";
$string['uploadteacher']="Upload requested by";
$string['uploadrule']="Required field, must be .pdf";
$string['uploadplease']="Please select a pdf on the filepicker";
$string['uploadfilepicker']="Attendance Pdf";
$string['selectteacher']="Select teacher";
$string['viewmodules']="PaperAttendance Modules";
$string['modulestitle']="Modules";
$string['modulename']="Module name";
$string['required']="Required field";
$string['initialtime']="Initial time";
$string['endtime']="End Time";
$string['addmoduletitle']="Add module";
$string['nomodules']="There are no modules";
$string['addmodule']="PaperAttendance Add module";
$string['delete']="Delete module";
$string['doyouwantdeletemodule']="Are you sure you want to delete this module?";
$string['edit']="Edit module";
$string['doyouwanteditmodule']="Are you sure you want to edit this module?";
$string['editmodule']="PaperAttendance Edit module";
$string['editmoduletitle']="Edit Module";
$string['printtitle']="Print attendance list";
$string['printgoback']="Go Back";
$string['downloadprint']="Print list";
$string['selectteacher']="Select Teacher";
$string['requestor']="Requestor";
$string['attdate']="Session date";
$string['modulescheckbox']="Modules";
$string['pleaseselectteacher']="You must select a teacher first";
$string['pleaseselectdate']="Please select a valid date";
$string['pleaseselectmodule']="Please select at least one module";
$string['pdfattendance']="Attendance";
$string['pleaseselectattendance']="Please select an option";
$string['absentattendance']="Absent";
$string['presentattendance']="Present";
$string['hashtag']="#";
$string['student']="Student";
$string['mail']="Mail";
$string['attendance']="Attendance";
$string['setting']="Settings";
$string['nonselectedstudent']="Non selected student";
$string['nonexiststudent']="Non exist student";
$string['date']="Date";
$string['time']="Time";
$string['scan']="Scan";
$string['studentsattendance']="Students Attendance";
$string['see']="See";
$string['seestudents']="See students";
$string['historytitle']="Attendance History";
$string['historyheading']="Attendance History";
$string['nonexistintingrecords']="Non existing records";
$string['back']="Back";
$string['download']="Download";
$string['downloadassistance']="Download assistance";
$string['backtocourse']="Back to course";
$string['edithistory']="Edit";
$string['pdfextensionunrecognized']="Pdf extension not recognized";
$string['courses']="Courses";
$string['sunday']="Sunday";
$string['monday']="Monday";
$string['tuesday']="Tuesday";
$string['wednesday']="Wednesday";
$string['thursday']="Thursday";
$string['friday']="Friday";
$string['saturday']="Saturday";
$string['january']="january";
$string['february']="february";
$string['march']="march";
$string['april']="april";
$string['may']="may";
$string['june']="june";
$string['july']="july";
$string['august']="august";
$string['september']="september";
$string['october']="october";
$string['november']="november";
$string['december']="ecember";
$string['of']=" ";
$string['from']=" ";
$string['error']="ACCESS DENIED - Student not enrolled in course";
$string['couldntsavesession']="Error, the session on the given modules already exists";
$string['couldntreadqrcode']="Couldn´t read QR code, Please make sure it is readable and not scratched.";
$string['omegasync']="Omega";
$string['synchronized']="Synchronized";
$string['unsynchronized']="Unsynchronized";
$string['module']="Module";
$string['nonprocessingattendance']="Attendance non processed yet";


// Settings
$string['settings']="Basic Configuration";
$string['grayscale']="Gray Scale";
$string['grayscaletext']="Maximum value to discern between present or absent, the lower, the darker.";
$string['minuteslate']="Minutes late";
$string['minuteslatetext']="Maximum minutes of delay on printing current module attendance's list";
$string['maxfilesize']="Maximum upload file size";
$string['maxfilesizetext']="Maximum upload file size in bytes";
$string['enrolmethod']="Default enrolment methods";
$string['enrolmethodpro']="The enrolment methods that will be selected when creating a new attendance. For main server is required 'database,meta'.";
$string['token']="Omega's Token";
$string['tokentext']="Omega's Token for its webapi";
$string['omegacreateattendance']="Omega's CreateAttendance Url";
$string['omegacreateattendancetext']="Omega's CreateAttendance Url webapi";
$string['omegaupdateattendance']="Omega's UpdateAttendance Url";
$string['omegaupdateattendancetext']="Omega's UpdateAttendance Url webapi";


// Task
$string['task']="Process PDFs";
$string['taskdelete']="Delete PDFs";

// Capabilities
$string["paperattendance:print"] = "View list";
$string["paperattendance:upload"] = "Upload scaner list";
$string["paperattendance:history"] = "View history attendance";
$string["paperattendance:manageattendance"] = "Setting paper attendance";
$string["paperattendance:modules"] = "Manage modules";
$string["paperattendance:teacherview"] = "Teacher view";