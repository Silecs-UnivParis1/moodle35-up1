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
require_once($CFG->dirroot.'/lib/statslib.php');
require_once($CFG->dirroot.'/report/stats/locallib.php'); // WARNING include locallib of another plugin


function teacherstats_enrolments_roles($crsid) {
    global $DB;
    $rolenames = array('editingteacher', 'teacher', 'student');
    //$roles = get_assoc_roles($rolenames);  // inutile ?

    $sql = "SELECT ra.roleid, r.name AS name, COUNT(DISTINCT ra.userid) AS cnt "
         . "FROM {role_assignments} ra JOIN {context} cx ON (cx.id = ra.contextid AND contextlevel = ?) "
         . "JOIN {role} r ON (ra.roleid = r.id) "
         . "WHERE cx.instanceid = ? GROUP BY ra.roleid";
    $cntall = $DB->get_records_sql($sql, array(CONTEXT_COURSE, $crsid));

    $sql = "SELECT ra.roleid, r.name AS name, COUNT(DISTINCT ra.userid) AS cnt "
         . "FROM {role_assignments} ra JOIN {context} cx ON (cx.id = ra.contextid AND contextlevel = ?) "
         . "JOIN {role} r ON (ra.roleid = r.id) "
         . "JOIN {logstore_standard_log} l ON (l.userid = ra.userid AND l.courseid = cx.instanceid) "
         . "WHERE cx.instanceid = ? GROUP BY ra.roleid";
    $cntactive = $DB->get_records_sql($sql, array(CONTEXT_COURSE, $crsid));

    $res = teacherstats_active_table($cntall, $cntactive);
    return $res;
}

function  teacherstats_active_table($cntall, $cntactive){
    $res = array();

    foreach ($cntall as $index => $row) {
        $active = ( isset($cntactive[$index]) ? $cntactive[$index]->cnt : 0 );
        $res[] = array(
            $row->name,
            $row->cnt,
            $active,
            round(100 * $active / $row->cnt) . ' %',
            $row->cnt - $active,
            round(100 * ($row->cnt - $active) / $row->cnt) . ' %',
        );
    }
    return $res;
}

function teacherstats_enrolments_groups($crsid) {
    global $DB;

    $sql = "SELECT g.id, g.name, COUNT(DISTINCT gm.id) AS cnt "
         . "FROM {groups_members} gm JOIN {groups} g on (gm.groupid = g.id) "
         . "WHERE g.courseid = ? GROUP BY g.id";
    $cntall = $DB->get_records_sql($sql, array($crsid));

    $sql = "SELECT g.id, g.name, COUNT(DISTINCT gm.id) AS cnt "
         . "FROM {groups_members} gm JOIN {groups} g on (gm.groupid = g.id) "
         . "JOIN {logstore_standard_log} l ON (l.userid = gm.userid AND l.courseid = g.courseid) "
         . "WHERE l.component = 'core' AND l.action = 'viewed' AND g.courseid = ? GROUP BY g.id";
    $cntactive = $DB->get_records_sql($sql, array($crsid));

    $res = teacherstats_active_table($cntall, $cntactive);
    return $res;
}


/**
 * computes the TOP $limit viewed resources for the target course
 * @param int $crsid course id
 * @param int $limit
 * @return array(array) for the table to display
 */
function teacherstats_resources_top($crsid, $limit) {
    global $DB;
    $res = array();

    $components = array('mod_book', 'mod_folder', 'mod_page', 'mod_resource', 'mod_url');
    $sql = "SELECT CONCAT(component, contextinstanceid), COUNT(id) AS cnt, component, contextinstanceid FROM {logstore_standard_log} "
         . "WHERE courseid=? AND action like 'view%' AND component IN ('" . implode("','", $components) . "') "
         . "GROUP BY component, contextinstanceid ORDER BY cnt DESC LIMIT " . $limit;
    $logtop = $DB->get_records_sql($sql, array($crsid));
    $cnt = 0;
    foreach ($logtop as $log) {
        $cnt++;
        $res[] = array(
            $cnt,
            get_module_title(substr($log->component, 4), $log->contextinstanceid),
            get_string('modulename', $log->component),
            $log->cnt,
        );
    }
    return $res;
}


/**
 * lists the assignments with statistics, as 2 variants : global an groups
 * @param type $crsid
 * @return array(array(array))
 */
