<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwsgroups
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $DB moodle_database */

define('MWS_SEARCH_MAXROWS', 100);

require __DIR__ . '/lib_users.php';

class mws_search_groups
{
    /**
     * @var string to search in user and cohort tables
     */
    public $token;

    /**
     * @var int
     */
    public $usermaxrows;

    /**
     * @var int
     */
    public $groupmaxrows = MWS_SEARCH_MAXROWS;

    /**
     * @var string 'no' | 'only' | 'both' (default)
     */
    public $filterstudent = 'both';

    /**
     * @var string '' (default) | 'structures' | 'affiliation' | ...
     */
    public $filtergroupcat = '';

    /**
     * @var Include obsolete cohorts
     */
    public $archives = true;

    /**
     * List the users matching the criteria
     *
     * @return array users
     */
    public function search_users() {
        if ($this->usermaxrows > MWS_SEARCH_MAXROWS || $this->usermaxrows == 0) {
            $this->usermaxrows = MWS_SEARCH_MAXROWS;
        }

        $search_u = new mws_search_users();
        $search_u->maxrows = $this->usermaxrows;
        $search_u->filterstudent = $this->filterstudent;
        return $search_u->search($this->token);
    }

    /**
     * List the groups matching the criteria
     *
     * @return array groups
     */
    public function search_groups() {
        if ($this->groupmaxrows > MWS_SEARCH_MAXROWS || $this->groupmaxrows == 0) {
            $this->groupmaxrows = MWS_SEARCH_MAXROWS;
        }

        if ($this->filtergroupcat == '') {
            $groups = $this->search_groups_all();
        } else {
            $groups = $this->search_groups_category($this->filtergroupcat);
        }
        return $groups;
    }

    /**
     * search groups according to filters
     * @return array
     */
    protected function search_groups_all() {
        $wherecat = self::categoryToWhere();

        $res = array();
        foreach (array_keys($wherecat) as $cat) {
            // echo "<b> $cat -> $where : " . count($groups) . " results</b><br />\n" ; //DEBUG
            $res = array_merge($res, $this->search_groups_category($cat));
        }
        return $res;
    }

    /**
     * search groups according to filters
     * @global type $DB
     * @param string $category Group/cohort category, see below
     * @return array
     */
    protected function search_groups_category($category) {
        global $DB;
        $ptoken = '%' . $DB->sql_like_escape($this->token) . '%';

        $wherecat = self::categoryToWhere();
        $cterms = explode('|', $category);
        $cwhere = array();
        foreach ($cterms as $term) {
            if (isset($wherecat[$term])) {
                $cwhere[] = $wherecat[$term];
            }
        }
        if (!$cwhere) {
            return array();
        }
        $sql = "SELECT id, name, idnumber, description, descriptionformat FROM {cohort} WHERE "
            . "( name LIKE ? OR idnumber LIKE ? ) AND (" . join(' OR ', $cwhere) . ')' ;
        if (!$this->archives) {
            $sql .= " AND up1key <> '' ";
        }
        // echo $sql . " <br />\n" ; //DEBUG
        $records = $DB->get_records_sql($sql, array($ptoken, $ptoken), 0, $this->groupmaxrows);
        $groups = array();
        $order = 0;
        foreach ($records as $record) {
            $order++;
            $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
            $groups[] = array(
                'key' => $record->idnumber,
                'name' => $record->name,
                'description' => format_text($record->description, $record->descriptionformat),
                'category' => groupKeyToCategory($record->idnumber),
                'size' => $size,
                'order' => $order
            );
        }
        return $groups;
    }

    /**
     * sort of reciprocal from groupKeyToCategory
     * return array assoc. array of WHERE conditions in the SQL syntax
     */
    protected static function categoryToWhere() {
        $patterns = array(
            'structures' => 'structures-%',
            'affiliation' => 'affiliation-%',
            'diploma' => 'diploma-%',
            'gpelp' => 'groups-gpelp.%',
            //'gpetp' => 'groups-gpetp.%',
            'elp' => 'groups-mati%'
        );
        $res = array();
        $other = '';
        foreach ($patterns as $cat => $pattern) {
            $res[$cat] = "idnumber LIKE '$pattern' ";
            $other = $other . "idnumber NOT LIKE '$pattern' AND ";
        }
        $res['other'] = substr($other, 0, -4); //drop the last AND
        return $res;
    }
}

