<?php

/**
 * Lib functions
 *
 * @package    report
 * @subpackage up1userstats
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/auth/ldapup1/auth.php');
require_once($CFG->dirroot . '/local/cohortsyncup1/lib.php');

$cohortPrefixes = array('structures', 'diploma-', 'groups-', 'affiliation-');

defined('MOODLE_INTERNAL') || die;

/*
 * Users and Authentication statistics
 */

function report_up1userstats_users() {
    global $DB;
    $res = array();

    $count = $DB->count_records('user_sync', array('ref_plugin' => 'auth_ldapup1'));
    $res[] = array('Utilisateurs annuaire (user_sync)', $count);

    $rows = $DB->get_records_sql("SELECT auth, COUNT(id) AS cnt FROM {user} GROUP BY auth WITH ROLLUP");
    foreach ($rows as $row) {
        if ($row->auth == '') {
            $auth = "TOTAL auth.";
        } else {
            $auth = 'Auth. ' . $row->auth;
        }
        $res[] = array($auth, $row->cnt);
    }
    return $res;
}

function report_up1userstats_users_by_affiliation() {
    global $DB;
    $res = array();

    $fieldid = $DB->get_field('custom_info_field', 'id',
            array('objectname'=>'user', 'shortname'=>'up1edupersonprimaryaffiliation'), MUST_EXIST);
    $sql = "SELECT data, count(id) as cnt FROM {custom_info_data} WHERE fieldid = ? GROUP BY data";
    $rows = $DB->get_records_sql($sql, array($fieldid));
    foreach ($rows as $row) {
        $res[] = array($row->data, $row->cnt);
    }
    return $res;
}


/*
 * Cohorts statistics
 */

function report_up1userstats_cohorts_generic() {
    global $DB;
    $res = array();

    $counttot = $DB->count_records('cohort', array('component' => 'local_cohortsyncup1'));
    $res[] = array('Cohortes UP1', $counttot);
    $count = $DB->count_records('cohort', array('component' => 'local_cohortsyncup1', 'up1key' => ''));
    $res[] = array('Non synchronisées', $count);
    $res[] = array('Synchronisées', $counttot - $count);
    
    $sql = "SELECT COUNT(*) FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (cm.cohortid = c.id) WHERE c.component = 'local_cohortsyncup1' ";
    $count = $DB->count_records_sql($sql);
    $res[] = array('Appartenances UP1', $count);
    return $res;
}


/**
 * see report_up1userstats_cohorts_criterium() below
 */
function report_up1userstats_cohorts_category() {
    return report_up1userstats_cohorts_criterium('up1category');
}

function report_up1userstats_cohorts_period() {
    return report_up1userstats_cohorts_criterium('up1period');
}

/**
 * compute cohorts nb and enrolled cohorts nb by (criterium $crit)
 * @param string $crit "up1category" or "up1period"
 * @return array(array) to be displayed by html_writer::table
 */
function report_up1userstats_cohorts_criterium($crit) {
    global $DB;
// NOTA: you have to define an index on enrol.customint1 to get a reasonable response time
    $sql = "SELECT IF(" .$crit. " <> '', " .$crit. ", '(none)') AS " .$crit
         . ", COUNT(DISTINCT c.id) AS cnt , COUNT(DISTINCT e.id) AS cntenrol, COUNT(DISTINCT e.customint1) AS cntec "
         . "FROM {cohort} c LEFT JOIN {enrol} e ON (e.enrol = 'cohort' AND e.customint1 = c.id) "
         . "WHERE component LIKE 'local_cohortsyncup1%' GROUP BY " .$crit." ORDER BY " .$crit. " ASC";
    $rows = $DB->get_records_sql($sql);
    $res = (array) $rows;
    return $res;
}

/**
 * compute top cohorts by members (optionally for a specific idnumber prefix)
 * @param int $limit SQL limit
 * @param string $prefix cohort.idnumber prefix
 * @return array(array) table partial content (N rows x 3 cols)
 */

function report_up1userstats_cohorts_top($limit, $prefix=false) {
    global $DB;
    $res = array();

    $sql = "SELECT cm.cohortid, c.idnumber, c.name, COUNT(cm.id) AS cnt "
        . "FROM {cohort_members} cm "
        . "JOIN {cohort} c ON (c.id = cm.cohortid) "
        . ($prefix ? "WHERE idnumber LIKE '".$prefix."%' " : '')
        . "GROUP BY cohortid  ORDER BY cnt DESC  LIMIT " . $limit;
    $cohorts = $DB->get_records_sql($sql);
    foreach ($cohorts as $cohort) {
        $url = new moodle_url('/cohort/view.php', array('id' => $cohort->cohortid));
        $res[] = array($cohort->cnt,
            html_writer::link($url, $cohort->idnumber),
            $cohort->name);
    }
    return $res;
}

/**
 * iterates the previous functions for all official prefixes
 * @param int $limit SQL limit
 * @return array(array)  table content (N rows x 3 cols)
 */
function report_up1userstats_cohorts_top_by_prefix($limit) {
    global $cohortPrefixes;
    $res = array();

    foreach ($cohortPrefixes as $prefix) {
        $linkdetails = html_writer::link(
            new moodle_url('/report/up1userstats/topcohorts.php', array('number'=>50, 'prefix'=>$prefix)),
            'Détails');
        $res[] = array('', $prefix, $linkdetails); // Separator header row for a given prefix
        $tres = report_up1userstats_cohorts_top($limit, $prefix);
        $res = array_merge($res, $tres);
    }
    return $res;
}


/*
 * Sync and log statistics
 */

function report_up1userstats_last_sync() {
    // $ldap = auth_plugin_ldapup1::get_last_sync(); // because non-static method
    $ldap = get_auth_plugin('ldapup1')->get_last_sync();
    $cohorts = get_cohort_last_sync('syncAllGroups');

    $res = array(
        array('LDAP', $ldap['begin'], $ldap['end']),
        array('Cohorts',
            date('Y-m-d H:i:s ', $cohorts['begin']),
            date('Y-m-d H:i:s ', $cohorts['end'])),
    );
    return $res;
}

/**
 *
 * @param string $plugin "ldap" or "cohort" : prefix for the 'action' column
 * @param int $howmany
 * @return array
 */
function report_up1userstats_syncs($plugin, $howmany) {
    global $DB;

    $res = array();
    $sql = "SELECT * FROM {up1_cohortsync_log} WHERE action LIKE ? ORDER BY id DESC LIMIT ". $howmany;
    $logs = $DB->get_records_sql($sql, [$plugin . ':%']);

    $logs = array_reverse($logs);
    foreach($logs as $log) {
        $begin = ($log->timebegin== 0 ? '?' : date('Y-m-d H:i:s ', $log->timebegin));
        $end = ($log->timeend == 0 ? '?' : date('Y-m-d H:i:s ', $log->timeend));
        $res[] = array($begin, $end, $log->action, $log->info);
    }
    return $res;
}
