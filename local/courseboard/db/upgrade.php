<?php
/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/../locallib.php');

function xmldb_local_courseboard_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.8.0 release upgrade line.
	// Put any upgrade step following this.

    if ($oldversion < 2016042701) {
        $table = new xmldb_table('up1_courseboard_memo');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, false, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, false, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', true, XMLDB_NOTNULL, false, '0');
        $table->add_field('message', XMLDB_TYPE_TEXT, 'big', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));

		$dbman->create_table($table);
		upgrade_plugin_savepoint(true, 2016042701, 'local', 'courseboard');
    }

    return true;
}
