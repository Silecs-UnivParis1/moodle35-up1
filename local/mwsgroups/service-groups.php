<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$token = required_param('token', PARAM_RAW);
$maxrows = optional_param('maxRows', 0, PARAM_INT);
$maxrowsfor = array(
    'users' => optional_param('userMaxRows', $maxrows, PARAM_INT),
    'groups' => optional_param('groupMaxRows', $maxrows, PARAM_INT),
);
$filterstudent = optional_param('filter_student', 'both', PARAM_ALPHA);
$filtergroupcat = optional_param('filter_group_category', '', PARAM_ALPHANUMEXT);
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$PAGE->set_context(context_system::instance());

$u_g = new mws_search_groups();
$u_g->token = $token;
$u_g->usermaxrows = $maxrowsfor['users'];
$u_g->groupmaxrows = $maxrowsfor['groups'];
$u_g->filterstudent = $filterstudent;
$u_g->filtergroupcat = $filtergroupcat;
$res = $u_g->search();

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

