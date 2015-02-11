<?php
/* @var $DB moodle_database */

// require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');
// require_once($CFG->dirroot . "/local/up1_courselist/courselist_tools.php");
require_once($CFG->dirroot . "/local/up1_courselist/Courselist_cattools.php");
require_once($CFG->dirroot . "/local/up1_courselist/Courselist_roftools.php");

class ChtNodeCategory extends ChtNode
{
    //private $id; // Moodle id from course_categories
    protected $flag = '(C) ';

    /**
     * @param int $catid
     * @return ChtNodeCategory
     */
    static function buildFromCategoryId($catid) {
        global $DB;
        $record = $DB->get_record('course_categories', array('id' => (int) $catid));
        return self::buildFromCategory($record);
    }

    /**
     * @param StdClass $record DB record of a category
     * @return ChtNodeCategory
     */
    static function buildFromCategory($record) {
        if (empty($record->id)) {
            // Cannot build node from an empty record.
            return null;
        }
        $new = new self;
        $new->name = $record->name;
        $new->code = $record->idnumber;
        $new->id = $record->id;
        $new->absolutePath = str_replace('/', '/cat', $record->path);
        $new->path = '/cat' . $record->id; // we assume this node is an entry point
        return $new;
    }

    /**
     * @return string
     */
    function getComponent() {
        if ($this->component != null) {
            return $this->component;
        }
        $absdepth = $this->getAbsoluteDepth();
        if ($absdepth < 3) {
            $this->component = '00';
            return $this->component;
        } else {
            $this->component = courselist_cattools::get_component_from_category($this->id);
            return $this->component;
        }
    }

    /**
     * Initialize the paths from the parent node.
     *
     * @param ChtNode $parent (optionally null if no parent, as for debugging)
     * @return \ChtNodeCategory
     */
    function setParent($parent) {
        parent::setParent($parent);
        $this->path = $parent->getPath() . '/cat' . $this->id;
        $this->absolutePath = $parent->getAbsolutePath() . '/cat' . $this->id;
        if ($parent->getAbsoluteDepth() != 2) {
            $this->component = $parent->getComponent();
        }
        return $this;
    }

    /**
     * @return array of ChtNode
     */
    function listChildren() {
        if ($this->children !== null) {
            return $this->children;
        }
        $this->children = array();
        $coursesDescendant = courselist_cattools::get_descendant_courses($this->id);
        list($coursesRof, $coursesCat) = courselist_roftools::split_courses_from_rof($coursesDescendant, $this->getComponent(), false);
        if ($this->hasRofChildren()) {
            $this->addRofChildren('/' . $this->id, $coursesRof);
            // if it contains directly courses (rare)...
            $this->addCourseChildren($coursesCat);
        } else {
            $this->addCategoryChildren();
        }
        return $this->children;
    }

    /**
     * list all the *descendant* courses (not only direct children)
     *
     * @return array of courseid
     */
    function listDescendantCourses() {
        $courses = courselist_cattools::get_descendant_courses($this->id);
        return $courses;
    }

    /**
     * @return boolean If True, children will be found through ROF instead of Moodle Cat.
     */
    private function hasRofChildren() {
        if ($this->getAbsoluteDepth() == 4) {
            return true; // true
        } else {
            return false;
        }
    }

    /**
     * add categories children, only for populated categories
     */
    private function addCategoryChildren() {
        // get all children categories (standard Moodle)
        $categories = coursecat::get($this->id)->get_children();
        // then keep only populated ones
        foreach ($categories as $category) {
            $courses = courselist_cattools::get_descendant_courses($category->id);
            $n = count($courses);
// TODO verbose mode?
// echo "cat = $category->id  n = $n  crs=" . join(', ', $courses) . "\n";
            if ($n >= 1) {
                $this->addDebugMessage("addCategoryChildren() PARENT {$category->id}");
                $this->children[] = ChtNodeCategory::buildFromCategory($category)
                    ->setParent($this)
                    ->addDebugMessage("addCategoryChildren() CHILD {$category->id}");
            }
        }
    }
}
