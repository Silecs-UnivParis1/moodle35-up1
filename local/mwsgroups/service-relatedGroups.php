<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(__DIR__ . '/lib.php');

$up1code = required_param('up1code', PARAM_RAW);  //ex. "0934B05,0938B05"
$callback = optional_param('callback', '', PARAM_ALPHANUMEXT); // if set, use jsonp instead of json

$PAGE->set_context(context_system::instance());

$u_g = new mws_search_groups();
$u_g->up1code = $up1code;
$res = array(
    'groups' => $u_g->search_related_groups(),
);

if (empty($callback)) {
    header('Content-Type: application/json; charset="UTF-8"');
    echo json_encode($res);
} else {
    header('Content-Type: application/javascript; charset="UTF-8"');
    echo $callback . '(' . json_encode($res) . ');';
}

