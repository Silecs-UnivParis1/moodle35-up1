<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(dirname(__DIR__) . '/coursehybridtree/locallib.php');

$PAGE->set_context(context_system::instance());

$node = optional_param('node', '/cat0', PARAM_RAW);
$debug = optional_param('debug', false, PARAM_BOOL);
$stats = optional_param('stats', false, PARAM_BOOL);

$tree = CourseHybridTree::createTree($node);

header('Content-Type: application/json; charset="UTF-8"');
if (!$tree) {
    $error = array(
        array(
            "id" => "err" . $node,
            "label" => "Erreur de chargement de '$node'...",
            "load_on_demand" => 0,
            "depth" => 1,
        )
    );
    echo json_encode($error);
} else {
    $tree->debug = $debug;
    $tree->stats = $stats;
    echo json_encode($tree->listJqtreeChildren());
}
