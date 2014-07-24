<?php
require_once __DIR__ . '/testlib.php';

echo "\n Catégorie de niveau 4\n";
$node = ChtNodeCategory::buildFromCategoryId(11);
ok('ChtNodeCategory', '===', get_class($node), "Classe");
ok('Licences', '===', $node->name, "name");
ok('4:02/Licences', '===', $node->code, "code");

ok('/cat11', '===', $node->getPath(), "path");
ok('/cat4/cat5/cat10/cat11', '===', $node->getAbsolutePath(), "abs path");
ok('/cat11', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");
ok(4, '===', $node->getAbsoluteDepth(), "abs depth");
ok('02', '===', $node->getComponent(), "component");


echo "\n Catégorie de niveau 3\n";
$node = ChtNodeCategory::buildFromCategoryId(10);
ok('ChtNodeCategory', '===', get_class($node), "Classe");
ok('02-Economie', '===', $node->name, "name");
ok('3:02', '===', $node->code, "code");

ok('/cat10', '===', $node->getPath(), "path");
ok('/cat4/cat5/cat10', '===', $node->getAbsolutePath(), "abs path");
ok('/cat10', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");
ok(3, '===', $node->getAbsoluteDepth(), "abs depth");
ok('02', '===', $node->getComponent(), "component");

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


echo"\n Catégorie de niveau 1 \n";
$node = ChtNodeCategory::buildFromCategoryId(4);
ok('ChtNodeCategory', '===', get_class($node), "Classe");

ok('Année 2013-2014', '===', $node->name, "name");
ok('1:2013-2014', '===', $node->code, "code");

ok('/cat4', '===', $node->getPath(), "path");
ok('/cat4', '===', $node->getAbsolutePath(), "abs path");
ok('/cat4', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");
ok(1, '===', $node->getAbsoluteDepth(), "abs depth");
ok('00', '===', $node->getComponent(), "component");

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


echo "\n Descend l'arbre (en profondeur)\n";
while ($node->listChildren()) {
    echo "\n";
    $cnodes = $node->listChildren();
    $cnodes[0]->toPrint(false);
    print_r($cnodes[0]);
    $node = $cnodes[0];
}