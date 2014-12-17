<?php
/**
 * @package    report
 * @subpackage up1userstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_report_up1userstats_upgrade($oldversion) {
    global $CFG, $DB;

    // $dbman = $DB->get_manager();

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    if ($oldversion < 2014061100) { // index useful for function report_up1userstats_cohorts_criterium()
        $res = $DB->execute("ALTER TABLE {enrol} ADD INDEX ( `customint1` ) ");
    }

    return true;
}
