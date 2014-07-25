<?php
require_once __DIR__ . '/testlib.php';

echo "\nCatégorie de niveau 4\n";
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


echo "\nCatégorie de niveau 3\n";
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



echo "\nCatégorie de niveau 1\n";
$node = ChtNodeCategory::buildFromCategoryId(4);
ok('ChtNodeCategory', '===', get_class($node), "Classe");

ok('/cat4', '===', $node->getPath(), "path");
ok('/cat4', '===', $node->getAbsolutePath(), "abs path");
ok('/cat4', '===', $node->getPseudoPath(), "pseudopath");
ok(1, '===', $node->getDepth(), "depth");
ok(1, '===', $node->getAbsoluteDepth(), "abs depth");
ok('00', '===', $node->getComponent(), "component");

$children = $node->listChildren();
// print_r($children);
ok(1, '===', count($children), "children count");



echo "\nDescend l'arbre (en profondeur)\n";
$child1 = $node->findChild("2:UP1");
ok(null, '!==', $child1, "Level 1 child");
$child2 = $child1->findChild("3:02");
ok(null, '!==', $child2, "Level 2 child");
$child3 = $child2->findChild("4:02/Licences");
ok(null, '!==', $child3, "Level 3 child {$child3->code} {$child3->getPath()}");
$child4 = $child3->findChild("UP1-PROG39308");
ok(null, '!==', $child4, "Level 4 child {$child4->code} {$child4->getPath()}");
ok(2, '===', count($child4->listChildren()), "Level 5 of size >=2");
ok(null, '!==', $child4->findChild("UP1-PROG24870"), "Level 5 child1");
ok(null, '!==', $child4->findChild("UP1-PROG25562"), "Level 5 child2");
//print_r($child4);

echo "\nSous-catégories\n";
$cat5 = ChtNodeCategory::buildFromCategoryId(5);
ok(3, '===', count($cat5->listChildren()), "3 children in cat5");
ok(null, '!==', $cat5->findChildById(6), "cat6 child of cat5 (exists)");
ok('ChtNodeCategory', '===', get_class($cat5->findChildById(6)), "cat6 child of cat5 (class)");

echo "Done\n";
