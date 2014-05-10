<?php

class ChtNodeCourse extends ChtNode
{
    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        /**
         * @todo
         */
        return $children;
    }

    function toHtmlTree() {
        return "<span>COURSE {$this->name}</span>";
    }
}
