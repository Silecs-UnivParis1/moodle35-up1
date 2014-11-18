<?php

/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $PAGE moodle_page */

require_once(dirname(dirname(__DIR__)) . "/config.php");
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");
require_once($CFG->dirroot.'/course/lib.php');


class courselist_roftools {

    /**
     * return all courses rattached to the given rofpath ; only this rofpath in the returned course value
     * @global moodle_database $DB
     * @param string $rofpath ex. "/02/UP1-PROG39308/UP1-PROG24870"
     * @param boolean $recursive
     * @return array assoc-array(crsid => rofpathid) ; in case of multiple rattachements, only the matching rofpathid is returned
     */
    public static function get_courses_from_parent_rofpath($rofpath, $recursive = true) {
        global $DB;
        // 1st step : find the matching courses
        $fieldid = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1rofpathid'), MUST_EXIST);
        $sql = "SELECT objectid, data FROM {custom_info_data} "
                . "WHERE objectname='course' AND fieldid=? AND data RLIKE ?";
        $res = $DB->get_records_sql_menu($sql, array($fieldid, $rofpath . ($recursive ? '' : '(;|$)')));
        //var_dump($res);
        // 2nd step : filter the results to keep only matching rofpaths
        $rofcourses = array();
        foreach ($res as $crsid => $rofpathids) {
            foreach (explode(';', $rofpathids) as $rofpathid) {
                if (strstr($rofpathid, $rofpath)) {
                    $rofcourses[$crsid] = $rofpathid;
                }
            }
        }
        //var_dump($rofcourses);
        return $rofcourses;
    }

    /**
     * split courses as 2 arrays : the ones with a ROF rattachement (rofcourses), and the ones without (catcourses)
     * @param array $courses array of course objects (from DB)
     * @param string $component '01' to ... '99'
     * @return array array($rofcourses, $catcourses)
     */
    public static function split_courses_from_rof($courses, $component) {
        $rofcourses = array();
        $catcourses = array();
        if ($component != "00") {
            foreach ($courses as $crsid) {
                $rofpathids = up1_meta_get_text($crsid, 'rofpathid', false);
                if ($rofpathids) {
                    $arrofpathids = explode(';', $rofpathids);
                    $found = false;
                    foreach ($arrofpathids as $rofpathid) {
                        if (courselist_roftools::rofpath_match_component($rofpathid, $component)) {
                            $found = true;
                            $rofcourses[$crsid] = $rofpathid;
                            break; // exit foreach
                        }
                    }
                    if (!$found) {
                        throw new Exception("Incohérence du ROF dans split_courses_from_rof()");
                        print_r($arrofpathids); die("\nCourseId: $crsid\nComponent: $component");
                    }
                } else {
                    $catcourses[$crsid] = $crsid;
                }
            }
        }
        return array($rofcourses, $catcourses);
    }

    /**
     * return true if $component is the first item of the path
     * @param string $rofpath ex. '/02/UP1-PROG1234'
     * @param string $component ex . '02', between 01 and 99
     */
    public static function rofpath_match_component($rofpath, $component) {
        $pattern = '|^/' . $component . '/|';
        if ( preg_match($pattern, $rofpath) === 1 ) {
            return true;
        }
        return false;
    }


    /**
     * sort courses by annee / semestre / fullname
     * @param array $courses ($crsid => $rofpathid)
     * @return array ($crsid)
     */
    public static function sort_courses($courses) {
        global $DB;

        if (empty($courses)) {
            return array();
        }
        $subquery = up1_meta_gen_sql_query('course', array('up1niveauannee', 'up1semestre'));
        $sql = "SELECT c.id "
            . "FROM {course} AS c JOIN (" . $subquery .") AS s ON (s.id = c.id) "
            . "WHERE c.id IN  ( " . implode(", ", array_keys($courses)) . " ) "
            . "ORDER BY s.up1niveauannee, s.up1semestre, c.fullname ";
        $sortcourses = $DB->get_fieldset_sql($sql);
        return $sortcourses;
    }
}
