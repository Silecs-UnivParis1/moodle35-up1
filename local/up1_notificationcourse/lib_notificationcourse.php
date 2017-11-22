<?php
/**
 * @package    local
 * @subpackage up1_notificationcourse
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * construit l'objet $message contenant le sujet et le corps de message version texte et html
 * @param string $subject
 * @return object $message
 */
function get_notificationcourse_message($formdata, $params) {
    $message = new object();
    $message->type = $formdata->message;
    $message->from = $params['shortnamesite'];
    if ($formdata->message && $formdata->message==1) {
        $message->subject = $formdata->msgrelancesubject;
        $message->body = $formdata->msgrelancebody;
        $message->info = get_string('word_relance', 'local_up1_notificationcourse');
    } else {
        $message->subject = $formdata->msginvitationsubject;
        $message->body = $formdata->msginvitationbody;
        $message->info = get_string('word_invitation', 'local_up1_notificationcourse');
    }

    $message->subject = str_replace('[[siteshortname]]', $params['shortnamesite'], $message->subject);
    $message->subject = str_replace('[[courseshortname]]', $params['shortnamecourse'], $message->subject);
    $message->subject = str_replace('[[activitename]]', $params['nomactivite'], $message->subject);
    $message->body = str_replace('[[sender]]', $params['user'], $message->body);
    $message->body = str_replace('[[courseshortname]]', $params['shortnamecourse'], $message->body);
    $message->body = str_replace('[[activitename]]', $params['nomactivite'], $message->body);

    $message->bodyhtml = $message->body ;
    $message->body = str_replace('[[linkactivity]]', $params['urlactivite'], $message->body);
    $message->body = str_replace('[[linkcourse]]', $params['urlcourse'], $message->body);
    $message->bodyhtml = str_replace('[[linkactivity]]', '<a href="' . $params['urlactivite'] . '>'
        . $params['urlactivite'] . '</a>', $message->bodyhtml);
    $message->bodyhtml = str_replace('[[linkcourse]]', '<a href="' . $params['urlcourse'] . '>'
        . $params['urlcourse'] . '</a>', $message->bodyhtml);

    $message->bodyhtml = str_replace("\n", '<br />', $message->bodyhtml);
    return $message;
}

/**
 * Interpète les paramètres du message et retourne le résultat
 * @param string $msg
 * @param array $params
 * @return string $msg
 */
function get_notificationcourse_message_interface($msg, $params) {
    $msg = str_replace('[[siteshortname]]', $params['shortnamesite'], $msg);
    $msg = str_replace('[[courseshortname]]', $params['shortnamecourse'], $msg);
    $msg = str_replace('[[activitename]]', $params['nomactivite'], $msg);
    $msg = str_replace('[[sender]]', $params['user'], $msg);
    return $msg;
}

/**
 * construit le messsage d'interface du nombre et de la qualité des
 * destinataires du message
 * @param int $nbdest
 * @param string $availability
 * @param array $msgbodyinfo
 * @return string $label
 */
function get_label_destinataire($nbdest, $availability, $msgbodyinfo) {
    $label = '';
    if ($nbdest == 0) {
        return get_string('norecipient', 'local_up1_notificationcourse');
    }
    $label = 'Nombre d\'utilisateurs ';
    if ($availability) {
        $label .= ' concernés par <a href="' . $msgbodyinfo['urlactivite']
            . '">' . $msgbodyinfo['nomactivite'] . '</a> : ';
    } else {
        $label .= ' inscrits à cet espace : ';
    }
    $label .= $nbdest;
    return $label;
}

/**
 * renvoie les utilisateurs ayant le rôle 'rolename'
 * dans le cours $course
 * @param object $course $course
 * @param string $rolename shortname du rôle
 * @return array de $user
 */
function get_users_from_course($course, $rolename='') {
    global $DB;
    $coursecontext = context_course::instance($course->id);
    $allIdUsers = array();

    if ($rolename == '') {
        return get_enrolled_users($coursecontext);
    } else {
        $rolestudent = $DB->get_record('role', array('shortname'=> $rolename));
        $allIdUsers = get_users_from_role_on_context($rolestudent, $coursecontext);
    }

    if (count($allIdUsers) == 0) {
        return $allIdUsers;
    }
    $ids = '';
    foreach ($allIdUsers as $sc) {
        $ids .= $sc->userid . ',';
    }
    $ids = substr($ids, 0, -1);
    $sql = "SELECT * FROM {user} WHERE id IN ({$ids})";
    $allUsers = $DB->get_records_sql($sql);

    return $allUsers;
}

/**
 * Envoi une notification aux $users + copie à $USER
 * @param array $idusers
 * @param object $msg
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string : message interface
 */
