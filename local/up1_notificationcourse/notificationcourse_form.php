<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//It must be included from a Moodle page

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class local_up1_notificationcourse_notificationcourse_form extends moodleform {
    public function definition() {

        $mform =& $this->_form;
        $params = $this->_customdata;

        // hidden elements
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'mod');
        $mform->setType('mod', PARAM_ALPHA);

        $radioarray = array();
        $radioarray[] = $mform->createElement('radio', 'destinataire', '', get_string('item_all', 'local_up1_notificationcourse')
            . ' (' . $this->_customdata['nbNotifiedStudents'] . ')', 0);
        $radioarray[] = $mform->createElement('radio', 'destinataire', '', get_string('item_viewed', 'local_up1_notificationcourse')
            . ' (' . $this->_customdata['nbViewedstudents'] . ')', 1);
        $radioarray[] = $mform->createElement('radio', 'destinataire', '', get_string('item_notviewed', 'local_up1_notificationcourse')
            . ' (' . $this->_customdata['nbNotviewedstudents'] . ')', 2);
        $mform->addGroup($radioarray, 'radioar', get_string('label_destinataire', 'local_up1_notificationcourse'), array(' ', ' '), false);

        $msgarray = array();
        $msgarray[] = $mform->createElement('radio', 'message', '', get_string('item_invitation', 'local_up1_notificationcourse') , 0);
        $msgarray[] = $mform->createElement('radio', 'message', '', get_string('item_relance', 'local_up1_notificationcourse'), 1);
        $mform->addGroup($msgarray, 'msgar', get_string('label_message', 'local_up1_notificationcourse'), array(' ', ' '), false);

        $mform->addElement('text', 'msginvitationsubject', get_string('subject', 'local_up1_notificationcourse'), 'maxlength="254" size="80" class="obligatoire"');
        $mform->setType('msginvitationsubject', PARAM_MULTILANG);
        $mform->setDefault('msginvitationsubject', get_notificationcourse_message_interface(
            get_config('local_up1_notificationcourse','invitation_subject'),
            $params)
        );

        $mform->addElement('textarea', 'msginvitationbody', get_string('body', 'local_up1_notificationcourse'), array('rows' => 15,
            'cols' => 80));
        $mform->setType('msginvitationbody', PARAM_TEXT);
        $mform->setDefault('msginvitationbody', get_notificationcourse_message_interface(
            get_config('local_up1_notificationcourse','invitation_body'),
            $params)
        );

        $mform->addElement('text', 'msgrelancesubject', get_string('subject', 'local_up1_notificationcourse'), 'maxlength="254" size="80"');
        $mform->setType('msgrelancesubject', PARAM_MULTILANG);
        $mform->setDefault('msgrelancesubject', get_notificationcourse_message_interface(
            get_config('local_up1_notificationcourse','relance_subject'),
            $params)
        );

        $mform->addElement('textarea', 'msgrelancebody', get_string('body', 'local_up1_notificationcourse'), array('rows' => 15,
            'cols' => 80));
        $mform->setType('msgrelancebody', PARAM_TEXT);
        $mform->setDefault('msgrelancebody', get_notificationcourse_message_interface(
            get_config('local_up1_notificationcourse','relance_body'),
            $params)
        );

        $mform->addElement('checkbox', 'copie', get_string('label_copie', 'local_up1_notificationcourse'));

        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true,  get_string('submit', 'local_up1_notificationcourse'));
    }
}
