<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');

class course_wizard_step_model extends moodleform {
    function definition() {
        global $OUTPUT, $SESSION;

        $mform = $this->_form;

        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 1);

        $mform->addElement('header', 'general', "Vous souhaitez");

        $course_model = wizard_get_course_model_list();
        if (count($course_model)) {
            $course_model_list = $course_model['model_name'];
            $course_summary = $course_model['model_summary'];
            if (count($course_model_list)) {
                $m1array = array();
                $m1array[] = $mform->CreateElement('radio', 'modeletype', '', '<span class="fake-fitemtitle">Créer un nouvel EPI</span>', 'selm1');
                $m1array[] = $mform->CreateElement('select', 'selm1', '', $course_model_list);
                $m1array[] = $mform->CreateElement('select', 'course_summary', '',  $course_summary, array('class' => 'cache'));

                $mform->addGroup($m1array, 'm1array', "", array(' '), false);
                $mform->disabledIf('selm1', 'modeletype', 'neq', 'selm1');
            }
        } else {
            throw new moodle_exception('La catégorie des cours modèles est vide. L\'assistant ne peut pas fonctionner.');
        }

        $course_list_teacher = wizard_get_course_list_teacher();
        if (count($course_list_teacher)) {
            if (count($course_list_teacher)) {
                $mform->addElement('radio', 'modeletype', '', "<span class='fake-fitemtitle'>Dupliquer l'un de vos EPI</span>"
				   . "<div class='indented-block-top' style='margin-left: 0.5em;'>" . get_string('blocHelp1SModel', 'local_crswizard') . "</div>", 'selm2');
                $mform->addElement('select', 'selm2', '', $course_list_teacher,  array(
                    'class' => 'transformIntoSubselects boitex',
                ));
                $mform->disabledIf('selm2', 'modeletype', 'neq', 'selm2');

                $mform->addElement('html', '<div id="bb_duplication" class="fitem femptylabel">');
                $mform->addElement('html', '<div class="fitemtitle"><label></label></div>');
                $mform->addElement('html', '<div class="felement fsubmit">');
                $mform->addElement('html', '<span class="fake-fitemtitle">'
                    . '<input type="submit" value="Duplication rapide" id="id_stepgo_22" name="stepgo_22" >'
                    . '</span>');
                $mform->addElement('html', '<div class="indented-block-top" style="margin-left: 0.5em;">');
                $mform->addElement('html', get_string('blocHelp2SModel', 'local_crswizard'));
                $mform->addElement('html', '</div>');
                $mform->addElement('html', '</div>');
                $mform->addElement('html', '</div>');
            }
        }

        $mform->setDefault('modeletype', 'selm1');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => 0)),
            get_string('previousstage', 'local_crswizard'), array('class' => 'previousstage')
        );
        $buttonarray[] = $mform->createElement('submit', 'stepgo_2', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            $this->validation_category($data, $errors);
        }
        return $errors;
    }

    private function validation_category($data, &$errors) {

        if ($data['modeletype'] == 'selm2' && ($data['selm2'] == 0) ) {
            $errors['selm2'] = 'Veuillez sélectionner une période et un cours';
        }
        return $errors;
    }
}
