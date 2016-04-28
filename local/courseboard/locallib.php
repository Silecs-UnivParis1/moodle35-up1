<?php

/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/custominfo/lib.php');

/* @var $DB moodle_database */

/**
 * print a table of courses data vs ROF data
 * 1st column = label
 * 2nd column = rof (metadata) data
 * 3rd+ columns = rof data, as many as rof references in "up1rofid" meta-field
 * @global moodle_database $DB
 * @global type $OUTPUT
 * @param int $crsid
 * @param array $rofdata : index => array ($up1category => array( 'up1field' => value ) )
 */
function print_table_course_vs_rof($crsid, $rofdata) {
    global $DB, $OUTPUT;

    $crsfields = custominfo_data::type('course')->get_structured_fields_short($crsid, true);
    foreach ($crsfields as $category => $fields) {
        if ($category == 'Other fields' || $category == 'Autres champs') {
            continue;
        }
        $catid = $DB->get_field('custom_info_category', 'id', array('name' => $category));
        $editurl = new moodle_url('/course/edit.php', array('id' => $crsid));
        $editurl->set_anchor('category_' . $catid);
        echo '<h4>' . $category . ' '
                . $OUTPUT->action_icon($editurl, new pix_icon('t/edit', 'Modifier les métadonnées'))
                . " </h4>\n";

        $table = new html_table();
        $table->data = array();
        foreach ($fields as $shortname => $field) { // $rows
            $row = new html_table_row();
            $row->cells[0] = new html_table_cell($field['name']);
            $row->cells[0]->attributes = array('title' => $shortname, 'class' => '');
            $row->cells[1] = new html_table_cell($field['data']);
            $row->cells[1]->attributes = array(
                    'data-courseid' => $crsid, 'data-fieldshortname' => $shortname, 'class' => 'updatable'
            );
            $ddlist = rof_get_menu_constant($shortname, true);
            if ($ddlist) {
                $row->cells[1]->attributes['data-structure'] = json_encode(
                        array(
                            'type' => 'list',
                            'options' => array_values($ddlist),
                        )
                );
            }
            foreach ($rofdata as $ind => $rofcolumn) { // columns 2+
                $cell = (isset($rofcolumn[$category][$shortname]) ? $rofcolumn[$category][$shortname] : '(NA)');
                if ($shortname == 'up1rofpathid') {
                    $rofpathid = array_filter(explode('/', $cell));
                    $cell = rof_format_path(rof_get_combined_path($rofpathid), 'rofid', true, '/');
                }
                $row->cells[2 + $ind] = $cell;
            }
            $table->data[] = $row;
        }
        $table->data = array_merge(get_table_course_header(count($rofdata)), $table->data);
        echo html_writer::table($table);
    } // categories
}


/**
 *
 * @param int $rofcols number of rof columns
 * @return type
 */
function get_table_course_header($rofcols) {
    $headings = array('Métadonnée', 'Métadonnée étendue');
    for ($i = 1 ; $i <= $rofcols ; $i++) {
        $headings[] = 'ROF ' . $i;
    }
    $row = array();
    foreach ($headings as $h) {
        $cell = new html_table_cell($h);
        $cell->header = true;
        $row[] = $cell;
    }
    return array($row);
}


/**
 * print html table of memo messages on a course
 * @global type $DB
 * @param int $crsid
 */
function print_course_memo($crsid) {
    global $DB;

    $table = new html_table();
    $table->classes = array('logtable', 'generalbox');
    $table->align = array('right', 'left', 'left');
    $table->head = array(
        get_string('time'),
        get_string('fullnameuser'),
        'Message'
    );
    $table->data = array();

    $sql = "SELECT m.id, m.timecreated, m.userid, m.message, u.firstname, u.lastname "
            . "FROM {up1_courseboard_memo} m JOIN {user} u  ON (m.userid = u.id) "
            . "WHERE ( courseid = ? ) "
            . "ORDER BY timecreated DESC ";
    $memos = $DB->get_recordset_sql($sql, array($crsid));

    foreach ($memos as $memo) {
        $row = new html_table_row();
        $row->cells[0] = new html_table_cell(userdate($memo->timecreated, '%Y-%m-%d %H:%M:%S'));
        $row->cells[1] = new html_table_cell($memo->firstname . ' ' . $memo->lastname);
        $row->cells[2] = new html_table_cell($memo->message);
        $table->data[] = $row;
    }
    $memos->close();
    echo html_writer::table($table);
}

