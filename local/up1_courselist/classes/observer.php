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
        $edata = $event->get_data();

        $mycourse = $DB->get_record('course', ['id' => $edata['courseid']]);
        if (! $mycourse->category) {
            throw new moodle_exception('unknowncategory');
        }
        $category = coursecat::get($mycourse->category);
        return $category->resort_courses('fullname', true);
    }

}