<?php
/**
 * @package    block
 * @subpackage course_opennow
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

    require_once('../../config.php');
	global $CFG, $DB;
	$courseid = required_param('courseid',PARAM_INT);
    $isvisible = required_param('visible', PARAM_INT);
	$returnurl = $_SERVER['HTTP_REFERER'];

    $context = context_course::instance($courseid);
    if ($data = data_submitted() and confirm_sesskey()) {
        $context = context_course::instance($data->courseid);
        if (has_capability('moodle/course:update', $context)) {
            if (!$course = $DB->get_record('course', array('id' =>$data->courseid))) {
                    error('Course ID was incorrect');
            } else {
				$visible = ($isvisible == 1 ? 0 : 1);
				if (! $DB->update_record('course', array('id' => $course->id,
					'visible' => $visible, 'visibleold' => $visible, 'timemodified' => time()))) {
					echo 'not updated';
					print_error('coursenotupdated');
				}
            }
		}
    }
    redirect($returnurl);
?>
