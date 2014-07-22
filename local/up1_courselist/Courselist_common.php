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


class courselist_common {

    //** @todo validate_pseudopath

    public static function get_courses_from_pseudopath($pseudopath) {

        if ( preg_match('/^\/cat(\d+)$/', $pseudopath, $matches) ) { // limited to a course category
            $cat = (int) $matches[1];  // catid = 0 special value for all courses
            $crs = courselist_cattools::get_descendant_courses($cat);
            if ($crs) {
                $courses = array_combine($crs, $crs); //
            } else {
                $courses = array();
            }
        } else { // at least one ROF item (component)
            $rofpath = strstr(substr($pseudopath, 1), '/'); // drop first component -category- of pseudopath
            $courses = courselist_roftools::get_courses_from_parent_rofpath($rofpath);
        }
        return $courses;
    }

    /**
     * Builds a HTML table listing each course in the pseudopath.
     *
     * @param string $pseudopath
     * @param string $format table|list
     * @return string HTML of the table.
     */
    public static function list_courses_html($pseudopath, $format) {
        $courses = courselist_common::get_courses_from_pseudopath($pseudopath);
        if ($courses) {
            $courseformatter = new courselist_format($format);
            $res = $courseformatter->get_header();
            foreach (courselist_roftools::sort_courses($courses) as $crsid) {
                // $rofpathid = $courses[$crsid];
                $res .= $courseformatter->format_entry($crsid, true) . "\n";
            }
            $res .= $courseformatter->get_footer() . "\n";
        } else { // no course
            $res = "<p>" . get_string('nomatchingcourse', 'local_up1_courselist') . "</p>";
        }
        return $res;
    }

    public static function has_multiple_rattachements($crsid) {
        $catbis = up1_meta_get_text($crsid, 'up1categoriesbis', false);
        if ( ! $catbis == '') {
            return true;
        }
        $rofpathids = up1_meta_get_text($crsid, 'up1rofpathid', false);
        $n = count(explode(';', $rofpathids));
        if ($n > 1) {
            return true ;
        }
        return false;
    }
}
