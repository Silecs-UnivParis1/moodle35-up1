<?php
require_once dirname(__DIR__) . '/up1_courselist/Courselist_format.php';

class ChtNodeCourse extends ChtNode
{
    //private $id; // integer ID from the course DB.
    protected $flag = '(E) ';

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
        if (empty($record->id)) {
            // Cannot build node from an empty record.
            return null;
        }
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
        parent::setParent($parent);
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

     /**
     * list all the *descendant* courses (not only direct children), itself included
     * @return array($courseid)
     */
    function listDescendantCourses() {
        return array($this->id);
    }

    protected function getLabel() {
        global $DB;
        static $courseformatter = null;
        if (!$courseformatter) {
            $courseformatter = new courselist_format('tree');
        }
        $course = $DB->get_record('course', array('id' => (int) $this->id));
        $crslink = $courseformatter->format_name($course, 'coursetree-name');

        if ($this->stats) {
            $entrycomplement = '<span class="coursetree-stats">';
            foreach (array_values($this->getStats()) as $column) {
                $entrycomplement .= '<span>' . $column .' </span> ';
            }
            $entrycomplement .= '</span>';
        } else {
            $teachers = $courseformatter->format_teachers($course, 'coursetree-teachers');
            $icons = $courseformatter->format_icons($course, 'coursetree-icons');
            $entrycomplement = $teachers . $icons;
        }
        return $crslink . $entrycomplement;
    }
}
