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
require_once($CFG->dirroot . '/local/coursehybridtree/libcrawler.php');


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


// ***** Tree crawling *****

function statscrawler($maxdepth = 6) {
    $tree = CourseHybridTree::createTree('/cat0');

    $timestamp = time();
    internalcrawler($tree, $maxdepth, 'crawl_stats', array('timestamp' => time()));
}


function crawl_stats($node, $params) {
    echo $node->getAbsoluteDepth() . "  " . $node->getAbsolutePath() . "  "  ;
    $descendantcourses = $node->listDescendantCourses();
    $coursenumbers = array();
    $usercount = array();
    $activitycount = array();

    echo "Compute courses number (total, visible, active)... \n";
    // $coursesnumbers = get_courses_numbers($descendantcourses, $activedays=90);
    // print_r($coursesnumbers) . "\n";
    echo "\n\n";

    echo "Count enrolled users (by role and total)... \n";
    // $usercount = get_usercount_from_courses($descendantcourses);
    // print_r($usercount);
    echo "\n\n";
    
    echo "Count and add inner course activity... \n";
    $activitycount = sum_inner_activity_for_courses($descendantcourses);
    echo "\n\n";

    update_reporting_table($params['timestamp'], $node->getAbsolutePath(), array_merge($coursenumbers, $usercount, $activitycount));
    return true;
}

function update_reporting_table($timestamp, $path, $criteria) {
    global $DB;
    foreach ($criteria as $name => $value) {
        $record = new stdClass();
        $record->object = 'node';
        $record->objectid = $path;
        $record->name = $name;
        $record->value = $value;
        $record->timecreated = $timestamp;
        //** @todo
        $lastinsertid = $DB->insert_record('up1reporting', $record, false);
    }
}


// ************************** Compute enrolled users ******************

function get_usercount_from_courses($courses) {
    global $DB;
    //** @todo more flexible list
    $targetroles = array('editingteacher', 'teacher', 'student');
    $rolemenu = $DB->get_records_menu('role', null, '', 'shortname, id' );
    $total = 0;
    $res = array();

    foreach ($targetroles as $role) {
echo " $role \n";
        $mycount = count_unique_users_from_role_courses($rolemenu[$role], $courses, false);
        $total += $mycount;
        $res['enrolled:' . $role . ':all'] = $mycount;
    }
    $res['enrolled:total:all'] = $total;
    return $res;
}

function count_unique_users_from_role_courses($roleid, $courses, $neverconnected=false) {
    $uniqusers = array();
    foreach ($courses as $courseid) {
echo ".";
        $mergeusers = array_merge($uniqusers, get_users_from_role_course($roleid, $courseid, $neverconnected));
        $uniqusers = array_unique($mergeusers, SORT_NUMERIC);
    }
    return count($uniqusers);
}

function get_users_from_role_course($roleid, $courseid, $neverconnected=false) {
    $context = context_course::instance($courseid);
    $where = '';
    if ($neverconnected) {
        $where = "u.lastlogin = 0";
    }
    $dbusers = get_role_users($roleid, $context, false, 'u.id', null, false, '', '', '', $where);
    $res = array();

    //** @todo optimize with an array_map ? (projection)
    foreach ($dbusers as $user) {
        $res[] = $user->id;
    }
    return $res;
}



// ************************** Compute course numbers ******************

function get_courses_numbers($courses, $activedays=90) {
    global $DB;
    $coursein = '(' . join(', ', $courses) . ')';
    $sql = "SELECT COUNT(id) FROM {course} c " .
            "WHERE id IN $coursein AND c.visible=1 ";
    $sqlactive = "AND (NOW() - c.timemodified) < ?"; // WARNING not exactly the good filter
    //** @todo see backup/util/helper/backup_cron_helper_class.php lines 155-165 : join with log table ? NECESSARY ???

    $res = array(
        'coursenumber:all' => count($courses),
        'coursenumber:visible' => $DB->get_field_sql($sql, array(), MUST_EXIST),
        'coursenumber:active'  => $DB->get_field_sql($sql . $sqlactive, array($activedays * DAYSECS), MUST_EXIST),
    );
    return $res;
}


