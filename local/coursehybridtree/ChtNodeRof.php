<?php

require_once($CFG->dirroot . "/local/roftools/roflib.php");

class ChtNodeRof extends ChtNode
{

    private $rofid; // UP1-PROG... or UP1-C...
    // private $component; // to be defined ?

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
        $new->rofid = $rofid;
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
        $suffix = '/' . $this->rofid;
        if ($parent instanceof ChtNodeCategory) {
            $suffix = '/' . $this->component .':'. $this->rofid;
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
            throw new moodle_exception('Component should be defined for NodeRof ' . $this->rofid);
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
        $this->addRofChildren();
        $this->addCourseChildren();
        return $this->children;
    }

    function toHtmlTree() {
        if ($this->isHybrid()) {
            // ...

        } else {
            return sprintf("<span>ROF %s</span>", htmlspecialchars($this->name));
        }
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


    /**
     * add direct courses children
     */
    private function addCourseChildren() {
        $courses = courselist_cattools::get_descendant_courses($this->getCatid());
        list($rofcourses, $catcourses) = courselist_roftools::split_courses_from_rof($courses, $this->component);
        /** @TODO factorize this result ? */
        foreach ($catcourses as $crsid) {
// echo "crsid = $crsid \n";
            $this->children[] = ChtNodeCourse::buildFromCourseId($crsid)
                ->setParent($this);
        }

    }

    /**
     * add Rof children (general case)
     */
    private function addRofChildren() {
        $parentRofpath = $this->getRofPathId();
        $rofcourses = courselist_roftools::get_courses_from_parent_rofpath($parentRofpath);
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



}
