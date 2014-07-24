<?php

/*
 * This class (and ChtNode) purpose is to implement the specifications given at
 * http://paris1-dev.silecs.info/wiki/doku.php/catalogue_des_cours:arbre_des_cours?&#consignes
 * and commented on http://tickets.silecs.info/mantis/view.php?id=2163
 */


class CourseHybridTree
{
    /**
     * Return a new instance of one of the ChtNode*.
     *
     * @param string $node
     * @return ChtNode
     */
    static public function createTree($node) {
        $m = array();
        if (preg_match('#/cat(\d+)$#', $node, $m)) {
            // root node, given through a category
            return ChtNodeCategory::buildFromCategoryId($m[1]);
        } else if (preg_match('/^{/', $node)) {
            // inside node (non-root), given through serialized attributes
            $attributes = json_decode($node);
            $class = $attributes->class;
            unset($attributes->class);
            return $class::unserialize($attributes);
        } else {
            die('{label: "An error occured: wrong node request"}');
        }
    }
}
