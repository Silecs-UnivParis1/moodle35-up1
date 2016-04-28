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
require_once('../../../admin/tool/up1_batchprocess/locallib.php');
require_once('../../../admin/tool/up1_batchprocess/batch_lib.php');
require_once('../../../admin/tool/up1_batchprocess/batch_libactions.php');

require_once('archive_form.php');


global $CFG, $PAGE, $OUTPUT, $USER;

require_login();

$id = required_param('id', PARAM_INT);

if (empty($id)) {
    print_error('invalidcourseid');
}

$pageparams = array('id'=>$id);

$PAGE->set_url('/local/crswizard/archive/index.php', $pageparams);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

if (! wizard_has_edit_course($course->id, $USER->id) ) {
    throw new moodle_exception('Vous n\'avez pas la permission d\'accéder à cette page.');
}

require_login($course);
$coursecontext = context_course::instance($course->id);
$PAGE->set_context($coursecontext);

$courseshortname = $course->shortname;
$archivecourse = get_string('archivingcourse', 'local_crswizard', $courseshortname);
$PAGE->navbar->add($archivecourse);

$site = get_site();
$PAGE->set_title("$site->shortname: $archivecourse");
$PAGE->set_heading($site->fullname);

$title = html_writer::tag('h2', "Archiver l'espace de cours $courseshortname");

$msg = '';
$prefix = default_prefix();
$isodate = isoDate();

$defaultRoles = ['from' => 'editingteacher', 'to' => 'ens_epi_archive'];
$roles = [];
foreach ($defaultRoles as $key => $role) {
    if ($DB->record_exists('role', array('shortname' => $role))) {
        $roles[$key] = $DB->get_field('role', 'id', array('shortname' => $role));
    }
}

$substRole = "Substituer les rôles \"enseignant éditeur\" par \"enseignant EPI archivé\". "
    . "C'est-à-dire que vous pourrez toujours accéder à cet EPI et le dupliquer, mais vous ne pourrez plus le modifier.";
if (count($roles) < 2) {
    $substRole = '<strike>' . $substRole . '</strike>';
    if (isset($roles['to']) == FALSE) {
        $substRole .= '<br /><b>Le rôle "enseignant EPI archivé" n\'exite pas.</b>';
    }
    if (isset($roles['from']) == FALSE) {
        $substRole .=  '<br /><b>Le rôle "enseignant éditeur" n\'exite pas.</b>';
    }
}
$msgEffect = html_writer::tag('div', "Cette action n'est pas réversible. Elle conduira à :", array('class' => 'fitem'));
$msgEffect .= html_writer::start_tag('ul', array('class'=>'list'));
$msgEffect .= html_writer::tag('li', "Fermer \"$courseshortname\", donc les etudiant.e.s n'y auront plus accès.");
$msgEffect .= html_writer::tag('li', "Préfixer le nom de l'EPI avec \"$prefix\".");
$msgEffect .= html_writer::tag('li', $substRole);
$msgEffect .= html_writer::tag('li', "Indiquer la date du jour comme date d'archivage.");
$msgEffect .= html_writer::tag('li', "Désactiver les inscriptions des étudiant.e.s, c'est-à-dire que, "
    . "pour les étudiant.e.s, cet EPI n'apparaîtra plus dans la liste des EPI auxquels elles/ils sont inscrit.e.s.");
$msgEffect .= html_writer::end_tag('ul');

$msgEffect .= html_writer::tag('div', " (Toutes ces opérations seront appliquées courant juillet à l'ensemble des EPI, "
    . "sauf ceux qui sont déjà archivés et ceux dont le calendrier ne correspond pas à l'année universitaire.)");


$mform = new wizard_archive_form(null, array('shortname' => $courseshortname));
$newformdata = array('id'=>$id);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

$header = $OUTPUT->header();

if ($formdata) {

    $urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;
    $linkcourse = html_writer::tag('a', 'Retour au cours', array('href'=> $urlcourse));

    if (strtolower(trim($formdata->confirmation)) == 'oui') {
        $categorycontext = context_coursecat::instance($course->category);
        $PAGE->set_context($categorycontext);

        $myCourse = [$course];
        //fermer
        $msg .= batchaction_visibility($myCourse, 0, false) . "<br />\n";
        //prefixe archive...
        $msg .= batchaction_prefix($myCourse, $prefix, false) . "<br />\n";
        //substituer le role enseignant à "enseignant EPI archivé"
        if (count($roles) == 2) {
            $msg .= batchaction_substitute($myCourse, $roles['from'], $roles['to'], false) . "<br />\n";
        } else {
            $msg .= 'Attention, la substitution "enseignant éditeur" par "enseignant EPI archivé" n\'a pas eu lieu' . "<br />\n";
        }
        //archiver à la date
        $tsdate = isoDateToTs($isodate);
        $msg .= batchaction_archdate($myCourse, $tsdate, false) . "<br />\n";
        //Désactiver les inscriptions (sauf manuelles)
        $msg .= batchaction_disable_enrols($myCourse, false, array('manual'), false) . "<br />\n";

        echo $header;
        echo $title;
        echo html_writer::tag('div', $msg, array('class' => 'fitem'));
        echo html_writer::tag('div', $linkcourse, array('class'=>'list'));

    } else {
        echo $header;
        echo $title;
        echo html_writer::tag('div', 'Le cours n\'a pas été archivé.', array('class'=>'list'));
        echo html_writer::tag('div', $linkcourse, array('class'=>'list'));
    }
} else {
    echo $header;
    echo $title;
    echo $msgEffect;
    $mform->display();
}

echo $OUTPUT->footer();
