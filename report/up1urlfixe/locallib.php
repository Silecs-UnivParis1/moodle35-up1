<?php
/**
 * Lib functions
 *
 * @package    report
 * @subpackage up1urlfixe
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(dirname(dirname(__DIR__)).'/config.php'); // global moodle config file.


/**
 *
 * @global type $DB
 * @return array(array(string)) : table rows
 */
function report_up1urlfixe_liste() {
    global $DB, $CFG;
    $res = array();
    $sql = "SELECT c.shortname AS crsname, cid.objectid AS crsid, cid.data "
          . "FROM custom_info_data cid "
          . "JOIN custom_info_field cif ON (cid.fieldid = cif.id) "
          . "JOIN course c ON (c.id = cid.objectid AND cid.objectname = :obj) "
          . "WHERE cif.shortname = :field AND cid.data != ''  ORDER BY cid.data ";
    $records = $DB->get_records_sql($sql, ['obj' => 'course', 'field' => 'up1urlfixe']);

    foreach ($records as $record) {
        $urlredirection = $CFG->wwwroot . '/fixe/' . $record->data;
        $urlcourse = new moodle_url('course/view.php', ['id' => $record->crsid]);
        $res[] = array (
            $record->data,
            html_writer::link($urlredirection, $urlredirection),
            html_writer::link($urlcourse, $record->crsname),
        );
    }
    return $res;
}

/**
 *
 * @global type $DB
 * @return array(array(string)) : table rows
 */
function report_up1urlfixe_doublons() {
    global $DB;
    $res = array();
    $sql = "SELECT COUNT(DISTINCT cid.id) AS cnt, GROUP_CONCAT(c.shortname) AS names, GROUP_CONCAT(cid.objectid) AS crsid, cid.data "
          . "FROM custom_info_data cid "
          . "JOIN custom_info_field cif ON (cid.fieldid = cif.id) "
          . "JOIN course c ON (c.id = cid.objectid AND cid.objectname = :obj) "
          . "WHERE cif.shortname = :field "
          . "GROUP BY cid.data HAVING cnt > 1";
    $doublons = $DB->get_records_sql($sql, ['obj' => 'course', 'field' => 'up1urlfixe']);

    foreach ($doublons as $doublon) {
        $res[] = array (
            $doublon->cnt,
            $doublon->data,
            $doublon->names,
            $doublon->crsid,
        );
    }
    return $res;
}


/**
 *
 * @global type $DB
 * @return array(array(string)) : table rows
 */
function report_up1urlfixe_supprimes() {
    global $DB;
    $res = array();
    $sql = "SELECT c.shortname AS name, cid.objectid AS crsid, cid.data "
          . "FROM custom_info_data cid "
          . "JOIN custom_info_field cif ON (cid.fieldid = cif.id) "
          . "LEFT JOIN course c ON (c.id = cid.objectid AND cid.objectname = :obj) "
          . "WHERE cif.shortname = :field AND c.id IS NULL";
    $supprimes = $DB->get_records_sql($sql, ['obj' => 'course', 'field' => 'up1urlfixe']);

    foreach ($supprimes as $supprime) {
        $res[] = array (            
            $supprime->data,
            $supprime->name,
            $supprime->crsid,
        );
    }
    return $res;
}