// ************************** Compute inner statistics (internal to a course) ******************

function sum_inner_activity_for_courses($courses) {

    $res = get_zero_activity_stats();
    $innerstats = get_inner_activity_all_courses();

    foreach ($courses as $courseid) {
        foreach ($innerstats[$courseid] as $name => $value) {
            $res[$name] += $value;
        }
    }
    return $res;
}

// TEST / DEBUG function
function get_activity_all_courses() {

    // var_dump(get_inner_activity_all_courses());
    foreach (get_inner_activity_all_courses() as $course => $values) {
        echo "<br />" . $course . " ";
        print_r($values);
    } 
    
}

function get_inner_activity_all_courses() {
    global $DB;
    $allcourses = $DB->get_fieldset_sql('SELECT id FROM {course} ORDER BY id', array());
    foreach ($allcourses as $course) {
        $res[$course] = get_inner_activity_stats($course);
    }
    return $res;
}

function get_zero_activity_stats() {
    $res = array(
        'module:instances' => 0,
        'forum:instances' => 0,
        'assign:instances' => 0,
        'file:instances' => 0,
        'module:views' => 0,
        'forum:views' => 0,
        'assign:views' => 0,
        'file:views' => 0,
        'forum:posts' => 0,
        'assign:posts' => 0,
    );
    return $res;
}

function get_inner_activity_stats($course) {
    $res = array(
        'module:instances' => count_inner_activity_instances($course, null),
        'forum:instances' => count_inner_activity_instances($course, 'forum'),
        'assign:instances' => count_inner_activity_instances($course, 'assign'),
        'file:instances' => count_inner_activity_instances($course, 'resource'), // 'resource' is the Moodle name for files (local or distant)
        'module:views' => count_inner_activity_views($course, null),
        'forum:views' => count_inner_activity_views($course, 'forum'),
        'assign:views' => count_inner_activity_views($course, 'assign'),
        'file:views' => count_inner_activity_views($course, 'resource'), // 'resource' is the Moodle name for files (local or distant)
        'forum:posts' => count_inner_forum_posts($course),
        'assign:posts' => count_inner_assign_posts($course),
    );
    return $res;
}

function count_inner_activity_instances($course, $module=null) {
    global $DB;
    $sql = "SELECT COUNT(cm.id) FROM {course_modules} cm " .
           ($module === null ? '' : "JOIN {modules} m ON (cm.module=m.id) ") .
           " WHERE course=? " .
           ($module === null ? '' : " AND m.name=?");
    $res = $DB->get_field_sql($sql, array($course, $module), MUST_EXIST);
    return $res;
}

function count_inner_activity_views($course, $module=null) {
    global $DB;
    $sql = "SELECT COUNT(l.id) FROM {log} l " .
           ($module === null ? '' : "JOIN {modules} m ON (l.module=m.name) ") .
           " WHERE course=? AND action LIKE ? " .
           ($module === null ? '' : " AND m.name=?");
    $res = $DB->get_field_sql($sql, array($course, 'view%', $module), MUST_EXIST);
    return $res;
}

function count_inner_forum_posts($course) {
    global $DB;
    $sql = "SELECT COUNT(fp.id) FROM {forum_posts} fp " .
           "JOIN {forum_discussions} fd ON (fp.discussion = fd.id) " .
           "WHERE fd.course = ?";
    return $DB->get_field_sql($sql, array($course), MUST_EXIST);
}

function count_inner_assign_posts($course) {
    global $DB;
    $sql = "SELECT COUNT(asu.id) FROM {assign_submission} asu " .
           "JOIN {assign} a ON (asu.assignment = a.id) " .
           "WHERE a.course = ?";
    return $DB->get_field_sql($sql, array($course), MUST_EXIST);
}