function send_notificationcourse($users, $msg, $infolog) {
    global $USER;
    $nb = 0;
    foreach ($users as $user) {
        $res = notificationcourse_send_email($user, $msg);
        if ($res) {
            ++$nb;
        }
    }

    if (isset($infolog['copie'])) {
        notificationcourse_send_email($USER, $msg);
        $infolog['copie'] = 1;
    }
    $infolog['nb'] = $nb;
    $infolog['typemsg'] = $msg->info;

    return get_result_action_notificationcourse($infolog);
}

/**
 * construit le message d'interface après l'envoi groupé de notification
 * @param array $infolog informations pour le log pour les envois de mails
 * @return string message interface
 */
function get_result_action_notificationcourse($infolog) {
    if ($infolog['nb'] == 0) {
        return get_string('nomessagesend', 'local_up1_notificationcourse');
    }
    if ($infolog['typemsg'] == 'invitation') {
        $message = get_string('numbernotificationinvitation', 'local_up1_notificationcourse', $infolog['nb']);
    } else {
        $message = get_string('numbernotificationrelance', 'local_up1_notificationcourse', $infolog['nb']);
    }

    if (isset($infolog['copie'])) {
        $message .= get_string('copie', 'local_up1_notificationcourse') . $infolog['userfullname'];
    }

    return $message;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param object $msg
 * @return false ou resultat de la fonction email_to_user()
 **/
function notificationcourse_send_email($user, $msg) {
    if (!isset($user->email) && empty($user->email)) {
        return false;
    }
    $emailform = $msg->from;
    return email_to_user($user, $emailform, $msg->subject, $msg->body, $msg->bodyhtml);
}


/**
 * construit le
 * @param array $msgbodyinfo
 * @param string $type
 * return string
 */
function get_email_body($msgbodyinfo, $type) {
    $res = '';
    $coursename = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    $a = new stdClass();
    $a->sender = $msgbodyinfo['user'];
    $a->linkactivity = $msgbodyinfo['nomactivite'];
    $a->linkcourse = $msgbodyinfo['shortnamecourse'] . ' - ' . $msgbodyinfo['fullnamecourse'];
    if ($type == 'html') {
        $a->linkactivity = html_writer::link($msgbodyinfo['urlactivite'], $msgbodyinfo['nomactivite']);
        $a->linkcourse = html_writer::link($msgbodyinfo['urlcourse'], $coursename);
    }
    $res .= get_string('msgsender', 'local_up1_notificationcourse', $a);
    return $res;
}

/**
 * Construit le chemin categories > cours
 * @param array $categories tableau de tableaux
 * @param object $course
 * @return string $path
 */
function get_pathcategories_course($categories, $course) {
    $path ='';
    $tabcat = array();
    if (count($categories)) {
        foreach ($categories as $category) {
            $tabcat[$category->depth] = $category->name;
        }
        ksort($tabcat);
        foreach ($tabcat as $cat) {
            $path .= $cat . ' > ';
        }
    }
    $path .= $course->shortname;
    return $path;
}

/**
 * renvoie les identifiant des inscrits n'ayant pas consulté la ressource
 * @param int $instanceid
 * @param object courseContext $courseContext
 * @return array
 */
function get_not_viewer_yet($instanceid, $courseContext) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/report/participation/locallib.php");

    $logtable = report_participation_get_log_table_name();
    $usernamefields = get_all_user_name_fields(true, 'u');

    list($relatedctxsql, $params) = $DB->get_in_or_equal($courseContext->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'relatedctx');
    $params['instanceid'] = $instanceid;
    $params['timefrom'] = 0;
    $params['edulevel'] = core\event\base::LEVEL_PARTICIPATING;
    $params['contextlevel'] = CONTEXT_MODULE;

    list($crudsql, $crudparams) = report_participation_get_crud_sql('');
    $params = array_merge($params, $crudparams);

    $sql = "SELECT ra.userid, $usernamefields, u.idnumber, COUNT(DISTINCT l.timecreated) AS vue
        FROM {user} u
        JOIN {role_assignments} ra ON u.id = ra.userid AND ra.contextid $relatedctxsql
        LEFT JOIN {" . $logtable . "} l
            ON l.contextinstanceid = :instanceid
            AND l.timecreated > :timefrom" . $crudsql ."
            AND l.edulevel = :edulevel
            AND l.anonymous = 0
            AND l.contextlevel = :contextlevel
            AND (l.origin = 'web' OR l.origin = 'ws')
            AND l.userid = ra.userid";


    $groupbysql = " GROUP BY ra.userid, $usernamefields, u.idnumber";
    $sql .= $groupbysql;
    $sql .= " having vue = 0";

    $users = $DB->get_records_sql($sql, $params);
    return array_keys($users);
}

//old
/**
 * construit le sujet du mail envoyé
 * @param string $siteshortname
 * @param string $courseshortname
 * @param string $activitename
 * @return string
 */
function get_email_subject($siteshortname, $courseshortname, $activitename) {
    $subject = '';
    $subject .='['. $siteshortname . '] '. get_string('notification', 'local_up1_notificationcourse')
        . $courseshortname . ' - ' . $activitename;
    return $subject;
}
