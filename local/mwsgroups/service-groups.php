<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$PAGE->set_context(context_system::instance());

$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$up1code = optional_param('up1code', '', PARAM_RAW); // ex. "0934B05,0938B05"
if ($up1code) {
    $res = array(
        'groups' => mws_search_groups::search_related_groups($up1code),
        'users' => array(),
        'related' => true
    );
} else {
    $token = required_param('token', PARAM_RAW);
    $maxrows = optional_param('maxRows', 0, PARAM_INT);
    $maxrowsfor = array(
        'users' => optional_param('userMaxRows', $maxrows, PARAM_INT),
        'groups' => optional_param('groupMaxRows', $maxrows, PARAM_INT),
    );
    $filterstudent = optional_param('filter_student', 'both', PARAM_ALPHA);
    $filtergroupcat = optional_param('filter_group_category', '', PARAM_ALPHANUMEXT);

    $u_g = new mws_search_groups();
    $u_g->token = $token;
    $u_g->usermaxrows = $maxrowsfor['users'];
    $u_g->groupmaxrows = $maxrowsfor['groups'];
    $u_g->filterstudent = $filterstudent;
    $u_g->filtergroupcat = $filtergroupcat;
    $u_g->archives = false;
    $res = array(
        'users'  => $u_g->search_users(),
        'groups' => $u_g->search_groups(),
    );
}

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

