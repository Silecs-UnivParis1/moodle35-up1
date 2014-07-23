<?php
/* @var $DB moodle_database */

// require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');
// require_once($CFG->dirroot . "/local/up1_courselist/courselist_tools.php");
require_once($CFG->dirroot . "/local/up1_courselist/Courselist_cattools.php");
require_once($CFG->dirroot . "/local/up1_courselist/Courselist_roftools.php");

class ChtNodeCategory extends ChtNode
{
    private $catid; // Moodle id from course_categories
    // private $component; // to be defined ? only if catlevel >=3

    static function buildFromCategoryId($catid) {
        global $DB;
        $record = $DB->get_record('course_categories', array('id' => (int) $catid));
        return self::buildFromCategory($record);
    }

    static function buildFromCategory($record) {
        $new = new self;
        $new->name = $record->name;
        $new->code = $record->idnumber;
        $new->catid = $record->id;
        $new->absolutePath = str_replace('/', '/cat', $record->path);
        $new->path = '/cat' . $record->id; // we assume this node is an entry point
        return $new;
    }

    function getComponent() {
        if ($this->component != null) {
            return $this->component;
        }
        $absdepth = $this->getAbsoluteDepth();
        if ($absdepth < 3) {
            $this->component = '00';
            return $this->component;
        } else {
            $this->component = courselist_cattools::get_component_from_category($this->catid);
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
        $this->path = $parent->getPath() . '/cat' . $this->catid;
        $this->absolutePath = $parent->getAbsolutePath() . '/cat' . $this->catid;
        if ($parent->getAbsoluteDepth() != 2) {
            $this->component = $parent->getComponent();
        }
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
        return $this->children;
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
     * add categories children, only for populated categories
     */
    private function addCategoryChildren() {
        // get all children categories (standard Moodle)
        $categories = coursecat::get($this->catid)->get_children();
        // then keep only populated ones
        foreach ($categories as $category) {
            $courses = courselist_cattools::get_descendant_courses($category->id);
            $n = count($courses);
// TODO verbose mode?
// echo "cat = $category->id  n = $n  crs=" . join(', ', $courses) . "\n";
            if ($n >= 1) {
                $this->children[] = ChtNodeCategory::buildFromCategoryId($category->id)
                    ->setParent($this);
            }
        }
    }

    /**
     * add direct courses children (case category level=4)
     */
    private function addCourseChildren() {
        $component = courselist_cattools::get_component_from_category($this->catid);

        $courses = courselist_cattools::get_descendant_courses($this->catid);
        list($rofcourses, $catcourses) = courselist_roftools::split_courses_from_rof($courses, $component);
        
        foreach ($catcourses as $crsid) {
// echo "crsid = $crsid \n";
            $this->children[] = ChtNodeCourse::buildFromCourseId($crsid)
                ->setParent($this);
        }
        
    }

}
