<?php

/*
 * This class (and descendants) purpose is to implement the specifications given at
 * http://paris1-dev.silecs.info/wiki/doku.php/catalogue_des_cours:arbre_des_cours?&#consignes
 * and commented on http://tickets.silecs.info/mantis/view.php?id=2163
 */


class CourseHybridTree
{

}

abstract class ChtNode
{
    public $name;
    public $code; // generally, Moodle idnumber

    protected $path;

    protected $absolutePath;

    /**
     * @var array children nodes
     */
    protected $children = null;

    /**
     * Depth from the root node of the tree (not the absolute depth).
     *
     * @return int
     */
    function getDepth() {
        /**
         * todo count('/')-1
         */
        return 3;
    }

    /**
     * Path from the root of the tree.
     */
    function getPath() {
        return $this->path;
    }

    /**
     * Path from the root of Moodle (not internal to Moodle).
     */
    function getAbsolutePath() {
        return $this->absolutePath;
    }

    /**
     * set absolute path ; FOR TESTING AND DEBUGGING ONLY
     * @param string $path
     */
    function setAbsolutePath($path) {
        $this->absolutePath = $path;
    }

    /**
     * The part of the absolute path from the last Moodle category (included).
     */
    function getPseudopath() {
        $arrAbsolutePath = $this->pathArray($this->absolutePath);
        $last = '';
        $lastindex = 0;
        foreach ($arrAbsolutePath as $index => $pathItem) {
            if ( preg_match('/cat[0-9]+/', $pathItem) ) {
                $last = $pathItem;
                $lastindex = $index;
            } else {
                break;
            }
        }
        $pseudoPath = '/' . implode('/', array_slice($arrAbsolutePath, $lastindex ));
        return $pseudoPath;
    }

    /**
     * This helper function returns a "clean" simple array from a path
     * @param string $path "/comp1/comp2/comp3..."
     */
    function pathArray($path) {
        return array_values(array_filter(explode('/', $path)));
    }

    /**
     * @return array of ChtNode
     */
    abstract function listChildren();

    abstract function toHtmlTree();
}