/**
 * add a user memo in the table up1_courseboard_memo
 * @global type $USER
 * @global moodle_database $DB
 * @param integer $crsid
 * @param string $message
 * @return integer or false
 */
function add_memo($crsid, $message) {
    global $USER, $DB;

    $memo = new stdClass();
    $memo->timecreated = time();
    $memo->userid = $USER->id;
    $memo->courseid = $crsid;
    $memo->message = $message;
    return $DB->insert_record('up1_courseboard_memo', $memo);
}


/**
 * print html table of administration logs for a course (creation, validation...)
 * @global type $DB
 * @param int $crsid
 * @param bool $brief
 */
function print_admin_log($crsid, $brief=true) {
    global $DB;

    $table = new html_table();
    $table->classes = array('logtable', 'generalbox');
    $table->align = array('right', 'left', 'left');
    $table->head = array(
        get_string('time'),
        get_string('fullnameuser'),
        get_string('action'),
        get_string('info')
    );

    $legacyrows = array();
    if ( ! $brief ) {
        $legacyrows =  array( new html_table_row(['<b>Logs historiques</b>', '', '', '' ]) );
        $legacyrows = array_merge($legacyrows, table_course_logs(get_course_log_legacy($crsid, false)));        
    }
    $records = get_course_log_standard($crsid);
    $detailed = ! $brief; // en première approximation : quand afficher les "infos" de logstore_standard ?
    $table->data = array_merge(table_course_logs($records, $detailed), $legacyrows);

    echo html_writer::table($table);
}

/**
 * turn log records into table rows
 * @param type $records
 * @return array \html_table_row
 */
function table_course_logs($records, $detailed=false) {
    $data = array();
    foreach ($records as $record) {
        $row = new html_table_row(array(
            userdate($record->timecreated, '%Y-%m-%d %H:%M:%S'),
            $record->firstname . ' ' . $record->lastname,
            $record->component . ' ' . $record->action,
            $record->info . ' '
                . ($detailed ? print_r(unserialize($record->other), true) : '')
            ));
        $data[] = $row;
    }
    return $data;
}

/**
 * get logs from legacy storage (pre-2.7)
 * @param integer $crsid
 * @param bool $brief
 * @return array records
 */
function get_course_log_legacy($crsid, $brief=true) {
    global $DB;

    $sqllong = "SELECT l.id, time as timecreated, userid, module as component, action, info, '' as other, u.firstname, u.lastname "
            . "FROM {log} l JOIN {user} u  ON (l.userid = u.id)"
            . "WHERE ( ( module = 'course' AND action = 'new' AND info LIKE '%ID " . $crsid . "%' ) "
            . "     OR ( module = 'course' AND action != 'view' AND action != 'login' AND course = ? ) "
            . "     OR (module IN ('course_validate', 'crswizard') AND course = ?)  ) "
            . "ORDER BY time DESC LIMIT 20";
    $sqlbrief = "SELECT l.id, time as timecreated, userid, module as component, action, info, '' as other, u.firstname, u.lastname "
            . "FROM {log} l JOIN {user} u  ON (l.userid = u.id) "
            . "WHERE ( module='crswizard' AND course = ?) "
            . "ORDER BY time DESC LIMIT 10";
    $sql = ($brief ? $sqlbrief : $sqllong);
    $logs = $DB->get_records_sql($sql, array($crsid, $crsid));
    return $logs;
}

/**
 * get logs from standard storage (post-2.7)
 * @param integer $crsid
 * @param bool $brief
 * @return array records
 */
function get_course_log_standard($crsid, $brief=true) {
    global $DB;

    $sql = "SELECT l.id, l.timecreated, userid, component, CONCAT(eventname, ' ', action) as action, '' as info, other, u.firstname, u.lastname "
            . "FROM {logstore_standard_log} l JOIN {user} u  ON (l.userid = u.id) "
            . "WHERE (crud != 'r' AND contextlevel <= 100 AND courseid = ?) "
            . "ORDER BY timecreated DESC " . ($brief ? 'LIMIT 10' : '');
    $logs = $DB->get_records_sql($sql, array($crsid));
    return $logs;
}