<?php

/*
 * This class (and descendants) purpose is to implement the specifications given at
 * http://paris1-dev.silecs.info/wiki/doku.php/catalogue_des_cours:arbre_des_cours?&#consignes
 * and commented on http://tickets.silecs.info/mantis/view.php?id=2163
 */

require_once($CFG->dirroot . '/report/up1reporting/locallib.php');

abstract class ChtNode
{
    public $name;
    public $code; // generally, Moodle idnumber
    public $debug = false; // boolean, display debug information at the beginning of each label
    public $stats = false; // boolean, display statistics information next to each entry (manager reporting)
    // reporting links are limited upon depth
    public $reportdepthmin = 3; // ufr-composante
    public $reportdepthmax = 6; // semestre

    protected $flag = '(N) ';
    protected $component; // '00' or "composante" coded on 2 digits (01 to 37 ...)
    protected $path;
    protected $absolutePath;

    protected $id;

    private $debugMessages = array(); // array of debugging messages for this node

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
     *
     * @return string
     */
    function getPath() {
        return $this->path;
    }

    /**
     * Path from the root of Moodle (not internal to Moodle).
     *
     * @return string
     */
    function getAbsolutePath() {
        return $this->absolutePath;
    }

    /**
     * set absolute path ; FOR TESTING AND DEBUGGING ONLY
     *
     * @param string $path
     */
    function setAbsolutePath($path) {
        $this->absolutePath = $path;
    }

    /*
     * @param ChtNode $parent
     * @return ChtNode chainable
     */
    public function setParent($parent) {
        $this->debug = $parent->debug;
        $this->stats = $parent->stats;
        return $this;
    }

    /*
     * @param string $msg
     * @return ChtNode chainable
     */
    public function addDebugMessage($msg) {
        $this->debugMessages[] = $msg;
        return $this;
    }

    /**
     * The part of the absolute path from the last Moodle category (included).
     *
     * @return string
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
     * This helper function returns a "clean" simple array of "dirs" in a path
     *
     * @param string $path "/comp1/comp2/comp3..."
     * @return array ["comp1", "comp2", "comp3"]
     */
    function pathArray($path) {
        return array_values(array_filter(explode('/', $path)));
    }

    /**
     * @return array of ChtNode
     */
    abstract function listChildren();

     /**
     * list all the *descendant* courses (not only direct children)
     *
     * @return array of courseid
     */
    abstract function listDescendantCourses();

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
     * @return ChtNode
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
        $nodeChildren = $this->listChildren();
        foreach ($nodeChildren as $node) {
            /* @var $node ChtNode */
            $info = ($this->debug ?
                    "<span class=\"coursetree-info\">{$node->flag} [id={$node->id} {$node->code}] </span>"
                    : '');
            $child = array(
                'id' => $node->serialize(),
                'label' => $info . $node->getLabel(),
                'load_on_demand' => ( ! ($node instanceof ChtNodeCourse) ),
                'depth' => $node->getDepth(),
            );
            if ($this->debug) {
                $lastMessages = array("pseudopath=" . $node->getPseudopath());
                $child['debugMessages'] = array_merge($lastMessages, $node->debugMessages);
            }
            $children[] = $child;
        }
        return $children;
    }

    /*
     * @return string
     */
    protected function getLabel() {
        $res =  '<span class="coursetree-dir">' . htmlspecialchars($this->name) . '</span>' ;
        if ($this->stats) {
            $res .= '<span class="coursetree-stats">';
            foreach (array_values($this->getStats()) as $column) {
                $res .= '<span>' . $column .' </span> ';
            }
            $res .= '</span>';
        }
        return $res;
    }

    /**
     * provides entries statistics, for each node of the tree
     * @return type
     */
    protected function getStats() {
        $teachroles = array('editingteacher' => 'Enseignants', 'teacher' => 'Autres intervenants' );
        $absdepth = $this->getAbsoluteDepth();
        $courses = $this->listDescendantCourses();
        $reportlink = '';
        // lien Reporting
        if ($absdepth >= $this->reportdepthmin && $absdepth <= $this->reportdepthmax
                && !($this instanceof ChtNodeCourse) ) {
            $url = new moodle_url('/report/up1reporting/exportcsv.php', array('node' => $this->getAbsolutePath()));
            $reportlink = html_writer::link($url, '[R]', array('title' => 'Reporting CSV'));
            //** @todo icon (table) instead of [R] ?
        }
        $res = array(
            'Cours' => count($courses),
            'Étudiants' => count_roles_from_courses(array('student' => "Étudiants"), $courses),
            'Enseignants' => count_roles_from_courses($teachroles, $courses),
            'Reporting' => $reportlink,
        );
        return $res;
    }

    /**
     * simple echo method for CLI
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
            if ($child->id == $id) {
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

        foreach ($rofcourses as $rofpathids) {
            foreach ($rofpathids as $rofpathid) {
                $potentialNodePath = array_slice($this->pathArray($rofpathid), 0, $targetRofDepth);
                if (isset($potentialNodePath[$targetRofDepth - 1])) {
                    $potentialNodes[] = $potentialNodePath[$targetRofDepth - 1];
                }
            }
        }
        foreach (array_unique($potentialNodes) as $rofid) {
            $this->addDebugMessage("addRofChildren() PARENT $rofid");
            $this->children[] = ChtNodeRof::buildFromRofId($rofid)
                    ->setParent($this)
                    ->addDebugMessage("addRofChildren() CHILD ($rofid)");
        }
    }

    /**
     * add direct courses children
     *
     * @param array $courses Format [DBrecord]  ou  [ id => rof ] (used by roftools and such).
     */
    protected function addCourseChildren($courses) {
        foreach ($courses as $crsid => $data) {
            $this->addDebugMessage("addCourseChildren() PARENT $crsid");
            if (is_object($data)) {
                $this->children[] = ChtNodeCourse::buildFromCourse($data)
                    ->setParent($this)
                    ->addDebugMessage("addCourseChildren() CHILD ({$data->id})");
            } else {
                $this->children[] = ChtNodeCourse::buildFromCourseId($crsid)
                    ->setParent($this)
                    ->addDebugMessage("addCourseChildren() CHILD {$crsid}");
            }
        }
    }
}
