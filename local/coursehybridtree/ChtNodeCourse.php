<?php
class ChtNodeCourse extends ChtNode
{
    /**
     *
     * @var integer ID from the course DB.
     */
    private $id;

    /**
     * @global moodle_database $DB
     * @param integer $courseid
     * @return ChtNodeCourse
     */
    static function buildFromCourseId($courseid) {
        global $DB;
        $record = $DB->get_record('course', array('id' => (int) $courseid));
        return self::buildFromCourse($record);
    }

    /**
     * @global moodle_database $DB
     * @param object $record
     * @return ChtNodeCourse
     */
    static function buildFromCourse($record) {
        /**
         * @todo check and fix this
         */
        $new = new self;
        $new->name = $record->fullname;
        $new->code = $record->idnumber;
        $new->id = $record->id;
        return $new;
    }

    /**
     * Initialize the paths from the parent node.
     *
     * @param ChtNode $parent (opt)
     * @return \ChtNodeCourse
     */
    function initPath($parent=null) {
        if ($parent) {
            $this->path = $parent->getPath() . '/' . $this->id;
            $this->absolutePath = $parent->getAbsolutePath() . '/' . $this->id;
        } else {
            $this->path = '/' . $this->id;
            $this->absolutePath = '/' . $this->id;
        }
        return $this;
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
        return "<span>COURSE {$this->name}</span>";
    }
}
