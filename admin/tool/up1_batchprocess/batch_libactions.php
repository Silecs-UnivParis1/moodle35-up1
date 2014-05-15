<?php

/**
 * Multi-criteria selection and batch processing for courses
 *
 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
$redirectUrl = $CFG->wwwroot . '/admin/tool/up1_batchprocess/index.php';

/**
 * prefixes each course with a given string
 * @param array $courses
 * @param string $prefix
 * @param bool $redirect
 */
function batchaction_prefix($courses, $prefix, $redirect) {
global $redirectUrl, $DB, $CFG;

    if ($prefix) {
        foreach ($courses as $course) {
        $course->fullname = $prefix . $course->fullname;
        // $course->shortname = $prefix . $course->shortname;
        $DB->update_record('course', $course);
     }
    if ($redirect) {
        redirect($redirectUrl);
        exit();
        }
    }
}

/**
 * suffixes each course with a given string
 * @param array $courses
 * @param string $suffix
 * @param bool $redirect
 */
function batchaction_suffix($courses, $suffix, $redirect) {
global $redirectUrl, $DB, $CFG;

    if ($suffix) {
        foreach ($courses as $course) {
        $course->fullname = $course->fullname . $suffix;
        // $course->shortname = $course->shortname . $suffix;
        $DB->update_record('course', $course);
     }
    if ($redirect) {
        redirect($redirectUrl);
        exit();
        }
    }
}

/**
 * search-and-replace a given regexp  in each course
 * @param array $courses
 * @param string $regexp
 * @param string $replace
 * @param bool $redirect
 */
function batchaction_regexp($courses, $regexp, $replace, $redirect) {
global $redirectUrl, $DB, $CFG;

    foreach ($courses as $course) {
        $course->fullname = preg_replace('/' . $regexp . '/', $replace, $course->fullname);
        // $course->shortname = preg_replace('/' . $regexp . '/', $replace, $course->shortname);
        $DB->update_record('course', $course);
    }
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
}

/**
 * open (visible:=1) or close (visible:=0) each course
 * @param array $courses
 * @param bool $redirect
 */
function batchaction_visibility($courses, $visible, $redirect) {
global $redirectUrl, $DB, $CFG;

    foreach ($courses as $course) {
        $course->visible = $visible;
        $DB->update_record('course', $course);
    }
    $msg = "Mise à jour de " . count($courses) . " cours." ;
    /** @todo flash message */
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
}


/**
 * substitute roles for all users of $rolefrom to $roleto in each course
 * @param array $courses of (DB) objects course
 * @param int $rolefrom
 * @param int $roleto
 * @param bool $redirect
 */
function batchaction_substitute($courses, $rolefrom, $roleto, $redirect) {
global $redirectUrl, $DB, $CFG, $USER;

    $modifiedroles = 0;
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        $cnt = $DB->count_records('role_assignments', array('roleid' => $rolefrom, 'contextid' => $context->id));
        $sql = "UPDATE {role_assignments} SET roleid=?, timemodified=UNIX_TIMESTAMP(), modifierid=? "
             . "WHERE roleid=? AND contextid=?";
        $ret = $DB->execute($sql, array($roleto, $USER->id, $rolefrom, $context->id));
        if ($ret) {
            $modifiedroles += $cnt;
        }
    }
    $msg = "$modifiedroles substitutions dans " . count($courses) . " cours." ;
    /** @todo flash message */
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
}


/**
 * update the custominfo field "datearchivage" for each course
 * @param array $courses of (DB) objects course
 * @param int $tsdate
 * @param bool $redirect
 */
function batchaction_archdate($courses, $tsdate, $redirect) {
global $redirectUrl, $DB, $CFG;

    foreach ($courses as $course) {
        up1_meta_set_data($course->id, 'datearchivage', $tsdate);
    }
    $msg = "Mise à jour de " . count($courses) . " cours." ;
    /** @todo flash message */
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
}

/**
 * disable each enrolment method (except 'manual') of each course
 * @param array $courses of (DB) objects course
 * @param bool $redirect
 */
function batchaction_disable_enrols($courses, $redirect) {
global $redirectUrl, $DB, $CFG;

    $cnt = 0;
    $excepts = array('manual');
    $plugins = enrol_get_plugins(false);
    // code dérivé de  moodle/enrol/instances.php l.143  (action=='disable')
    foreach ($courses as $course) {
        $instances = enrol_get_instances($course->id, false); // records of table "enrol"
        foreach ($instances as $instanceid => $instance) {
            $plugin = $plugins[$instance->enrol];
            if ( $instance->status != ENROL_INSTANCE_DISABLED && ! in_array($instance->enrol, $excepts) ) {
                $plugin->update_status($instance, ENROL_INSTANCE_DISABLED);
                $cnt++;
            }
        }
    }
    $msg = "Désactivation de $cnt méthodes d'inscription dans " . count($courses) . " cours.";
    /** @todo flash message */
    if ($redirect) {
        redirect($redirectUrl);
        exit();
    }
}