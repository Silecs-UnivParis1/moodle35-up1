<?php

require_once($CFG->dirroot . "/local/roftools/roflib.php");

class ChtNodeRof extends ChtNode
{
    //private $id; // UP1-PROG... or UP1-C...
    protected $flag = '(R) ';

    /**
     * @param string $path A path where the root is a category, and the end the rofid.
     * @return ChtNodeRof
     */
    static function buildFromPath($path) {
        $new = new self();
        return $new->setPath($path)->readName();
    }

    /**
     * @param string $rofid
     * @return ChtNodeRof
     */
    static function buildFromRofId($rofid) {
        $new = new self;
        $new->code = $rofid;
        $new->id = $rofid;
        return $new->readName();
    }

    public function readName() {
        list($record, ) = rof_get_record($this->id);
        $this->name = $record->name;
        return $this;
    }

    public function setPath($path) {
        $this->path = $path;
        $rofpath = preg_replace('#^(/cat\d+)+#', '', $path);
        $m = array();
        if (preg_match('#^/(\d+):.*?([^/]+)$#', $rofpath, $m)) {
            $this->component = $m[1];
            $this->id = preg_replace('/^\d+:/', '', $m[2]);
            $this->code = $this->id;
        } else {
            die("Error reading path.");
        }
        return $this;
    }

    /**
     * Initialize the paths from the parent node.
     *
     * @param ChtNode $parent (optionally null if no parent, as for debugging)
     * @return \ChtNodeCategory
     */
    function setParent($parent) {
        parent::setParent($parent);
        $this->component = $parent->getComponent();
        $suffix = '/' . $this->id;
        if ($parent instanceof ChtNodeCategory) {
            $suffix = '/' . $this->component .':'. $this->id;
            // instead of pseudopath, insert component into first rofpath component
        }
        $this->path = $parent->getPath() . $suffix;
        $this->absolutePath = $parent->getAbsolutePath() . $suffix;
        return $this;
    }

    function getComponent() {
        if ($this->component != '00' and $this->component != NULL) {
            return $this->component;
        } else {
            throw new moodle_exception('Component should be defined for NodeRof ' . $this->id);
        }
    }

    function getRofPathId() {
        if (preg_match('@/cat\d+/(\d+:.*)$@', $this->path, $matches)) {
            return '/' . str_replace(':', '/', $matches[1]);
        }
    }

    function getCatid() {
       if (preg_match('@/cat(\d+)/\d+:@', $this->path, $matches)) {
            return (int)$matches[1];
         }
    }

    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        $this->addRofChildren($this->getRofPathId(), courselist_roftools::get_courses_from_parent_rofpath($this->getRofPathId()));
        $this->addCourseChildren(courselist_roftools::get_courses_from_parent_rofpath($this->getRofPathId(), false));
        
        // ROF entries are sorted using their name, to cope with eg. "semestre N" 
        usort($this->children, function ($a, $b) { // compare nodes : Courses last, else by name
            $dira = (int) ($a instanceof ChtNodeCourse);
            $dirb = (int) ($b instanceof ChtNodeCourse);
            if ($dira === $dirb) {
                return strcmp(strtolower($a->name), strtolower($b->name));
            } else {
                return ($dira < $dirb ? -1 : 1);
            }
        } );

        if ($this->getAbsoluteDepth() > 7) {
            foreach ($this->children as $pos => $child) {
                if ($child instanceof ChtNodeRof) {
                    $subchildren = $child->listChildren();
                    if (count($subchildren) === 1 && $subchildren[0] instanceof ChtNodeCourse) {
                        $this->children[$pos] = $subchildren[0];
                    }
                }
            }
        }

        return $this->children;
    }

    /**
     * list all the *descendant* courses (not only direct children)
     *
     * @return array of courseid
     */
    function listDescendantCourses() {
        $courses = courselist_roftools::get_courses_from_parent_rofpath($this->getRofPathId());
        return array_keys($courses);
    }

    /**
     * @return boolean
     */
    private function isHybrid() {
        if (
                count($this->children) > 1
                && $this->children[0] instanceof ChtNodeCourse
                && $this->children[1] instanceof ChtNodeRof
                ) {
            return true;
        }
        return false;
    }
}
