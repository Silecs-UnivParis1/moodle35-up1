<?php
/* @var $DB moodle_database */

class ChtNodeCategory extends ChtNode
{
    private $id;

    static function buildFromCategoryId($id) {
        global $DB;
        $record = $DB->get_record('category', array('id' => (int) $id));
        return self::buildFromCategory($record);
    }

    static function buildFromCategory($record) {
        $new = new self;
        $new->name = $record->name;
        $new->code = $record->idnumber;
        $new->id = $record->id;
        $new->absolutePath = str_replace('/', '/cat', $record->path);
        return $new;
    }

    function initPath($parentPath) {
        $this->path = $parentPath . '/cat' . $this->id;
        return $this;
    }

    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        if ($this->hasRofChildren()) {
            // ...
        } else {
            // ...
            $children[] = ChtNodeCategory::buildFromCategoryId($id)
                    ->initPath($this->path);

            // if it contains directly courses (rare)...
            $children[] = ChtNodeCourse::buildFromCourseId($courseId)
                    ->initPath($this->path);
        }
        return $children;
    }

    function toHtmlTree($recursive=false) {
        $html = "<span>{$this->name}</span>";

        if ($recursive) {
            foreach ($this->listChildren() as $child) {
                $html .= $child->toHtmlTree();
            }
        }
        return $html;
    }

    /**
     * @return boolean If True, children will be found through ROF instead of Moodle Cat.
     */
    private function hasRofChildren() {
        if ($this->getDepth() == 4) {
            //...
        }
    }
}


