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
     * @param string $nodename
     * @return ChtNode
     */
    static public function createTree($nodename) {
        $m = array();
        if ($nodename === '/cat0') {
            $node = ChtNodeCategory::buildFromCategoryId(
                get_config('local_crswizard','cas2_default_etablissement')
            );
        } else if (preg_match('#^/cat(\d+)$#', $nodename, $m)) {
            // root node, given through a category ex. /cat1
            $node = ChtNodeCategory::buildFromCategoryId($m[1]);
        } else if (preg_match('#.+/cat(\d+)$#', $nodename, $m)) {
            // root node, given through a category-path ex. /cat1/cat2/cat3
            $node = ChtNodeCategory::buildFromCategoryId($m[1]);
        } else if (preg_match('#^/cat\d+/.+$#', $nodename, $m)) {
            // root node, given through a path
            $node = ChtNodeRof::buildFromPath($nodename);
        } else if (preg_match('/^{/', $nodename)) {
            // inside node (non-root), given through serialized attributes
            $attributes = json_decode($nodename);
            $class = $attributes->class;
            unset($attributes->class);
            $node = $class::unserialize($attributes);
        } else {
            die('{label: "An error occured: wrong node request"}');
        }
        return $node;
    }
}
