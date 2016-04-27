<?php

/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $DB moodle_database */


/**
 * upgrade "memo" storage from (legacy) table log to table up1_courseboard_memo
 * @global type $DB
 */
function upgrade_to_memo() {
    global $DB;

    $sql = "SELECT time, userid, course, info "
            . "FROM {log} l "
            . "WHERE ( module='courseboard' AND action='memo') ";
    $logs = $DB->get_recordset_sql($sql);

    $records = array();
    foreach ($logs as $log) {
        $memo = new stdClass();
        $memo->timecreated = $log->time;
        $memo->userid = $log->userid;
        $memo->courseid = $log->course;
        $memo->message = $log->info;
        $records[] = $memo;
    }
    $logs->close();
    $diag = $DB->insert_records('up1_courseboard_memo', $records);
    return $diag;
}