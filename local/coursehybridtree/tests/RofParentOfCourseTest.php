<?php
require_once __DIR__ . '/testlib.php';

$node = ChtNodeRof::buildFromPath("/cat11/02:UP1-PROG39308/UP1-PROG24870/UP1-C24877/UP1-C24879");
ok('ChtNodeRof', '===', get_class($node), "Class ChtNodeRof");
ok('/02/UP1-PROG39308/UP1-PROG24870/UP1-C24877/UP1-C24879', '===', $node->getRofPathId(), "RofPathId");

ok(1, '<=', count($node->listChildren()), "Count of children");
$children = $node->listChildren();
ok('ChtNodeCourse', '===', get_class($children[0]), "First child is a course");
//print_r($node);

echo "Done\n";
