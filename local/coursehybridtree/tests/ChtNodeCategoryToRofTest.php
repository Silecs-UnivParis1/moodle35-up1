<?php
require_once __DIR__ . '/testlib.php';

// Test final : parcourir une catégorie de niveau 4 et vérifier si un noeud existe tel qu'attendu
// cat=11 02-Economie / Licence économie
$node = ChtNodeCategory::buildFromCategoryId(11);
$children = $node->listChildren();

// TODO code the remaining




// Test intermédiaire : 2 noeuds créés à la main, association manuelle et test des héritages (profondeur, paths...)

$node4 = ChtNodeCategory::buildFromCategoryId(11);
$node5 = ChtNodeRof::buildFromRofId('UP1-PROG39308');
$node5->SetParent($node4);

ok(2, '===', $node5->getDepth(), "depth");
ok(5, '===', $node5->getAbsoluteDepth(), "abs depth");
ok('/02/UP1-PROG39308', '===', $node5->getRofPathId(), "rofpathid");
ok('02', '===', $node5->getComponent(), "component");

