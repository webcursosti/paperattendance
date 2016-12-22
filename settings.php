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
//Belongs to plugin PaperAttendance

defined('MOODLE_INTERNAL') || die;
if ($hassiteconfig) {

	$settings = new admin_settingpage('local_paperattendance', 'PaperAttendance');

	$ADMIN->add('localplugins', $settings);
	/*
	 $settings->add(new admin_setting_configtext(
	 name for $CFG - example $CFG->appname,
	 Text for field,
	 Description text,
	 Default value,
	 Type value - example PARAM_TEXT
	 ));
	 */

	// Basic Settings
	$settings->add(
			new admin_setting_heading(
					'paperattendance_basicsettings',
					get_string('settings', 'local_paperattendance'),
					''
					)
			);
	// Enrol method
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_enrolmethod',
					get_string('enrolmethod', 'local_paperattendance'),
					get_string('enrolmethodpro', 'local_paperattendance'),
					'manual',
					PARAM_TEXT
			)
	);
	//grayscale
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_grayscale',
					get_string('grayscale', 'local_paperattendance'),
					get_string('grayscaletext', 'local_paperattendance'),
					'63500',
					PARAM_INT
					)
			);
	//minuteslate
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_minuteslate',
					get_string('minuteslate', 'local_paperattendance'),
					get_string('minuteslatetext', 'local_paperattendance'),
					'20',
					PARAM_INT
					)
			);
	//max file size
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_maxfilesize',
					get_string('maxfilesize', 'local_paperattendance'),
					get_string('maxfilesizetext', 'local_paperattendance'),
					'8388608',
					PARAM_INT
			)
	);
	//omega's token
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_omegatoken',
					get_string('token', 'local_paperattendance'),
					get_string('tokentext', 'local_paperattendance'),
					'webisis54521kJusm32ADDddiiIsdksndQoQ01',
					PARAM_TEXT
					)
			);
	//omega's create attendance url
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_omegacreateattendanceurl',
					get_string('omegacreateattendance', 'local_paperattendance'),
					get_string('omegacreateattendancetext', 'local_paperattendance'),
					'http://webapitest.uai.cl/webcursos/createattendance',
					PARAM_TEXT
					)
			);
	//omega's create attendance url
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_omegaupdateattendanceurl',
					get_string('omegaupdateattendance', 'local_paperattendance'),
					get_string('omegaupdateattendancetext', 'local_paperattendance'),
					'http://webapitest.uai.cl/webcursos/createattendance',
					PARAM_TEXT
					)
			);
}