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
 * Capability definitions for the paperattendance module
 * The capabilities are loaded into the database table when the module is
 * installed or updated.
 * Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 * [mod/block]/<plugin_name>:<capabilityname>
 * component_name should be the same as the directory name of the mod or block.
 * Core moodle capabilities are defined thus:
 * moodle/<capabilityclass>:<capabilityname>
 * Examples: mod/forum:viewpost
 * block/recent_activity:view
 * moodle/site:deleteuser
 * The variable name for the capability definitions array is $capabilities
 *
 * @package mod
 * @subpackage paperattendance
 * @copyright 2016 Jorge Cabané <jcabane@alumnos.uai.cl>
 * @copyright 2016 Hans Jeria <hansjeria@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */  
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
		'local/paperattendance:printsecre' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_COURSECAT,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:print' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_COURSE,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:upload' => array(
				'captype' => 'write',
				'contextlevel' => CONTEXT_COURSECAT,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_PROHIBIT,
						'editingteacher' => CAP_PROHIBIT,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:history' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_COURSE,
				'archetypes' => array(
						'student' => CAP_ALLOW,
						'teacher' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:manageattendance' => array(
				'riskbitmask' => RISK_MANAGETRUST,
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:modules' => array(
				'riskbitmask' => RISK_MANAGETRUST,
				'captype' => 'write',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:teacherview' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_SYSTEM,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_ALLOW,
						'editingteacher' => CAP_ALLOW,
						'manager' => CAP_ALLOW
				)),
		'local/paperattendance:printsearch' => array(
				'captype' => 'read',
				'contextlevel' => CONTEXT_COURSECAT,
				'archetypes' => array(
						'student' => CAP_PROHIBIT,
						'teacher' => CAP_PROHIBIT,
						'editingteacher' => CAP_PROHIBIT,
						'manager' => CAP_ALLOW
				))
		
);