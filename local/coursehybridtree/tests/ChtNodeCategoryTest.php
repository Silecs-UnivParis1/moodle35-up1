<?php
require_once __DIR__ . '/testlib.php';


// Catégorie de niveau 3
$node = ChtNodeCategory::buildFromCategoryId(10);
ok('ChtNodeCategory', '===', get_class($node), "Classe");

ok('02-Economie', '===', $node->name, "name");
ok('3:02', '===', $node->code, "code");

// not necessary $node->setParent(null);

ok('/cat10', '===', $node->getPath(), "path");
ok('/cat4/cat5/cat10', '===', $node->getAbsolutePath(), "abs path");
ok('/cat10', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");

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


echo "\n\n";
// Catégorie de niveau 1
$node = ChtNodeCategory::buildFromCategoryId(4);
ok('ChtNodeCategory', '===', get_class($node), "Classe");

ok('Année 2013-2014', '===', $node->name, "name");
ok('1:2013-2014', '===', $node->code, "code");

// not necessary $node->setParent(null);

ok('/cat4', '===', $node->getPath(), "path");
ok('/cat4', '===', $node->getAbsolutePath(), "abs path");
ok('/cat4', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");

$children = $node->listChildren();
// print_r($children);
ok(4, '===', count($children), "children count");

$html = $node->toHtmlTree();
ok(
        '/^<span\b/',
        function($a,$b) { return preg_match($a, $b); },
        $html,
        "HTML starts with span"
);
file_put_contents(__DIR__ . '/output/course.html', $html, FILE_APPEND);


// descend l'arbre (en profondeur)
while ($node->listChildren()) {
    echo "\n";
    $cnodes = $node->listChildren();
    print_r($cnodes[0]);
    $node = $cnodes[0];
}