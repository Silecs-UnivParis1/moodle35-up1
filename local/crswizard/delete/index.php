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
 * Edit course settings
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laters
 */

require_once('../../../config.php');
require_once('../../../course/lib.php');
require_once('../lib_wizard.php');
require_once('../libaccess.php');
require_once('delete_form.php');


global $CFG, $PAGE, $OUTPUT, $USER;

require_login();

$id = required_param('id', PARAM_INT);

if (empty($id)) {
    print_error('invalidcourseid');
}

$pageparams = array('id'=>$id);

$PAGE->set_url('/local/crswizard/delete/index.php', $pageparams);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

if (! wizard_has_delete_course($course->id, $USER->id) ) {
    throw new moodle_exception('Vous n\'avez pas la permission d\'accéder à cette page.');
}

require_login($course);
$coursecontext = context_course::instance($course->id);
$PAGE->set_context($coursecontext);

$courseshortname = $course->shortname;

$deletecourse = get_string("deletingcourse", "", $courseshortname);
$PAGE->navbar->add($deletecourse);

$site = get_site();
$PAGE->set_title("$site->shortname: $deletecourse");
$PAGE->set_heading($site->fullname);

$title = html_writer::tag('h2', "Supprimer l'espace de cours $courseshortname");

$mform = new wizard_delete_form(null, array('shortname' => $courseshortname));
$newformdata = array('id'=>$id);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($formdata) {
    if (strtolower(trim($formdata->confirmation)) == 'oui') {
        $categorycontext = context_coursecat::instance($course->category);
        $PAGE->set_context($categorycontext);

        echo $OUTPUT->header();
        $strdeletingcourse = get_string("deletingcourse", "", $courseshortname);
        echo $OUTPUT->heading($strdeletingcourse);
        $res = delete_course($course);
        if ($res) {
            //si cours non validé
            $nbsum = $DB->count_records('crswizard_summary', array('courseid' => $course->id));
            if ($nbsum > 0) {
                $DB->delete_records('crswizard_summary', array('courseid' => $course->id));
            }
            echo $OUTPUT->heading( get_string("deletedcourse", "", $courseshortname) );
        } else {
            echo '<p>Un problème a eu lieu.</p>';
        }
        echo $OUTPUT->continue_button($CFG->wwwroot);

    } else {
        echo $OUTPUT->header();
        echo $title;
        $urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;
        echo "<p>Le cours n'a pas été supprimé.</p>";
        echo '<p><a href="' . $urlcourse . '">Retour au cours</a></p>';
    }
} else {
    echo $OUTPUT->header();
    echo $title;
    $mform->display();
}

echo $OUTPUT->footer();
