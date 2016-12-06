<?php
/**
 * @package    local_up1_notificationcourse
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $ADMIN admin_root */

defined('MOODLE_INTERNAL') || die;

if (has_capability('moodle/site:config', context_system::instance())) {
    $settings = new admin_settingpage('local_resourcenotif', get_string('pluginname', 'local_up1_notificationcourse'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading('label_invitation', 'Invitation de consultation', ''));
    $invitationSubject = new admin_setting_configtext(
            'invitation_subject',
            get_string('subject', 'local_up1_notificationcourse'),
            get_string('descriptionsujet', 'local_up1_notificationcourse'),
            get_string('default_invitation_sujet', 'local_up1_notificationcourse'),
            PARAM_NOTAGS);
    $invitationSubject->plugin = 'local_up1_notificationcourse';
    $settings->add($invitationSubject);

    $descriptionInvitationMessage = get_string('descriptionmsg', 'local_up1_notificationcourse');
    $defaultInvitationMessage = get_string('default_invitation_message', 'local_up1_notificationcourse');
    $invitationMessage = new admin_setting_configtextarea('invitation_body',
        get_string('body', 'local_up1_notificationcourse'),
        $descriptionInvitationMessage,
        $defaultInvitationMessage);
    $invitationMessage->plugin = 'local_up1_notificationcourse';
    $settings->add($invitationMessage);

    $settings->add(new admin_setting_heading('label_consultation', 'Relance de consultation', ''));

    $relanceSubject = new admin_setting_configtext(
            'relance_subject',
            get_string('subject', 'local_up1_notificationcourse'),
            get_string('descriptionsujet', 'local_up1_notificationcourse'),
            get_string('default_relance_sujet', 'local_up1_notificationcourse'),
            PARAM_NOTAGS);
    $relanceSubject->plugin = 'local_up1_notificationcourse';
    $settings->add($relanceSubject);

    $descriptionRelanceMessage = get_string('descriptionmsg', 'local_up1_notificationcourse');
    $defaultRelanceMessage = get_string('default_relance_message', 'local_up1_notificationcourse');
    $relanceMessage = new admin_setting_configtextarea('relance_body',
        get_string('body', 'local_up1_notificationcourse'),
        $descriptionRelanceMessage,
        $defaultRelanceMessage);
    $relanceMessage->plugin = 'local_up1_notificationcourse';
    $settings->add($relanceMessage);
}
