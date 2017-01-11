<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_once('lib_notificationcourse.php');
require_once('notificationcourse_form.php');

$id = required_param('id', PARAM_INT);
$moduletype = required_param('mod', PARAM_ALPHA);

if (! $cm = get_coursemodule_from_id($moduletype, $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $module = $DB->get_record($moduletype, array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

require_login($course, false, $cm);
$modcontext = context_module::instance($cm->id);
require_capability('moodle/course:manageactivities', $modcontext);

$url = new moodle_url('/local/up1_notificationcourse/notificationcourse.php', array('mod' => $moduletype, 'id'=>$id));
$PAGE->set_url($url);

$site = get_site();

$msgresult = '';
$infolog = array();
$infolog['courseid'] = $cm->course;
$infolog['cmid'] = $cm->id;
$infolog['cmurl'] = $url;
$infolog['userid'] = $USER->id;

$courseContext = context_course::instance($course->id);

$urlcourse = $CFG->wwwroot . '/course/view.php?id='.$course->id;
$urlactivite = $CFG->wwwroot . '/mod/' . $moduletype . '/view.php?id=' . $cm->id;

$coursepath = get_pathcategories_course($PAGE->categories, $course);

$mailsubject = get_email_subject($site->shortname, $course->shortname, format_string($cm->name));


$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($module->name));
$PAGE->requires->css(new moodle_url('/local/up1_notificationcourse/notificationcourse.css'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js_init_code(file_get_contents(__DIR__ . '/js/include-for-notificationcourse-form.js'));

$recipicents = '';
$students = get_users_from_course($course);
$notifiedStudents = [];

$modinfo = get_fast_modinfo($course)->get_cm($cm->id);
$info = new \core_availability\info_module($modinfo);
$notifiedStudents = $info->filter_user_list($students);
$nbNotifiedStudents = count($notifiedStudents);

//destinataires
$notifiedStudentsKeys = array_keys($notifiedStudents);
$clefs = get_not_viewer_yet($cm->id, $courseContext);
$notviewedstudents =  array_intersect($notifiedStudentsKeys, $clefs);
$viewedstudents = array_diff($notifiedStudentsKeys, $notviewedstudents);

$params = array(
    'user' => $USER->firstname . ' ' . $USER->lastname,
    'shortnamesite' => $site->shortname,
    'urlactivite' => $urlactivite,
    'nomactivite' => format_string($cm->name),
    'coursepath' => $coursepath,
    'urlcourse' => $urlcourse,
    'shortnamecourse' => $course->shortname,
    'fullnamecourse' => $course->fullname,
    'nbNotifiedStudents' => $nbNotifiedStudents,
    'nbNotviewedstudents' => count($notviewedstudents),
    'nbViewedstudents' => count($viewedstudents)
);

if ($nbNotifiedStudents == 0) {
    $recipicents = get_string('norecipient', 'local_up1_notificationcourse');
} else {
    $recipicents = get_label_destinataire($nbNotifiedStudents, $cm->availability, $params);
}

$mform = new local_up1_notificationcourse_notificationcourse_form(null, $params);

$newformdata = array('id'=>$id, 'mod' => $moduletype);
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect($urlcourse);
}

if ($formdata) {
    if (isset($formdata->copie)) {
        $infolog['copie'] = 1;
        $infolog['userfullname'] = fullname($USER);
    }
    // select and construct msg
    $msg = get_notificationcourse_message($formdata, $params);

    if (isset($formdata->destinataire)) {
        if ($formdata->destinataire == '0') {
            $msgresult = send_notificationcourse($notifiedStudents, $msg, $infolog);
        } elseif ($formdata->destinataire == '1') {
            $viewedstudentsflip = array_flip($viewedstudents);
            $resultviewedstudents = array_intersect_key($notifiedStudents, $viewedstudentsflip);
            $msgresult = send_notificationcourse($resultviewedstudents, $msg, $infolog);

        } elseif($formdata->destinataire == '2') {
            $notviewedstudentsflip = array_flip($notviewedstudents);
            $result = array_intersect_key($notifiedStudents, $notviewedstudentsflip);
            $msgresult = send_notificationcourse($result, $msg, $infolog);
        } else {
            $msgresult = 'Aucun message envoyé : un problème a eu lieu lors de la sélection du destinataire';
        }
    } else {
        $msgresult = 'Aucun message envoyé : un problème a eu lieu lors de la sélection du destinataire';
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sendnotification', 'local_up1_notificationcourse'));

if ($msgresult != '') {
    echo $OUTPUT->box_start('info');
    echo $msgresult;
    echo html_writer::tag('p', html_Writer::link($urlcourse, get_string('returncourse', 'local_up1_notificationcourse')));
    echo $OUTPUT->box_end();
} else {
    echo html_writer::tag('p', $recipicents, array('class' => 'notificationlabel'));
    $senderlabel = html_writer::tag('span', get_string('sender', 'local_up1_notificationcourse'), array('class' => 'notificationgras'));
    $sender = $site->shortname . ' &#60;'. $CFG->noreplyaddress . '&#62;';
    echo html_writer::tag('p', $senderlabel . $sender, array('class' => 'notificationlabel'));
    $mform->display();
}
echo $OUTPUT->footer();
