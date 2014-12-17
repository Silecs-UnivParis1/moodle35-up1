<?php

/**
 * Administrator reporting
 *
 * @package    report
 * @subpackage up1reporting
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');

/**
 * prepare table content to be displayed : UFR | course count | student count | teacher count
 * @param int $parentcat parent category id
 * @param bool $ifzero whether we display the row if #courses == 0
 * @return array of array of strings (html) to be displayed by html_writer::table()
 */
function report_base_counts($parentcat, $ifzero=true) {
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
            // $roleusers = get_role_users($dbrole->id, $context);
            $res += count_role_users($dbrole->id, $context);
            // $res2 = count_role_users($dbrole->id, $context);
            /** GA @todo why? apparently, count_role_users gives a number always < count(get_roles_users) */
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
    $sql = "SELECT id, idnumber FROM {course_categories} WHERE idnumber LIKE '2:%' ORDER BY idnumber";
    $period = $DB->get_records_sql_menu($sql);
    foreach ($period as $id => $idnumber) {
        $parentcat[$id] = substr($idnumber, 2);
    }
    return $parentcat;
}


// TEST / DEBUG function
function get_activity_all_courses() {
    global $DB;
    $allcourses = $DB->get_fieldset_sql('SELECT id FROM {course} ORDER BY id', array());
    foreach ($allcourses as $course) {
        echo "<br />" . $course . " ";
        print_r(get_inner_activity_stats($course));
    }
}


function get_inner_activity_stats($course) {
    $res = array(
        'module:instances' => get_inner_activity_instances($course, null),
        'forum:instances' => get_inner_activity_instances($course, 'forum'),
        'assign:instances' => get_inner_activity_instances($course, 'assign'),
        'file:instances' => get_inner_activity_instances($course, 'resource'), // 'resource' is the Moodle name for files (local or distant)
        'module:views' => get_inner_activity_views($course, null),
        'forum:views' => get_inner_activity_views($course, 'forum'),
        'assign:views' => get_inner_activity_views($course, 'assign'),
        'file:views' => get_inner_activity_views($course, 'resource'), // 'resource' is the Moodle name for files (local or distant)
        'forum:posts' => get_inner_forum_posts($course),
        'assign:posts' => get_inner_assign_posts($course),
    );
    return $res;
}

function get_inner_activity_instances($course, $module=null) {
    global $DB;
    $sql = "SELECT COUNT(cm.id) FROM {course_modules} cm " .
           ($module === null ? '' : "JOIN {modules} m ON (cm.module=m.id) ") .
           " WHERE course=? " .
           ($module === null ? '' : " AND m.name=?");
    $res = $DB->get_field_sql($sql, array($course, $module), MUST_EXIST);
    return $res;
}

function get_inner_activity_views($course, $module=null) {
    global $DB;
    $sql = "SELECT COUNT(l.id) FROM {log} l " .
           ($module === null ? '' : "JOIN {modules} m ON (l.module=m.name) ") .
           " WHERE course=? AND action LIKE ? " .
           ($module === null ? '' : " AND m.name=?");
    $res = $DB->get_field_sql($sql, array($course, 'view%', $module), MUST_EXIST);
    return $res;
}

function get_inner_forum_posts($course) {
    global $DB;
    $sql = "SELECT COUNT(fp.id) FROM {forum_posts} fp " .
           "JOIN {forum_discussions} fd ON (fp.discussion = fd.id) " .
           "WHERE fd.course = ?";
    return $DB->get_field_sql($sql, array($course), MUST_EXIST);
}

function get_inner_assign_posts($course) {
    global $DB;
    $sql = "SELECT COUNT(asu.id) FROM {assign_submission} asu " .
           "JOIN {assign} a ON (asu.assignment = a.id) " .
           "WHERE a.course = ?";
    return $DB->get_field_sql($sql, array($course), MUST_EXIST);
}
