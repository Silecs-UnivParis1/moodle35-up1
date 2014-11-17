<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(dirname(__DIR__) . '/coursehybridtree/locallib.php');

$PAGE->set_context(context_system::instance());

$node = optional_param('node', '/cat0', PARAM_RAW);
$debug = optional_param('debug', false, PARAM_BOOL);

$tree = CourseHybridTree::createTree($node);
$tree->debug = $debug;
if (!$tree) {
    die("Node '$node' not found!");
}

header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($tree->listJqtreeChildren());
