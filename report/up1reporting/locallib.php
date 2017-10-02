<?php

/**
 * Administrator reporting
 *
 * @package    report
 * @subpackage up1reporting
 * @copyright  2013-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');
require_once($CFG->dirroot . '/local/coursehybridtree/libcrawler.php');

global  $ReportingTimestamp, $CourseInnerStats;

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



// ***** Log statistics *****

function cli_meta_statistics() {
    echo "Number of statpoints = " . up1reporting_count_timestamps() . "\n\n";

    echo "Details:\n";
    $timestats = up1reporting_last_records(1000);
    echo "Timestamp            Criters Nodes Records\n";
    foreach ($timestats as $stats) {
        printf("%s  %6d %6d %6d \n", $stats->timestamp, $stats->crit, $stats->nodes, $stats->records);
    }
}

function up1reporting_count_timestamps() {
    global $DB;

    return $DB->count_records_sql("SELECT COUNT(DISTINCT timecreated) FROM {report_up1reporting}");
}

function up1reporting_last_records($howmany) {
    global $DB;

    // $lastrecord = $DB->get_field_sql("SELECT LAST(timecreated) FROM {report_up1reporting}");
    $sql = "SELECT FROM_UNIXTIME(timecreated) AS timestamp, "
         . "COUNT(DISTINCT name) AS crit, COUNT(DISTINCT objectid) AS nodes, COUNT(id) as records "
         . "FROM {report_up1reporting} GROUP BY timecreated ORDER BY timecreated DESC LIMIT " . $howmany;

    $logs = $DB->get_records_sql($sql);
    return array_values($logs);
}


// ***** Tree crawling *****
/**
 * This is the main function to start crawling the hybridtree and compute statistics
 * @global type $ReportingTimestamp
 * @param type $rootnode
 * @param type $maxdepth
 */
function statscrawler($rootnode, $maxdepth = 6, $verb) {
    global $ReportingTimestamp, $CourseInnerStats;

    if ($rootnode) {
        $tree = CourseHybridTree::createTree($rootnode);
    } else  {
        $tree = CourseHybridTree::createTree('/cat0');
    }
    $ReportingTimestamp = time();
    $enable = array('countcourses'=>true, 'enrolled'=>true, 'activities'=>true);
    $crawlparams = array('enable' => $enable, 'verb' => $verb);

    if ($enable['activities']) {
        // computes one time only the global course activity statistics
        $CourseInnerStats = get_inner_activity_all_courses();
    }
    internalcrawler($tree, $maxdepth, 'crawl_stats', $crawlparams);
}

/**
 * This function is called by internalcrawler (coursehybridtree:libcrawler) on each node
 * to compute the related statistics
 * @param object $node
 * @param array $extraparams : array('verb'=>int, 'enable'=>array())
 * @return boolean ALWAYS TRUE
 */
function crawl_stats($node, $extraparams) {
    $verb = $extraparams['verb'];
    $enable = $extraparams['enable'];
    $nodepath = $node->getAbsolutePath();
    progressBar($verb, 1, "\n" . $node->getAbsoluteDepth() . "  " . $nodepath . "  ");
    $starttime = microtime(true);
    $descendantcourses = $node->listDescendantCourses();
    $coursesnumbers = array();
    $usercount = array();
    $activitycount = array();

    if ($enable['countcourses']) {
        progressBar($verb, 2, "\nCompute courses number (total, visible, active)... \n");
        $coursesnumbers = get_courses_numbers($descendantcourses, $activedays=90);
    }

    if ($enable['enrolled']) {
        progressBar($verb, 2, "Count enrolled users (by role and total)... \n");
        $usercount = get_usercount_from_courses($descendantcourses, $verb);
    }

    if ($enable['activities']) {
        progressBar($verb, 2, "Count and add inner course activity... \n");
        $activitycount = sum_inner_activity_for_courses($descendantcourses);
    }

    progressBar($verb, 3, "\n" . (string)(microtime(true) - $starttime) . " s.\n");

    update_reporting_table($nodepath, array_merge($coursesnumbers, $usercount, $activitycount));
    return true;
}

/**
 * this function is called on each node, to insert the records in the database
 * one new row for each member of the $criteria array
 * @global type $ReportingTimestamp
 * @param string $path eg. /cat10/cat11/cat12/cat14/01:UP1-PROG39571/UP1-PROG33876
 * @param array $criteria($name => $value)
 * @return boolean true if all records inserted, false otherwise
 */
function update_reporting_table($path, $criteria) {
    global $DB, $ReportingTimestamp;
    $diag = true;
    foreach ($criteria as $name => $value) {
        $record = new stdClass();
        $record->object = 'node';
        $record->objectid = $path;
        $record->name = $name;
        $record->value = $value;
        $record->timecreated = $ReportingTimestamp;
        $lastinsertid = $DB->insert_record('report_up1reporting', $record, false);
        if ( ! $lastinsertid) {
            $diag = false;
            echo "Error inserting " . print_r($record, true);
        }
    }
    return $diag;
}


// ************************** Compute enrolled users ******************

function get_usercount_from_courses($courses, $verb) {
    global $DB;
    //** @todo more flexible roles list ?
    $targetroles = array('editingteacher', 'teacher', 'student');
    $rolemenu = $DB->get_records_menu('role', null, '', 'shortname, id' );
    $res = array();

    $total = 0;
    progressbar($verb, 2, "  all ");
    foreach ($targetroles as $role) {
        progressbar($verb, 2, "  $role ");
        $mycount = count_unique_users_from_role_courses($rolemenu[$role], $courses, false, $verb);
        $total += $mycount;
        $res['enrolled:' . $role . ':all'] = $mycount;
    }
    $res['enrolled:total:all'] = $total;

    $total = 0;
    progressbar($verb, 2, "  neverconnected ");
    foreach ($targetroles as $role) {
        progressbar($verb, 2, "  $role ");
        $mycount = count_unique_users_from_role_courses($rolemenu[$role], $courses, true, $verb);
        $total += $mycount;
        $res['enrolled:' . $role . ':neverconnected'] = $mycount;
    }
    $res['enrolled:total:neverconnected'] = $total;

    return $res;
}

function count_unique_users_from_role_courses($roleid, $courses, $neverconnected=false, $verb) {
    $uniqusers = array();
    $progressmark = ($verb >=2 ? '.' : '');
    foreach ($courses as $courseid) {
        echo $progressmark;
        foreach (get_users_from_role_course($roleid, $courseid, $neverconnected) as $userid) {
            $uniqusers[$userid] = true;
        }
    }
    return count($uniqusers);
}

function get_users_from_role_course($roleid, $courseid, $neverconnected=false) {
    $context = context_course::instance($courseid);
    $where = '';
    if ($neverconnected) {
        $where = "u.lastlogin = 0";
    }
    $dbusers = get_role_users($roleid, $context, false, 'u.id', 'u.id', false, '', '', '', $where);
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
global $CourseInnerStats;

    $res = get_zero_activity_stats();
    foreach ($courses as $courseid) {
        foreach ($CourseInnerStats[$courseid] as $name => $value) {
            $res[$name] += $value;
        }
    }
    return $res;
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
    $sql = "SELECT COUNT(l.id) FROM {logstore_standard_log} l " .
           ($module === null ? '' : "JOIN {modules} m ON (l.component=concat('mod_', m.name)) ") .
           " WHERE l.courseid=? AND l.action LIKE ? " .
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
