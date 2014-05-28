<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage cohortsyncup1
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * In this file, the functions related to cohorts sync used outside of local/cohortsyncup1
 */

/**
 * returns the last sync from the logs
 * @param $synctype = 'syncFromUsers'|'syncAllGroups'
 * @return array('begin' => integer, 'end' => integer) as moodle timestamps
 * @uses exit
 */
function get_cohort_last_sync($synctype) {
    global $DB;

    $allowedSyncs = array('syncFromUsers', 'syncAllGroups');
    if ( ! in_array($synctype, $allowedSyncs)) {
        throw new coding_exception('unknown sync type: ['. $synctype . '].');
    }
    $sql = "SELECT MAX(time) FROM {log} WHERE module=? AND action=?";
    $begin = $DB->get_field_sql($sql, array('local_cohortsyncup1', $synctype.':begin'));
    if ($begin === null) $begin=0;
    $end = $DB->get_field_sql($sql, array('local_cohortsyncup1', $synctype.':end'));
    if ($end === null) $end=0;
        $res = array(
            'begin' => $begin,
            'end' => $end,
        );
        return $res;
}


/**
 * search "new" cohorts equivalent to "old" ones, for yearly cohorts
 * @param array $old_idnumbers
 * @return assoc. array (array)
 */
function get_equivalent_cohorts($old_idnumbers) {
    global $DB;

    $res = array('new' => array(), 'notfound' => array(), 'unchanged' => array());
    $curyear = get_config('local_cohortsyncup1', 'cohort_period');
    foreach ($old_idnumbers as $idnumber) {
        $dbcohort = $DB->get_record('cohort', array('idnumber' => $idnumber), '*', MUST_EXIST);

        if ($dbcohort->up1period == '') {
            $res['unchanged'][] = $idnumber;
        } else {
            if ($dbcohort->up1period == $curyear) {
                $res['unchanged'][] = $idnumber;
            }
            else {
                $potidnumber = cohort_raw_idnumber($idnumber) . '-' . $curyear;  // potential idnumber
                if ( $DB->record_exists('cohort', array('idnumber' => $potidnumber)) ) {
                    $res['new'][] = $potidnumber;
                } else {
                    $res['notfound'][] = $potidnumber;
                }
            }
        }
    }
    return $res;
}

/**
 * text to explain in user interface the reuse of "old" cohorts (to be used in wizard)
 * @param array $equivs as computed by the previous function
 */
function explain_equivalent_cohorts($equivs) {
    if (count($equivs['new'])) {
        echo "Les cohortes annualisées suivantes ont été reconnues et leurs équivalentes actuelles préselectionnées :\n<ul>\n";
        foreach ($equivs['new'] as $idnumber) {
            echo '<li>' . $idnumber . "</li>\n";
        }
        echo "</ul>\n";
    }
    if (count($equivs['notfound'])) {
        echo "Les cohortes annualisées suivantes n'ont apparemment pas d'équivalentes actuelles :\n<ul>\n";
        foreach ($equivs['notfound'] as $idnumber) {
            echo '<li>' . $idnumber . "</li>\n";
        }
        echo "</ul>\n";
    }
    if (count($equivs['unchanged'])) {
        echo "Les cohortes suivantes sont toujours valables : <br />\n<ul>\n";
        foreach ($equivs['unchanged'] as $idnumber) {
            echo '<li>' . $idnumber . "</li>\n";
        }
        echo "</ul>\n";
    }
    return true;
}