function teacherstats_assignments($crsid) {
    global $DB;
    $res = array('global' => null, 'groups' => null);

    // $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, SUM(IF(ass.status = 'submitted', 1, 0)) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
    $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, "
           . "COUNT(DISTINCT ass.id) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
         . "FROM {assign} a "
         . "LEFT JOIN {assign_submission} ass ON (ass.assignment = a.id AND ass.status = 'submitted') "
         . "LEFT JOIN {assign_grades} ag ON (ag.assignment = a.id) "
         . "WHERE a.course = ? GROUP BY a.id";
    $assigns = $DB->get_records_sql($sql, array($crsid));
    foreach($assigns as $assign) {
        $res['global'][] = array(
            $assign->name,
            $assign->due,
            (integer)$assign->cntas,
            (integer)$assign->cntag,
        );
    }

    $sql = "SELECT a.id, a.name, FROM_UNIXTIME(a.duedate) AS due, GROUP_CONCAT(g.name) AS grp, "
           . "COUNT(DISTINCT ass.id) AS cntas, COUNT(DISTINCT ag.id) AS cntag "
         . "FROM {assign} a "
         . "LEFT JOIN {assign_submission} ass ON (ass.assignment = a.id AND ass.status = 'submitted') "
         . "LEFT JOIN {groups} g ON (g.id = ass.groupid)"
         . "LEFT JOIN {assign_grades} ag ON (ag.assignment = a.id) "
         . "WHERE a.course = ? AND ass.groupid > 0  GROUP BY a.id";

    $assigns = $DB->get_records_sql($sql, array($crsid));
    foreach($assigns as $assign) {
        $res['groups'][] = array(
            $assign->name,
            $assign->grp,
            $assign->due,
            (integer)$assign->cntas,
            (integer)$assign->cntag,
        );
    }
    return $res;
}


/**
 * Computes the statistics for selected activities (see $modulenames)
 * WARNING the computing uses the log table only, not the per-module specific tables
 * @param int $crsid
 * @return array(array('string')) table rows and cells
 */
function teacherstats_activities($crsid) {
    global $DB;
    $res = array();
    $modulenames = array('chat', 'data', 'forum', 'glossary', 'wiki');
    $components = array('mod_chat', 'mod_data', 'mod_forum', 'mod_glossary', 'mod_wiki');

    foreach ($modulenames as $modulename) {
        $moduletitle[$modulename] = $DB->get_records_menu($modulename, array('course' => $crsid), null, 'id, name');
    }

    $sql = "SELECT l.contextinstanceid, l.component, cm.instance, COUNT(DISTINCT l.id) AS edits, COUNT(DISTINCT l.userid) AS users "
         . "FROM {logstore_standard_log} l JOIN {course_modules} cm ON  (cm.id = l.contextinstanceid) "
         . "WHERE l.component IN ('" . implode("','", $components) . "') "
         . "  AND l.courseid=? AND l.action IN ('sent', 'created', 'updated') "
         . "GROUP BY contextinstanceid ORDER BY component, contextinstanceid";
    $activities = $DB->get_records_sql($sql, array($crsid));
    foreach ($activities as $activity) {
        $module = substr($activity->component, 4);
        if (isset($moduletitle[$module][$activity->instance])) {
            $res[] = array(
                $moduletitle[$module][$activity->instance],
                get_string('modulename', $module),
                $activity->edits - 1, // sinon la création est comptée comme contribution
                $activity->users - 1, // idem
            );
        }
    }
    return $res;
}

/**
 * Computes the statistics for selected questionnaires (see $modulenames)
 * WARNING the computing uses the log table only, not the per-module specific tables
 * @todo THIS HAS PROBABLY TO BE REWRITTEN WHEN survey, feedback, questionnaire and choice will be unified
 * @todo see http://docs.moodle.org/27/en/Questionnaire -> Moodle 2.8
 * @param int $crsid
 * @return array(array('string')) table rows and cells
 */
