<?php

require_once($CFG->dirroot . "/local/roftools/roflib.php");

class ChtNodeRof extends ChtNode
{

    //private $id; // UP1-PROG... or UP1-C...

    /**
     * @param string $rofid
     * @return ChtNodeRof
     */
    static function buildFromRofId($rofid) {
        list($record, ) = rof_get_record($rofid);
        // $table = rof_get_table($rofid);
        $new = new self;
        $new->name = $record->name;
        $new->code = $rofid;
        $new->id = $rofid;
        return $new;
    }

    /**
     * Initialize the paths from the parent node.
     *
     * @param ChtNode $parent (optionally null if no parent, as for debugging)
     * @return \ChtNodeCategory
     */
    function setParent($parent) {
        $this->component = $parent->getComponent();
        $suffix = '/' . $this->id;
        if ($parent instanceof ChtNodeCategory) {
            $suffix = '/' . $this->component .':'. $this->id;
            // instead of pseudopath, insert component into first rofpath component
        }
        $this->path = $parent->getPath() . $suffix;
        $this->absolutePath = $parent->getAbsolutePath() . $suffix;
        $this->component = $parent->getComponent();
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
        if (preg_match('@^/cat\d+/cat\d+/cat\d+/cat\d+/(.*)$@', $this->absolutePath, $matches)) {
            return '/' . str_replace(':', '/', $matches[1]);
        }
    }

    function getCatid() {
       if (preg_match('@^/cat\d+/cat\d+/cat\d+/cat(\d+)/@', $this->absolutePath, $matches)) {
            return (int)$matches[1];
         }
    }


    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        $this->addRofChildren($this->getRofPathId(), courselist_roftools::get_courses_from_parent_rofpath($this->getRofPathId()));
        $this->addCourseChildren($this->getCatid());
        return $this->children;
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
