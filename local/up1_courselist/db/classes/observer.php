<?php
/**
 * Event observer
 * This class is called by the events course_created and course_updated (in ../db/events.php)
 * to re-sort the courses under the same parent category with respect to fullname
 *
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_up1_courselist_observer {

    public static function course_modified(\core\event\base $event) {
        global $DB;
var_dump($event);
die("FIN");

        $category = $DB->get_record('course_categories', array('id' => $eventdata->category));
        if (! $category) {
            throw new moodle_exception('unknowncategory');
        }

        // copied from course/category.php l.87-95
        if ($courses = get_courses($eventdata->category, '', 'c.id, c.fullname, c.sortorder')) {
            core_collator::asort_objects_by_property($courses, 'fullname', core_collator::SORT_NATURAL);
            $i = 1;
            foreach ($courses as $course) {
                $DB->set_field('course', 'sortorder', $category->sortorder+$i, array('id'=>$course->id));
                $i++;
            }
            fix_course_sortorder(); // should not be needed
        }
        return true;
    }

}