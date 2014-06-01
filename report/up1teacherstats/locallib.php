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
 * This file contains functions used by the outline reports
 *
 * @package    report
 * @subpackage up1teacherstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

/**
 * computes the TOP $limit viewed resources for the target course
 * @param int $crsid course id
 * @param int $limit
 * @return array(array) for the table to display
 */
function teacherstats_resources_top($crsid, $limit) {
    global $DB;
    $res = array();

    $resourcenames = array('book', 'folder', 'label', 'page', 'resource', 'url');
    $resources = get_assoc_resources($resourcenames);
    $sql = "SELECT CONCAT(module, cmid), COUNT(id) AS cnt, module, cmid FROM log "
         . "WHERE course=? AND action like 'view%' AND module IN ('" . implode("','", $resourcenames) . "') "
         . "GROUP BY module, cmid ORDER BY cnt DESC LIMIT " . $limit;
    $logtop = $DB->get_records_sql($sql, array($crsid));
    $cnt = 0;
    foreach ($logtop as $log) {
        $cnt++;
        $res[] = array(
            $cnt,
            get_module_title($log->module, $log->cmid),
            $log->module,
            $log->cnt,
        );
    }
    return $res;
}


/**
 * lists the assignments with statistics, as 2 variants : global an groups
 * @param type $crsid
 * @return array(array(array))
 */
function teacherstats_assignments($crsid) {
    global $DB;
    $res = array('global' => null, 'groups' => null);
    
    // $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, SUM(IF(ass.status = 'submitted', 1, 0)) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
    $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, "
           . "COUNT(DISTINCT ass.id) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
         . "FROM {assign} a "
         . "LEFT JOIN {assign_submission} ass ON (ass.assignment = a.id AND ass.status = 'submitted') "
         . "LEFT JOIN {assign_grades} ag ON (ag.assignment = a.id) "
         . "WHERE a.course = ? GROUP BY a.id";
    $assigns = $DB->get_records_sql($sql, array($crsid));
    foreach($assigns as $assign) {
        $res['global'][] = array(
            $assign->name,
            $assign->due,
            (integer)$assign->cntas,
            (integer)$assign->cntag,
        );
    }

    $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, GROUP_CONCAT(g.name) AS grp, "
           . "COUNT(DISTINCT ass.id) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
         . "FROM {assign} a "
         . "LEFT JOIN {assign_submission} ass ON (ass.assignment = a.id AND ass.status = 'submitted') "
         . "LEFT JOIN {groups} g ON (g.id = ass.groupid)"
         . "LEFT JOIN {assign_grades} ag ON (ag.assignment = a.id) "
         . "WHERE a.course = ? AND ass.groupid > 0  GROUP BY a.id";

    $assigns = $DB->get_records_sql($sql, array($crsid));
    foreach($assigns as $assign) {
        $res['groups'][] = array(
            $assign->name,
            $assign->grp,
            $assign->due,
            (integer)$assign->cntas,
            (integer)$assign->cntag,
        );
    }
    return $res;
}


/**
 * Computes the statistics for selected activities (see $modulenames)
 * WARNING the computing uses the log table only, not the per-module specific tables
 * @param int $crsid
 * @return array(array('string')) table rows and cells
 */
function teacherstats_activities($crsid) {
    global $DB;
    $res = array();
    $modulenames = array('chat', 'data', 'forum', 'glossary', 'wiki');

    foreach ($modulenames as $modulename) {
        $moduletitle[$modulename] = $DB->get_records_menu($modulename, array('course' => $crsid), null, 'id, name');
    }

    $sql = "SELECT l.cmid, l.module, cm.instance, COUNT(DISTINCT l.id) AS edits, COUNT(DISTINCT l.userid) AS users "
         . "FROM {log} l JOIN {course_modules} cm ON  (cm.id = l.cmid) "
         . "WHERE l.module IN ('" . implode("','", $modulenames) . "') "
         . "  AND l.course=? AND ( action like 'add%' OR action IN ('edit', 'talk') ) "
         . "GROUP BY cmid ORDER BY module, cmid";
    $activities = $DB->get_records_sql($sql, array($crsid));
    foreach ($activities as $activity) {
        $res[] = array(
            $moduletitle[$activity->module][$activity->instance],
            $activity->module,
            $activity->edits - 1, // sinon la création est comptée comme contribution
            $activity->users - 1, // idem
        );
    }
    return $res;
}


/**
 * returns an associative array of ($id => $name) for the modules (table module)
 * @global type $DB
 * @param array(string) $resourcenames
 * @return array
 */
function get_assoc_resources($resourcenames) {
    global $DB;
    $sql = "SELECT id, name from {modules} WHERE name IN ('" . implode("','", $resourcenames) .  "')";
    $resources = $DB->get_records_sql_menu($sql);
    return $resources;
}

/**
 * get the module instance name/label for a course_modules id (and modulename)
 * @param string $modulename, which is also the name of the target table
 * @param int $cmid course_modules id
 * @return string
 */
function get_module_title($modulename, $cmid) {
    global $DB;
    $sql = "SELECT name FROM {" . $modulename . "} m "
         . "JOIN {course_modules} cm ON (cm.instance = m.id) "
         . "WHERE cm.id = ?";
    return $DB->get_field_sql($sql, array($cmid), MUST_EXIST);
}