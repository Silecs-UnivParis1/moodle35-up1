<?php
require_once __DIR__ . '/testlib.php';

$node = ChtNodeCourse::buildFromCourseId(4);
ok('ChtNodeCourse', '===', get_class($node), "Classe");

ok('<Nom du cours>', '===', $node->name, "name");
ok('id?', '===', $node->code, "code");

$node->initPath();
ok('', '===', $node->getPath(), "path");
ok('', '===', $node->getAbsolutePath(), "apath");
ok('', '===', $node->getPseudoPath(), "ppath");

$children = $node->listChildren();
ok(0, '===', count($children), "children count");

$html = $node->toHtmlTree();
ok(
        '/^<span\b/',
        function($a,$b) { return preg_match($a, $b); },
        $html,
        "HTML starts with span"
);
file_put_contents(__DIR__ . '/output/course.html', $html);
