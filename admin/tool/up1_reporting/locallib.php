<?php

/**
 * Administrator reporting
 *
 * @package    tool
 * @subpackage up1_reporting
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');

/**
 * prepare table content to be displayed : UFR | course count | student count | teacher count
 * @param int $parentcat parent category id
 * @param bool $ifzero whether we display the row if #courses == 0
 * @return array of array of strings (html) to be displayed by html_writer::table()
 */
function report_base_counts($parentcat, $ifzero=false) {
    global $DB;

    $teachroles = array('editingteacher' => 'Enseignants', 'teacher' => 'Autres intervenants' );
    $componentcats = $DB->get_records_menu('course_categories', array('parent' => $parentcat), '', 'id, name');

    foreach ($componentcats as $catid => $ufrname) {
        $courses = courselist_cattools::get_descendant_courses($catid);
        if ( $ifzero || count($courses) > 0) {
            $result[] = array(
                $ufrname,
                count($courses),
                count_roles_from_courses(array('student' => "Étudiants"), $courses),
                count_roles_from_courses($teachroles, $courses),
            );
        }
    }
    return $result;
}

/**
 * computes subscribed users for several roles and several courses
 * uses context information and get_role_users()
 * @param assoc array $roles ($roleShortName => $frLabel)
 * @param array of int $courses
 * @return int count
 */
function count_roles_from_courses($roles, $courses) {
    global $DB;

    $res = 0;
    foreach ($roles as $role => $rolefr) {
        $dbrole = $DB->get_record('role', array('shortname' => $role));
        foreach ($courses as $courseid) {
            $context = context_course::instance($courseid);
            $roleusers = get_role_users($dbrole->id, $context);
            $res += count($roleusers);
            // $res2 = count_role_users($dbrole->id, $context);
            /** GA @todo why? apparently, this gives a false number (always < $res) */
        }
    }
    return $res;
}

/**
 * Renvoie les catégories de cours de niveau 2
 * @return array
 */
function get_parentcat() {
    global $DB;
    $parentcat = array();
    $sql = "select id, idnumber from {course_categories} where idnumber like '2:%' order by idnumber";
    $period = $DB->get_records_sql_menu($sql);
    foreach ($period as $id => $idnumber) {
        $parentcat[$id] = substr($idnumber, 2);
    }
    return $parentcat;
}
