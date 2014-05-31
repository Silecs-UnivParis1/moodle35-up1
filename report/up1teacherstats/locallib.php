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