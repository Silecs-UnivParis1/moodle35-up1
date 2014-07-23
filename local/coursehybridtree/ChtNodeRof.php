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
        $record = rof_get_record($rofid);
        // $table = rof_get_table($rofid);
        $new = new self;
        $new->name = $record->name;
        $new->code = $rofid;
        $new->rofid = $rofid;
        return $new;
    }


    /**
     * Depth from the root node of the tree (not the absolute depth).
     *
     * @return int
     */
    function getDepth() {
        return 0; // @todo
    }


    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        /**
         * @todo
         */
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
}
