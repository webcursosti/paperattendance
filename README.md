# paperattendance
Moodle plugin 

------------------------------------------
Paper Attendance activity for Moodle 2.6+
Version: 1.0.0
------------------------------------------

Authors:
* Hans Jeria (hansjeria@gmail.com)
* Jorge Cabané (jcabane@alumnos.uai.cl) 
* Matías Queirolo (mqueirolo@alumnos.uai.cl)
* @copyright  2016 Cristobal Silva (cristobal.isilvap@gmail.com) 
 

Release notes
-------------

1.0.0: First official deploy

NOTE
----

This module was developed starting from Moodle 3.0.1 and it is currently used and
developed in Moodle 3.0.6. It does not use any specific code in 3.0.6 so
it should be compatible to 3.0+, however we have not tested in 3.1+.

Introduction
------------

The paper attendance project began its development in July 2016 to give a definitive solution to the problem of registering attendance for teachers. Nowadays there is only one plugin for Moodle that allows the taking of assists, but its use requires a previous management of Webcourses by the teacher that wants to use it, besides the assistance must be taken from the platform.

Thus, the main idea of paper attendance is that teachers have an effective and simple method so that they can take the attendance in their classes, without having to have much knowledge of the applications of Webcourses or of what this allows them to do, so just mark in the paper course list the students present, deliver the paper to the secretaries who must digitize and upload to the platform, this way, will automatically take the paper assitance to an online registration in both Webcourses and Omega.

Installation
------------

In order to install PaperAttendance, the paperattendance directory in which this
README file is, should be copied to the /local/ directory in your Moodle
installation. Then visit your admin page to install the module.

Also this plugin uses the library included in Moodle that is in the directory mod/assign along with the following libraries that come in the paperattendance project:

- Phpdecoder
- Phpqrcode

However, the following library must be installed in php:

- Imagick

As for scanning and using the scanner, you must install the PaperPort program and use the black and white configuration with a resolution of 600 dpi and sensitivity 30.


Acnkowledgments, suggestions, complaints and bug reporting
----------------------------------------------------------

We'll be happy to get any useful feedback from you. Please feel free to
email us, our name and email address are in the top of this document. 
