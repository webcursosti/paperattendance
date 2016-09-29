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
	//greyscale
	$settings->add(
			new admin_setting_configtext(
					'paperattendance_greyscale',
					get_string('greyscale', 'local_paperattendance'),
					get_string('greyscaletext', 'local_paperattendance'),
					'61000',
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
}