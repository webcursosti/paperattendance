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
 * This file keeps track of upgrades to the evaluaciones block
 *
 * Sometimes, changes between versions involve alterations to database structures
 * and other major things that may break installations.
 *
 * The upgrade function in this file will attempt to perform all the necessary
 * actions to upgrade your older installation to the current version.
 *
 * If there's something it cannot do itself, it will tell you what you need to do.
 *
 * The commands in here will all be database-neutral, using the methods of
 * database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @since 2.0
 * @package blocks
 * @copyright 2016 Matías Queirolo
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 * @param int $oldversion
 * @param object $block
 */


function xmldb_local_paperattendance_upgrade($oldversion) {
	global $CFG, $DB;

	$dbman = $DB->get_manager();
	
	if ($oldversion < 2016060603) {
	
		// Define table paperattendance_session to be created.
		$table = new xmldb_table('paperattendance_session');
	
		// Adding fields to table paperattendance_session.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('uploaderid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('teacherid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('pdf', XMLDB_TYPE_CHAR, '255', null, null, null, null);
		$table->add_field('status', XMLDB_TYPE_CHAR, '45', null, null, null, null);
		$table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
	
		// Adding keys to table paperattendance_session.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
		$table->add_key('uploaderid', XMLDB_KEY_FOREIGN, array('uploaderid'), 'user', array('id'));
	
		// Conditionally launch create table for paperattendance_session.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016060603, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016060604) {
	
		// Define table paperattendance_presence to be created.
		$table = new xmldb_table('paperattendance_presence');
	
		// Adding fields to table paperattendance_presence.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('sessionid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('status', XMLDB_TYPE_CHAR, '45', null, null, null, null);
		$table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
	
		// Adding keys to table paperattendance_presence.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'paperattendance_session', array('id'));
		$table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
	
		// Conditionally launch create table for paperattendance_presence.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016060604, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016060605) {
	
		// Define table paperattendance_sessmodule to be created.
		$table = new xmldb_table('paperattendance_sessmodule');
	
		// Adding fields to table paperattendance_sessmodule.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('sessionid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('date', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('moduleid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
	
		// Adding keys to table paperattendance_sessmodule.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('sessionid', XMLDB_KEY_FOREIGN, array('sessionid'), 'paperattendance_session', array('id'));
		$table->add_key('moduleid', XMLDB_KEY_FOREIGN, array('moduleid'), 'paperattendance_module', array('id'));
	
		// Conditionally launch create table for paperattendance_sessmodule.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016060605, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016060606) {
	
		// Define table paperattendance_module to be created.
		$table = new xmldb_table('paperattendance_module');
	
		// Adding fields to table paperattendance_module.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('name', XMLDB_TYPE_CHAR, '45', null, null, null, null);
		$table->add_field('initialtime', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
		$table->add_field('endtime', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
	
		// Adding keys to table paperattendance_module.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
	
		// Conditionally launch create table for paperattendance_module.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016060606, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016071103) {
	
		// Changing type of field initialtime on table paperattendance_module to char.
		$table = new xmldb_table('paperattendance_module');
		$field = new xmldb_field('initialtime', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'name');
	
		// Launch change of type for field initialtime.
		$dbman->change_field_type($table, $field);
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016071103, 'local', 'paperattendance');
		
		// Changing type of field endtime on table paperattendance_module to char.
		$table = new xmldb_table('paperattendance_module');
		$field = new xmldb_field('endtime', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'initialtime');
		
		// Launch change of type for field endtime.
		$dbman->change_field_type($table, $field);
		
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016071103, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016071201) {
	
		// Changing type of field status on table paperattendance_presence to int.
		$table = new xmldb_table('paperattendance_presence');
		$field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
	
		// Launch change of type for field status.
		$dbman->change_field_type($table, $field);
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016071201, 'local', 'paperattendance');
		
		// Changing type of field status on table paperattendance_session to int.
		$table = new xmldb_table('paperattendance_session');
		$field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'pdf');
		
		// Launch change of type for field status.
		$dbman->change_field_type($table, $field);
		
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016071201, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016122001) {
	
		// Define field greyscale to be added to paperattendance_presence.
		$table = new xmldb_table('paperattendance_presence');
		$field = new xmldb_field('greyscale', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'lastmodified');
	
		// Conditionally launch add field greyscale.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016122001, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016122002) {
	
		// Rename field greyscale on table paperattendance_presence to grayscale.
		$table = new xmldb_table('paperattendance_presence');
		$field = new xmldb_field('greyscale', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'lastmodified');
	
		// Launch rename field graeyscale.
		$dbman->rename_field($table, $field, 'grayscale');
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016122002, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016122101) {
	
		// Define field omegasync to be added to paperattendance_presence.
		$table = new xmldb_table('paperattendance_presence');
		$field = new xmldb_field('omegasync', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'grayscale');
	
		// Conditionally launch add field omegasync.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016122101, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2016122601) {
	
		// Define field omegaid to be added to paperattendance_presence.
		$table = new xmldb_table('paperattendance_presence');
		$field = new xmldb_field('omegaid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'omegasync');
	
		// Conditionally launch add field omegaid.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2016122601, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2017010502) {
	
		// Define table paperattendance_discussion to be created.
		$table = new xmldb_table('paperattendance_discussion');
	
		// Adding fields to table paperattendance_discussion.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('presenceid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
		$table->add_field('comment', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('result', XMLDB_TYPE_INTEGER, '5', null, null, null, null);
		$table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
	
		// Adding keys to table paperattendance_discussion.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
		$table->add_key('presencekey', XMLDB_KEY_UNIQUE, array('presenceid'));
	
		// Conditionally launch create table for paperattendance_discussion.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2017010502, 'local', 'paperattendance');
	}
	
	if ($oldversion < 2017010601) {
	
		// Define field id to be added to paperattendance_session.
		$table = new xmldb_table('paperattendance_session');
		$field = new xmldb_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
	
		// Conditionally launch add field id.
		if (!$dbman->field_exists($table, $field)) {
			$dbman->add_field($table, $field);
		}
	
		// Paperattendance savepoint reached.
		upgrade_plugin_savepoint(true, 2017010601, 'local', 'paperattendance');
	}
	
	
	return true;
}