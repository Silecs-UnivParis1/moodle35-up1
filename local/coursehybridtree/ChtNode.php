<?php

/*
 * This class (and descendants) purpose is to implement the specifications given at
 * http://paris1-dev.silecs.info/wiki/doku.php/catalogue_des_cours:arbre_des_cours?&#consignes
 * and commented on http://tickets.silecs.info/mantis/view.php?id=2163
 */

abstract class ChtNode
{
    public $name;
    public $code; // generally, Moodle idnumber

    protected $flag = '(N) ';
    protected $info = true; // prefix label with additional information for debugging
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
                'label' => ($this->info ? $node->flag . '[' . $node->id . ' ' . $node->code . '] ' : '') . $node->name,
                'load_on_demand' => ( ! ($node instanceof ChtNodeCourse) ),
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
     * @param string $id
     * @return ChtNode
     */
    function findChildById($id) {
        foreach ($this->listChildren() as $child) {
            if ($child->id === $id) {
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
            if (isset($potentialNodePath[$targetRofDepth - 1])) {
                $potentialNode = $potentialNodePath[$targetRofDepth - 1];
                $potentialNodes[] = $potentialNode;
            }
        }
        foreach (array_unique($potentialNodes) as $rofid) {
            $this->children[] = ChtNodeRof::buildFromRofId($rofid)
                    ->setParent($this);
        }
    }

    /**
     * add direct courses children
     *
     * @param array $courses Format [DBrecord]  ou  [ id => rof ] (used by roftools and such).
     */
    protected function addCourseChildren($courses) {
        foreach ($courses as $crsid => $data) {
            if (is_object($data)) {
                $this->children[] = ChtNodeCourse::buildFromCourse($data)
                ->setParent($this);
            } else {
                $this->children[] = ChtNodeCourse::buildFromCourseId($crsid)
                    ->setParent($this);
            }
        }
    }
}
