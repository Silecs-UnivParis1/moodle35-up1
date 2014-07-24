<?php
class ChtNodeCourse extends ChtNode
{
    //private $id; // integer ID from the course DB.

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
    function setParent($parent=null) {
        if ($parent) {
            $this->path = $parent->getPath() . '/' . $this->id;
            $this->absolutePath = $parent->getAbsolutePath() . '/' . $this->id;
        } else {
            $this->path = '/' . $this->id;
            $this->absolutePath = '/' . $this->id;
        }
        return $this;
    }

    function getComponent() {
        if ($this->component != '00' and $this->component != NULL) {
            return $this->component;
        } else {
            throw new moodle_exception('Component should be defined for NodeCourse ' . $this->id);
        }
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
}
