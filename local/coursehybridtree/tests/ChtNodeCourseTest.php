<?php
require_once __DIR__ . '/testlib.php';

$node = ChtNodeCourse::buildFromCourseId(4);
ok('ChtNodeCourse', '===', get_class($node), "Classe");

ok('Mathématiques 1 - indépendant', '===', $node->name, "name");
ok('', '===', $node->code, "code");

$node->setParent();
ok('/4', '===', $node->getPath(), "path");
ok('/4', '===', $node->getAbsolutePath(), "abs path");
ok('/4', '===', $node->getPseudoPath(), "pseudopath");
// ok('00', '===', $node->getComponent(), 'component'); //this must trigger an exception


// 2 tests supplémentaires sur le Pseudopath en "tordant" le node car il n'est pas réaliste
$node->setAbsolutePath('/cat1/cat2/cat3/01/PROG-1/123');
ok('/cat3/01/PROG-1/123', '===', $node->getPseudopath(), "pseudopath");

$node->setAbsolutePath('/cat1/01/PROG-1/123');
ok('/cat1/01/PROG-1/123', '===', $node->getPseudopath(), "pseudopath");


$children = $node->listChildren();
ok(0, '===', count($children), "children count");

echo "Done\n";
