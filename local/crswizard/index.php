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
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laters
 */
require_once('../../config.php');
require_once('../../course/lib.php');
require_once(__DIR__ . '/lib_wizard.php');
require_once(__DIR__ . '/step1_form.php');
require_once(__DIR__ . '/step2_form.php');
require_once(__DIR__ . '/step3_form.php');
require_once(__DIR__ . '/step_confirm.php');
require_once(__DIR__ . '/step_cle.php');

global $CFG, $PAGE, $OUTPUT, $SESSION;

require_login();

$systemcontext = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_url('/local/crswizard/index.php');
$PAGE->set_context($systemcontext);

//require_capability('moodle/course:request', $systemcontext);
$capcreate = use_crswizard($systemcontext);

if (!isset($_POST['stepin'])) {
    $stepin = optional_param('stepin', 1, PARAM_INT);
    $stepgo = $stepin;
    if (isset($SESSION->wizard)) {
        unset($SESSION->wizard);
    }
} else {
    $stepin = $_POST['stepin'];
    $stepgo = get_stepgo($stepin, $_POST);
}

if (isset($stepgo)) {
    $SESSION->wizard['form_step' . $stepin] = $_POST;
    switch ($stepgo) {
        case 1:
            $steptitle = get_string('selectcourse', 'local_crswizard');
            $step1form = step1_form();
            break;
        case 2:
            $steptitle = get_string('coursedefinition', 'local_crswizard');
            $editoroptions = array(
                'maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes' => $CFG->maxbytes, 'trusttext' => false, 'noclean' => true
            );
            $course = file_prepare_standard_editor(null, 'summary', $editoroptions, null, 'course', 'summary', null);
            $editform = new course_wizard_step2_form(NULL, array('editoroptions' => $editoroptions));
            break;
        case 3:
            $data = $SESSION->wizard['form_step2'];
            $errors = validation_shortname($data['shortname']);
            if (count($errors)) {
                $data['erreurs'] = $errors;
                $SESSION->wizard['form_step2'] = $data;
                $editform = new course_wizard_step2_form(NULL);
                $steptitle = get_string('coursedefinition', 'local_crswizard');
            } else {
                $steptitle = get_string('coursedescription', 'local_crswizard');
                $editform = new course_wizard_step3_form();
                if (isset($SESSION->wizard['form_step3'])) {
                    $editform->set_data((object) $SESSION->wizard['form_step3']);
                }
            }
            break;
        case 4:
			$steptitle = get_string('enrolteachers', 'local_crswizard');
			$url = '/local/crswizard/enrol/teacher.php';
			wizard_navigation(4);
			redirect(new moodle_url($url));
            break;
        case 5:
			$steptitle = get_string('enrolcohorts', 'local_crswizard');
			wizard_navigation(5);
            $url = '/local/crswizard/enrol/cohort.php';
            redirect(new moodle_url($url));
            break;
        case 6:
			$steptitle = get_string('stepkey', 'local_crswizard');
			wizard_navigation(6);
			$editform = new course_wizard_step_cle();
            break;
        case 7:
			$steptitle = get_string('confirmationtitle', 'local_crswizard');
            wizard_navigation(7);
            $editform = new course_wizard_step_confirm();
            break;
        case 8:
            // envoi message
            $corewizard = new core_wizard();
			$corewizard->create_course_to_validate();
            $messagehtml = $SESSION->wizard['form_step7']['messagehtml'];
            $message = $SESSION->wizard['form_step7']['message'];
            if (isset($SESSION->wizard['form_step7']['remarques']) && $SESSION->wizard['form_step7']['remarques'] != '') {
                $messagehtml .= '<p>La demande est accompagnée de la remarque suivante : <div>'
                        . $SESSION->wizard['form_step7']['remarques'] . '</div></p>';
                $message .= "\n" . 'La demande est accompagnée de la remarque suivante : ' . "\n"
                        . $SESSION->wizard['form_step7']['remarques'];
            }
            send_course_request($message, $messagehtml);
            unset($SESSION->wizard);
            redirect(new moodle_url('/'));
            break;
    }
}
$site = get_site();

$straddnewcourse = get_string("addnewcourse");
$PAGE->navbar->add($straddnewcourse);

$title = "$site->shortname: $straddnewcourse";
$fullname = $site->fullname;

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('wizardcourse', 'local_crswizard'));
echo $OUTPUT->heading($steptitle);

if (isset($messageInterface)) {
    echo $OUTPUT->box_start();
    echo $messageInterface;
    echo $OUTPUT->box_end();
}

if (isset($editform)) {
    $editform->display();
} elseif (isset($step1form)) {
    echo $step1form;
} else {
    echo '<p>Pas de formulaires</p>';
}

echo $OUTPUT->footer();