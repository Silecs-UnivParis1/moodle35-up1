<?php
require_once __DIR__ . '/testlib.php';

// Test final : parcourir une catégorie de niveau 4 et vérifier si un noeud existe tel qu'attendu
// cat=11 02-Economie / Licence économie
$node = ChtNodeCategory::buildFromCategoryId(11);
$children = $node->listChildren();

// TODO code the remaining



