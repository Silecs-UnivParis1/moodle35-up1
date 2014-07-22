<?php
/* @var $DB moodle_database */

class ChtNodeCategory extends ChtNode
{
    private $catid; // Moodle id from course_categories

    static function buildFromCategoryId($catid) {
        global $DB;
        $record = $DB->get_record('category', array('id' => (int) $catid));
        return self::buildFromCategory($record);
    }

    static function buildFromCategory($record) {
        $new = new self;
        $new->name = $record->name;
        $new->code = $record->idnumber;
        $new->catid = $record->id;
        $new->absolutePath = str_replace('/', '/cat', $record->path);
        return $new;
    }

    /**
     * Initialize the paths from the parent node.
     *
     * @param ChtNode $parent
     * @return \ChtNodeCategory
     */
    function initPath($parent) {
        $this->path = $parent->getPath() . '/cat' . $this->catid;
        $this->absolutePath = $parent->getAbsolutePath() . '/cat' . $this->catid;
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
            $this->addCategoryChildren();
            // if it contains directly courses (rare)...
            $this->addCourseChildren();
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
            return false; // true
        } else {
            return false;
        }
    }

    /**
     * @todo Code this!
     */
    private function addCategoryChildren() {
        // ...
        $children[] = ChtNodeCategory::buildFromCategoryId($id)
               ->initPath($this->path);
    }

    /**
     * @todo Code this!
     */
    private function addCourseChildren() {
        // ...
        $children[] = ChtNodeCourse::buildFromCourseId($courseId)
                ->initPath($this->path);
    }
}


