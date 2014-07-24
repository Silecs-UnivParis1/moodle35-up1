<?php

define('NO_OUTPUT_BUFFERING', true);
require('../../config.php');
require_once(dirname(__DIR__) . '/coursehybridtree/locallib.php');

$node = optional_param('node', '/cat0', PARAM_RAW);

$tree = CourseHybridTree::createTree($node);
if (!$tree) {
    die("Node '$node' not found!");
}

header('Content-Type: application/json; charset="UTF-8"');
echo json_encode($tree->listJqtreeChildren());
