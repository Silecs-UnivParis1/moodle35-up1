<?php
require_once __DIR__ . '/testlib.php';

// Test final : parcourir une catégorie de niveau 4 et vérifier si un noeud existe tel qu'attendu
// cat=11 02-Economie / Licence économie
$node = ChtNodeCategory::buildFromCategoryId(11);
ok('02', '===', $node->getComponent(), "component");

ok_contains(
        "UP1-PROG39308",
        function ($expected, $value) { return ($expected === $value->code); },
        $node->listChildren(),
        "UP1-PROG39308 is a ROF child of this category"
);
ok_contains(
        "UP1-PROG9490",
        function ($expected, $value) { return ($expected === $value->code); },
        $node->listChildren(),
        "UP1-PROG39308 is a ROF child of this category"
);
ok(2, '===', count($node->listChildren()), "# children");



// Test intermédiaire : 2 noeuds créés à la main, association manuelle et test des héritages (profondeur, paths...)

$node4 = ChtNodeCategory::buildFromCategoryId(11);
$node5 = ChtNodeRof::buildFromRofId('UP1-PROG39308');
$node5->SetParent($node4);
$node6 = ChtNodeRof::buildFromRofId('UP1-PROG24870');
$node6->SetParent($node5);

echo "Node cat11 (depth 4)\n";
ok('02', '===', $node4->getComponent(), "component");
ok(4, '===', $node4->getAbsoluteDepth(), "abs depth");
ok(1, '===', $node4->getDepth(), "depth");


echo "Node UP1-PROG39308 (depth 5)\n";
ok('02', '===', $node5->getComponent(), "component");
ok(11, '==', $node5->getCatid(), "catid");
ok('/cat11/02:UP1-PROG39308', '===', $node5->getPath(), "path");
ok('/cat4/cat5/cat10/cat11/02:UP1-PROG39308', '===', $node5->getAbsolutePath(), "abs path");
ok($node4->getDepth() + 1, '===', $node5->getDepth(), "depth");
ok($node4->getAbsoluteDepth() + 1, '===', $node5->getAbsoluteDepth(), "abs depth");
ok('/02/UP1-PROG39308', '===', $node5->getRofPathId(), "rofpathid");

echo "Node UP1-PROG24870 (depth 6)\n";
ok('02', '===', $node6->getComponent(), "component");
ok('/cat11/02:UP1-PROG39308/UP1-PROG24870', '===', $node6->getPath(), "path");
ok('/cat4/cat5/cat10/cat11/02:UP1-PROG39308/UP1-PROG24870', '===', $node6->getAbsolutePath(), "abs path");
ok($node5->getDepth() + 1, '===', $node6->getDepth(), "depth");
ok($node5->getAbsoluteDepth() + 1, '===', $node6->getAbsoluteDepth(), "abs depth");
ok('/02/UP1-PROG39308/UP1-PROG24870', '===', $node6->getRofPathId(), "rofpathid");

echo "Done\n";
