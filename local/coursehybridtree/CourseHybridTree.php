<?php
class CourseHybridTree
{

}

abstract class ChtNode
{
    public $name;
    public $code;

    protected $path;

    protected $absolutePath;

    protected $children = null;

    /**
     * Depth from the root node.
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
     * Path from the root of Moodle.
     */
    function getAbsolutePath() {
        return $this->absolutePath;
    }

    /**
     * The part of the absolute path from the last Moodle category.
     */
    function getPseudopath() {
        /**
         * @todo absolutepath -> pseudopath
         */
        return "/cat...";
    }

    /**
     * @return array of ChtNode
     */
    abstract function listChildren();

    abstract function toHtmlTree();
}