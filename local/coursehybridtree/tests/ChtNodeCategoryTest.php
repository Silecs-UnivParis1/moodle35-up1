<?php
require_once __DIR__ . '/testlib.php';

$node = ChtNodeCategory::buildFromCategoryId(10);
ok('ChtNodeCategory', '===', get_class($node), "Classe");

ok('02-Economie', '===', $node->name, "name");
ok('3:02', '===', $node->code, "code");

$node->initPath(null);
ok('/cat10', '===', $node->getPath(), "path");
ok('/cat4/cat5/cat10', '===', $node->getAbsolutePath(), "abs path");
ok('/cat10', '===', $node->getPseudoPath(), "pseudopath");

$children = $node->listChildren();
ok(1, '===', count($children), "children count");

$html = $node->toHtmlTree();
ok(
        '/^<span\b/',
        function($a,$b) { return preg_match($a, $b); },
        $html,
        "HTML starts with span"
);
file_put_contents(__DIR__ . '/output/course.html', $html);
