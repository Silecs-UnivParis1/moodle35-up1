<?php

/*
 * This class (and descendants) purpose is to implement the specifications given at
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

abstract class ChtNode
{
    public $name;
    public $code; // generally, Moodle idnumber
    
    protected $component; // '00' or "composante" coded on 2 digits (01 to 37 ...)
    protected $path;
    protected $absolutePath;

    protected $id;

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
        return count(explode('/', $this->path)) - 1; // first item is empty
    }

    /**
     * Depth from the root node of the tree (not the absolute depth).
     *
     * @return int
     */
    function getAbsoluteDepth() {
        return count(explode('/', $this->absolutePath)) - 1; // first item is empty
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

    /**
     * Serialize.
     *
     * @return string
     */
    public function serialize() {
        $o = new stdClass();
        foreach (array('name', 'code', 'component', 'path', 'absolutePath', 'id') as $attr) {
            $o->$attr = $this->$attr;
        }
        $o->class = get_class($this);
        return json_encode($o);
    }

    /**
     * Return a new instance from a plain object.
     *
     * @param Stdclass $data
     * @return \static
     */
    static public function unserialize($data) {
        $new = new static;
        foreach (array('name', 'code', 'component', 'path', 'absolutePath', 'id') as $attr) {
            $new->$attr = $data->$attr;
        }
        return $new;
    }

    /**
     * @return array Cf http://mbraak.github.io/jqTree/
     */
    public function listJqtreeChildren() {
        $children = array();
        foreach ($this->listChildren() as $node) {
            /* @var $node ChtNode */
            $children[] = array(
                'id' => $node->serialize(),
                'label' => $node->name,
                'load_on_demand' => true,
                'depth' => $node->getDepth(),
            );
        }
        return $children;
    }

    /**
     * simple echo method
     * @param boolean $printPath
     */
    function toPrint($printPath=false) {
        echo "[$this->code] $this->name  ";
        if ($printPath) {
            echo $this->getAbsolutePath();
        }
        echo "\n";
    }

    /**
     * @param string $code
     * @return ChtNode
     */
    function findChild($code) {
        foreach ($this->listChildren() as $child) {
            if ($child->code === $code) {
                return $child;
            }
        }
        return null;
    }

    /**
     * add Rof children
     *
     * @param string $parentRofpath
     * @param array $rofcourses
     */
    protected function addRofChildren($parentRofpath, $rofcourses) {
        $targetRofDepth = count(explode('/', $parentRofpath));
        $potentialNodes = array();

        foreach ($rofcourses as $rofpathid) {
            /**
             * On ne filtre pas par parentrofpath ?
             */
            $potentialNodePath = array_slice($this->pathArray($rofpathid), 0, $targetRofDepth);
            $potentialNode = $potentialNodePath[$targetRofDepth - 1];
            $potentialNodes[] = $potentialNode;
        }
        foreach (array_unique($potentialNodes) as $rofid) {
            $this->children[] = ChtNodeRof::buildFromRofId($rofid)
                    ->setParent($this);
        }
    }

    /**
     * add direct courses children
     *
     * @param integer $catid category.id
     */
    protected function addCourseChildren($catid) {
        $courses = courselist_cattools::get_descendant_courses($catid);
        list(, $catcourses) = courselist_roftools::split_courses_from_rof($courses, $this->component);
        /** @TODO factorize this result ? */
        foreach ($catcourses as $crsid) {
            $this->children[] = ChtNodeCourse::buildFromCourseId($crsid)
                ->setParent($this);
        }
    }
}

