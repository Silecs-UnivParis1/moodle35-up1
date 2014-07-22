<?php

class ChtNodeRof extends ChtNode
{
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
