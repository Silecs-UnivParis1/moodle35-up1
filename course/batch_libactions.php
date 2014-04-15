<?php

/*
 * @license http://www.gnu.org/licenses/gpl-2.0.html  GNU GPL v2
 */

/**
 * prefixes each course with a given string
 * @param array $courses
 * @param string $prefix
 * @param bool $redirect
 */
function batchaction_prefix($courses, $prefix, $redirect) {
global $DB, $CFG;

    if ($prefix) {
        foreach ($courses as $course) {
        $course->fullname = $prefix . $course->fullname;
        // $course->shortname = $prefix . $course->shortname;
        $DB->update_record('course', $course);
     }
    if ($redirect) {
        redirect($CFG->wwwroot . '/course/batch.php');
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
global $DB, $CFG;

    if ($suffix) {
        foreach ($courses as $course) {
        $course->fullname = $course->fullname . $suffix;
        // $course->shortname = $course->shortname . $suffix;
        $DB->update_record('course', $course);
     }
    if ($redirect) {
        redirect($CFG->wwwroot . '/course/batch.php');
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
global $DB, $CFG;

    foreach ($courses as $course) {
        $course->fullname = preg_replace('/' . $regexp . '/', $replace, $course->fullname);
        // $course->shortname = preg_replace('/' . $regexp . '/', $replace, $course->shortname);
        $DB->update_record('course', $course);
    }
    if ($redirect) {
        redirect($CFG->wwwroot . '/course/batch.php');
        exit();
    }
}

/**
 * close each course
 * @param array $courses
 * @param bool $redirect
 */
function batchaction_close($courses, $redirect) {
global $DB, $CFG;

    foreach ($courses as $course) {
        $course->visible = 0;
        $DB->update_record('course', $course);
    }
    if ($redirect) {
        redirect($CFG->wwwroot . '/course/batch.php');
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
global $DB, $CFG, $USER;

    $modifiedroles = 0;
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        $cnt = $DB->count_records('role_assignments', array('roleid' => $rolefrom, 'contextid' => $context->id));
        $sql = "UPDATE {role_assignments} SET roleid=?, timemodified=UNIX_TIMESTAMP(), modifierid=? WHERE roleid=? AND contextid=?";
        $ret = $DB->execute($sql, array($roleto, $USER->id, $rolefrom, $context->id));
        if ($ret) {
            $modifiedroles += $cnt;
        }
    }
    $msg = "$modifiedroles substitutions dans " . count($courses) . " cours." ;
    if ($redirect) {
        redirect($CFG->wwwroot . '/course/batch.php');
        exit();
    }

}