/**
 * Wrapper on the class mws_search_groups, emulates wsgroups "search" action from Moodle data
 *
 * @param string $token to search in user and cohort tables
 * @param int $usermaxrows
 * @param int $groupmaxrows
 * @param string $filterstudent = 'no' | 'only' | 'both'
 * @param string $filtergroupcat = '' | 'structures' | 'affiliation' | ...
 * @return array('users' => $users, 'groups' => $groups)
 */
function mws_search($token, $usermaxrows, $groupmaxrows, $filterstudent='both', $filtergroupcat='') {
    $s = new mws_search_groups();
    $s->token = $token;
    $s->usermaxrows = $usermaxrows;
    $s->groupmaxrows = $groupmaxrows;
    $s->filterstudent = $filterstudent;
    $s->filtergroupcat = filtergroupcat;
    return array('users' => $s->search_users(), 'groups' => $s->search_groups());
}

/**
 * emulates wsgroups "userGroupsId" action from Moodle data
 * @global type $DB
 * @param string $uid (sens ldap) Moodle username
 * @return $groups as wsgroups structure
 */
function mws_userGroupsId($uid) {
    global $DB;

    $user = $DB->get_record('user', array('username' => $uid), 'id', MUST_EXIST);
    // on évite une 2e jointure dans la requête suivante, qui ralentit considérablement
    $groups = array();
    $sql = "SELECT c.id, c.name, c.idnumber, c.description, c.descriptionformat "
        . "FROM {cohort} c JOIN {cohort_members} cm ON (cm.cohortid = c.id) "
        . "WHERE userid = ?";

    $records = $DB->get_records_sql($sql, array($user->id));
    foreach ($records as $record) {
        $size = $DB->count_records('cohort_members', array('cohortid' => $record->id));
        $groups[] = array(
            'key' => $record->idnumber,
            'name' => $record->name,
            'description' => format_text($record->description, $record->descriptionformat),
            'category' => groupKeyToCategory($record->idnumber),
            'size' => $size
        );
     }
    return $groups;
}

/**
 * function provided by Pascal Rigaux, cf http://tickets.silecs.info/mantis/view.php?id=1642 (5089)
 * @param string $key group key == cohort idnumber
 * @return string category, among (structures, affiliation, diploma, elp, gpelp, gpetp)
 */
function groupKeyToCategory($key) {
    if (
            preg_match('/^(structures|affiliation|diploma)-/', $key, $matches)
            || preg_match('/^groups-(gpelp|gpetp)\./', $key, $matches)
    ) {
        return $matches[1];
    } else if (startsWith($key, 'groups-mati'))
        return 'elp';
    else if (startsWith($key, 'groups-'))
        return 'other';
    else
        return null;
}

/**
 * returns an associative array, telling for each group category (see above) if it's yearly or not
 * @return array( string => boolean)
 */
function groupYearlyPredicate() {
    $curyear = get_config('local_cohortsyncup1', 'cohort_period');
    if ($curyear == '0') { // l'annualisation ne doit pas etre appliquée
        $groupYearly = array(
            'structures' => false,
            'affiliation' => false,
            'diploma' => false,
            'gpelp' => false,
            'gpetp' => false,
            'elp' => false,
            'other' => false,
            null => false
        );
    } else {
        $groupYearly = array(
            'structures' => false,
            'affiliation' => false,
            'diploma' => true,
            'gpelp' => true,
            'gpetp' => true,
            'elp' => true,
            'other' => false,
            null => false
        );
    }
    return $groupYearly;
}


/**
 * @param string $haystack
 * @param string $needle
 * @return boolean
 */
function startsWith($haystack, $needle)
{
    return strncmp($haystack, $needle, strlen($needle)) === 0;
}
