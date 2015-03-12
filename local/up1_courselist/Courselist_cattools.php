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


class courselist_cattools {
    /**
     * get component (ex. 05) from categoryid
     * @param int $catid
     * @return string component, ex. "05"
     * @TODO REWRITE THIS CODE after merge with 'extended idnumber'
     */
    public static function get_component_from_category($catid) {
        global $DB;
        $idnumber = $DB->get_field('course_categories', 'idnumber', array('id' => $catid), MUST_EXIST);
        $split = explode('/', $idnumber);
        if (isset($split[2])) {
            return $split[2]; // ex. "4:2012-2013/UP1/04/Licences" -> "04"
        } else {
           return '00';
        }
    }

    /**
     * recherche les rattachements des cours aux catégories (principaux ET secondaires)
     * @param int $catid
     * @return array array(int crsid)
     */
    public static function get_descendant_courses($catid) {
        $r1 = self::get_descendant_courses_from_category($catid);
        $r2 = self::get_descendant_courses_from_catbis($catid, 'up1categoriesbis');
        $r3 = self::get_descendant_courses_from_catbis($catid, 'up1categoriesbisrof');
        return array_unique(array_merge($r1, $r2, $r3));
    }

    /**
     * recherche les rattachements principaux aux catégories (standard moodle)
     * @global moodle_database $DB
     * @param int $catid ; 0 is accepted as a special value for all courses
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_category($catid) {
        global $DB;

        if ($catid === 0) {
            $res = $DB->get_fieldset_select('course', 'id', '');
            return $res;
        } else {
            $sql = "SELECT cco.instanceid FROM {context} cco "
                . "JOIN {context} cca ON (cco.path LIKE CONCAT(cca.path, '/%') ) "
                . "WHERE cca.instanceid=? AND cco.contextlevel=? and cca.contextlevel=? ";
            $res = $DB->get_fieldset_sql($sql, array($catid, CONTEXT_COURSE, CONTEXT_COURSECAT));
            return $res;
        }
    }

    /**
     * recherche les rattachements secondaires des catégories (up1categoriesbis)
     * @global moodle_database $DB
     * @param int $catid
     * @param string $metadatacat 'up1categoriesbis' or 'up1categoriesbisrof'
     * @return array array(int crsid)
     */
    protected static function get_descendant_courses_from_catbis($catid, $metadatacat) {
        global $DB;

        $sql = "SELECT cid.objectid, c2.path FROM {course_categories} c1 "
                . "JOIN {course_categories} c2 ON (c2.path LIKE CONCAT(c1.path, '/%') OR c2.id=c1.id) "
                . "JOIN {custom_info_data} cid ON ((CONCAT(';',data,';') LIKE CONCAT('%;',c2.id,';%'))) "
                . "WHERE c1.id = ? AND cid.fieldid = ? AND objectname='course' ";

        $fieldid = $DB->get_field('custom_info_field', 'id', array('shortname' => $metadatacat));
        $res = $DB->get_fieldset_sql($sql, array($catid, $fieldid));
        return $res;
    }


    /**
     * compute the associative array category path for a given course
     * @param int $crsid
     * @return assoc. array ($catid => $catname)
     */
    public static function get_coursecat_array_from_course($crsid) {
        global $DB;
        $sql = "SELECT cc.path FROM {course_categories} cc "
            . "JOIN {course} c ON (c.category = cc.id) "
            . "WHERE c.id = ?";
        $pathid = $DB->get_field_sql($sql, array($crsid), MUST_EXIST);
        $pathidarray = array_filter(explode('/', $pathid));
        $catpath = array();
        foreach ($pathidarray as $catid) {
            $catpath[$catid] = $DB->get_field('course_categories', 'name', array('id' => $catid) );
        }
        return $catpath;
    }

    /**
     * prepare an html category path for a given course
     * @param int $crsid
     * @return human-readable categories path, with links
     */
    public static function get_coursecat_path_from_course($crsid) {
        $patharray = self::get_coursecat_array_from_course($crsid);
        $out = '';
        foreach ($patharray as $catid => $name) {
            $url = new moodle_url('/course/index.php', array('categoryid' => $catid));
            $out .= " / " . html_writer::link($url, $name);
        }
        return $out;
    }

}
