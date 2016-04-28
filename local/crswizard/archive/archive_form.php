<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class wizard_archive_form extends moodleform {
    function definition() {

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('html', html_writer::tag('div', get_string('archivecourseexplain', 'local_crswizard'), array('class' => 'fitem')));
        $mform->addElement('text','confirmation', 'Confirmation','maxlength="3" size="3"');
        $mform->addRule('confirmation', get_string('archiveconfirmationmsg', 'local_crswizard'), 'required', null, 'client');
        $mform->setType('confirmation', PARAM_TEXT);
        $mform->addElement('submit', 'archive', get_string('archivecoursebutton', 'local_crswizard')
            . ' ' . $this->_customdata['shortname']);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            $this->validation_confirmation($data, $errors);
        }
        return $errors;
    }

    private function validation_confirmation($data, &$errors) {
        $tabresp = array('oui', 'non');
        $reponse = strtolower(trim($data['confirmation']));
        if ( in_array($reponse, $tabresp) == FALSE) {
            $errors['confirmation'] = get_string('archiveconfirmationmsg', 'local_crswizard');
        }
        return $errors;
    }
}
