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
        return $children;
    }

    function toHtmlTree() {
        return "<span>ROF {$this->name}</span>";
    }
}
