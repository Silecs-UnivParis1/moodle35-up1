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
     * Return all courses rattached to the given rofpath.
     * In case of multiple rattachements, only the matching rofpathid is returned.
     *
     * When recursive, only one matching course can be returned (indexed by courseId): the last one!
     * @todo Check unicity/recursive
     *
     * @global moodle_database $DB
     * @param string $rofpath ex. "/02/UP1-PROG39308/UP1-PROG24870"
     * @param boolean $recursive True by default.
     * @return array assoc-array(crsid => [rofpathids...])
     */
    public static function get_courses_from_parent_rofpath($rofpath, $recursive = true) {
        global $DB;
        // 1st step : find the matching courses
        $fieldid = $DB->get_field('custom_info_field', 'id', array('objectname' => 'course', 'shortname' => 'up1rofpathid'), MUST_EXIST);
        $sql = "SELECT objectid AS courseid, data AS rofpathids"
                . " FROM {custom_info_data} "
                . " WHERE objectname='course' AND fieldid=? AND ";
        if ($recursive) {
            $sql .= "data LIKE ?";
            $res = $DB->get_records_sql_menu($sql, array($fieldid, '%' . $rofpath . '%'));
        } else {
            $sql .= " (data LIKE ? OR data LIKE ?)";
            $res = $DB->get_records_sql_menu($sql, array($fieldid, '%' . $rofpath, '%' . $rofpath . ';%' ));
        }
        // 2nd step : filter the results to keep only matching rofpaths
        $rofcourses = array();
        foreach ($res as $crsid => $rofpathids) {
            $rofcourses[$crsid] = array_filter(
                    explode(';', $rofpathids),
                    function ($rowrofpathid) use ($rofpath) { return (strpos($rowrofpathid, $rofpath) !== false); }
            );
        }
        //var_dump($rofcourses);
        return $rofcourses;
    }

    /**
     * Split courses (output of courselist_cattools::get_descendant_courses) as 2 arrays :
     *  rofcourses: courses with a ROF rattachement matching $component, as [courseId => [rofpathids...]]
     * catcourses: courses without this, as [courseId => courseId].
     *
     * @param array $courses array of course ID (from DB)
     * @param string $component '01' to ... '99'
     * @param bool $die die (with exception) on error ; otherwise returns an empty result
     * @return array array($rofcourses, $catcourses)
     */
    public static function split_courses_from_rof($courses, $component, $die=true) {
        $rofcourses = array();
        $catcourses = array();
        if ($component != "00") {
            foreach ($courses as $crsid) {
                $rofpathids = up1_meta_get_text($crsid, 'rofpathid', false);
                if ($rofpathids) {
                    $matchingRofpathids = array_filter(
                            explode(';', $rofpathids),
                            function ($rofpathid) use ($component) {
                                return courselist_roftools::rofpath_match_component($rofpathid, $component);
                            }
                    );
                    if ($matchingRofpathids) {
                        $rofcourses[$crsid] = $matchingRofpathids;
                    } else {
                        echo "\n courses = " ; print_r($courses);
                        echo "\n component = "; print_r($component);
                        echo "\nrofpathids = "; print_r($rofpathids);
                        echo "\nCourseId = $crsid \nComponent = $component \n";
                        $msg = "Incohérence du ROF dans split_courses_from_rof()\n";
                        if ($die) { 
                            throw new Exception($msg);
                        } else {
                            echo "WARNING: $msg \n";
                        }
                    }
                } else { // no rofpathid
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