function teacherstats_questionnaires($crsid) {
    global $DB;
    $res = array();
    $modulenames = array('quiz', 'feedback', 'choice');
    $components = array('mod_quiz', 'mod_survey', 'mod_feedback', 'mod_choice');

    foreach ($modulenames as $modulename) {
        $moduletitle[$modulename] = $DB->get_records_select($modulename, 'course = ?', array($crsid), '', 'id, name, timeclose as until');
    }
    $moduletitle['survey'] = $DB->get_records_select('survey', 'course = ?', array($crsid), '', 'id, name, 0 as until');

    $sql = "SELECT l.contextinstanceid, l.component, cm.instance, COUNT(DISTINCT l.userid) AS users "
         . "FROM {logstore_standard_log} l JOIN {course_modules} cm ON  (cm.id = l.contextinstanceid) "
         . "WHERE l.component IN ('" . implode("','", $components) . "') "
         . "  AND l.courseid=? AND ( l.action = 'submitted' ) "
         . "GROUP BY l.contextinstanceid ORDER BY l.component, l.contextinstanceid";

    $activities = $DB->get_records_sql($sql, array($crsid));

    foreach ($activities as $activity) {
        $module = substr($activity->component, 4);
        if (isset($moduletitle[$module][$activity->instance])) {
            $activite = $moduletitle[$module][$activity->instance];
            $res[] = array(
                $activite->name,
                get_string('modulename', $module),
                $activity->users,
                $activite->until > 0 ? date('d-m-Y H:i', $activite->until) : 'Aucune',
            );
        }
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

function get_assoc_roles($rolenames) {
    global $DB;
    $sql = "SELECT id, shortname from {role} WHERE shortname IN ('" . implode("','", $rolenames) .  "')";
    $records = $DB->get_records_sql_menu($sql);
    return $records;
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


// Les 2 fonctions suivantes sont inspirées/adaptées de  report_stats_report() du plugin report_stats
// FIXME à adapter si elle est réécrite : GROSSE horreur, monolithique, illisible
// time = (codage crétin) : 4 = 4 semaines, par jour ; 12 = 2 mois, par semaine ; année passée = 32, par mois
// cf function stats_get_parameters() dans lib/statslib.php

// Pour afficher les graphiques calculés par report_stats
function teacherstats_graph_connections($courseid, $time, $alt) {
    global $CFG, $DB, $OUTPUT;
    $mode = STATS_MODE_GENERAL; // =1
    $report = 2; // affichages ;  1=connexions, seulement pour le cours 1
    $roleid = 0; // tous les roles
    echo '<div class="graph">';
    echo '<img src="'.$CFG->wwwroot.'/report/stats/graph.php?mode='.$mode.'&amp;course='.$courseid.'&amp;time='.$time.'&amp;report='.$report.'&amp;roleid='.$roleid.'" alt="'.$alt.'" />';
    echo '</div>';
}


// Fonction inutilisée mais potentiellement utile pour débogage
function teacherstats_report_connections($course, $time) {
    global $CFG, $DB, $OUTPUT;

    $user = null;
    $userid = 0;
    $mode = STATS_MODE_GENERAL;
    $roleid = 0;
    $report = 2; // affichages ;  1=connexions, seulement pour le cours 1

    $courses = get_courses('all','c.shortname','c.id,c.shortname,c.fullname');
    $courseoptions = array();

    foreach ($courses as $c) {
        $context = context_course::instance($c->id);

        if (has_capability('report/stats:view', $context)) {
            $courseoptions[$c->id] = format_string($c->shortname, true, array('context' => $context));
        }
    }

    $reportoptions = stats_get_report_options($course->id, $mode);
    $timeoptions = report_stats_timeoptions($mode);
    if (empty($timeoptions)) {
        print_error('nostatstodisplay', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
    }

    $users = array();
    $table = new html_table();
    $table->width = 'auto';

    if (!empty($report) && !empty($time)) {
        if ($report == STATS_REPORT_LOGINS && $course->id != SITEID) {
            print_error('reportnotavailable');
        }

        $param = stats_get_parameters($time,$report,$course->id,$mode);

        if (!empty($param->sql)) {
            $sql = $param->sql;
        } else {
            //TODO: lceanup this ugly mess
            $sql = 'SELECT '.((empty($param->fieldscomplete)) ? 'id,roleid,timeend,' : '').$param->fields
                .' FROM {stats_'.$param->table.'} WHERE '
                .(($course->id == SITEID) ? '' : ' courseid = '.$course->id.' AND ')
                .((!empty($userid)) ? ' userid = '.$userid.' AND ' : '')
                .((!empty($roleid)) ? ' roleid = '.$roleid.' AND ' : '')
                . ((!empty($param->stattype)) ? ' stattype = \''.$param->stattype.'\' AND ' : '')
                .' timeend >= '.$param->timeafter
                .' '.$param->extras
                .' ORDER BY timeend DESC';
        }

// echo $sql;
// $sql = "SELECT CONCAT(timeend, roleid) AS uniqueid, timeend, roleid, sum(stat1) as line1 FROM {stats_daily}"
// . " WHERE stattype = 'activity' AND timeend >= 1401746400 GROUP BY timeend,roleid ORDER BY timeend DESC";

        $stats = $DB->get_records_sql($sql);

        if (empty($stats)) {
            echo $OUTPUT->notification(get_string('statsnodata'));

        } else {

            $stats = stats_fix_zeros($stats,$param->timeafter,$param->table,(!empty($param->line2)));

            echo $OUTPUT->heading(format_string($course->shortname).' - '.get_string('statsreport'.$report)
                    .((!empty($user)) ? ' '.get_string('statsreportforuser').' ' .fullname($user,true) : '')
                    .((!empty($roleid)) ? ' '.$DB->get_field('role','name', array('id'=>$roleid)) : ''));

            if ($mode == STATS_MODE_DETAILED) {
                echo '<div class="graph"><img src="'.$CFG->wwwroot.'/report/stats/graph.php?mode='.$mode.'&amp;course='.$course->id.'&amp;time='.$time.'&amp;report='.$report.'&amp;userid='.$userid.'" alt="'.get_string('statisticsgraph').'" /></div>';
            } else {
                echo '<div class="graph"><img src="'.$CFG->wwwroot.'/report/stats/graph.php?mode='.$mode.'&amp;course='.$course->id.'&amp;time='.$time.'&amp;report='.$report.'&amp;roleid='.$roleid.'" alt="'.get_string('statisticsgraph').'" /></div>';
            }

            $table = new html_table();
            $table->align = array('left','center','center','center');
            $param->table = str_replace('user_','',$param->table);
            switch ($param->table) {
                case 'daily'  : $period = get_string('day'); break;
                case 'weekly' : $period = get_string('week'); break;
                case 'monthly': $period = get_string('month', 'form'); break;
                default : $period = '';
            }
            $table->head = array(get_string('periodending','moodle',$period));
            if (empty($param->crosstab)) {
                $table->head[] = $param->line1;
                if (!empty($param->line2)) {
                    $table->head[] = $param->line2;
                }
            }

            if (empty($param->crosstab)) {
                foreach  ($stats as $stat) {
                    $a = array(userdate($stat->timeend-(60*60*24),get_string('strftimedate'),$CFG->timezone),$stat->line1);
                    if (isset($stat->line2)) {
                        $a[] = $stat->line2;
                    }
                    if (empty($CFG->loglifetime) || ($stat->timeend-(60*60*24)) >= (time()-60*60*24*$CFG->loglifetime)) {
                        if (has_capability('report/log:view', context_course::instance($course->id))) {
                            $a[] = '<a href="'.$CFG->wwwroot.'/report/log/index.php?id='.
                                $course->id.'&amp;chooselog=1&amp;showusers=1&amp;showcourses=1&amp;user='
                                .$userid.'&amp;date='.usergetmidnight($stat->timeend-(60*60*24)).'">'
                                .get_string('course').' ' .get_string('logs').'</a>&nbsp;';
                        } else {
                            $a[] = '';
                        }
                    }
                    $table->data[] = $a;
                }
            } else {
                $data = array();
                $roles = array();
                $times = array();
                $missedlines = array();
                $coursecontext = context_course::instance($course->id);
                $rolenames = role_fix_names(get_all_roles($coursecontext), $coursecontext, ROLENAME_ALIAS, true);
                foreach ($stats as $stat) {
                    if (!empty($stat->zerofixed)) {
                        $missedlines[] = $stat->timeend;
                    }
                    $data[$stat->timeend][$stat->roleid] = $stat->line1;
                    if ($stat->roleid != 0) {
                        if (!array_key_exists($stat->roleid,$roles)) {
                            $roles[$stat->roleid] = $rolenames[$stat->roleid];
                        }
                    } else {
                        if (!array_key_exists($stat->roleid,$roles)) {
                            $roles[$stat->roleid] = get_string('all');
                        }
                    }
                    if (!array_key_exists($stat->timeend,$times)) {
                        $times[$stat->timeend] = userdate($stat->timeend,get_string('strftimedate'),$CFG->timezone);
                    }
                }

                foreach ($data as $time => $rolesdata) {
                    if (in_array($time,$missedlines)) {
                        $rolesdata = array();
                        foreach ($roles as $roleid => $guff) {
                            $rolesdata[$roleid] = 0;
                        }
                    }
                    else {
                        foreach (array_keys($roles) as $r) {
                            if (!array_key_exists($r, $rolesdata)) {
                                $rolesdata[$r] = 0;
                            }
                        }
                    }
                    krsort($rolesdata);
                    $row = array_merge(array($times[$time]),$rolesdata);
                    if (empty($CFG->loglifetime) || ($stat->timeend-(60*60*24)) >= (time()-60*60*24*$CFG->loglifetime)) {
                        if (has_capability('report/log:view', context_course::instance($course->id))) {
                            $row[] = '<a href="'.$CFG->wwwroot.'/report/log/index.php?id='
                                .$course->id.'&amp;chooselog=1&amp;showusers=1&amp;showcourses=1&amp;user='.$userid
                                .'&amp;date='.usergetmidnight($time-(60*60*24)).'">'
                                .get_string('course').' ' .get_string('logs').'</a>&nbsp;';
                        } else {
                            $row[] = '';
                        }
                    }
                    $table->data[] = $row;
                }
                krsort($roles);
                $table->head = array_merge($table->head,$roles);
            }
            $table->head[] = get_string('logs');
            //if (!empty($lastrecord)) {
                //$lastrecord[] = $lastlink;
                //$table->data[] = $lastrecord;
            //}
            echo html_writer::table($table);
        }
    }
}
