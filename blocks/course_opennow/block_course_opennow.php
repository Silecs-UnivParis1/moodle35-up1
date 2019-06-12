<?php
/**
 * @package    block
 * @subpackage course_opennow
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/up1_metadata/lib.php');

class block_course_opennow extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_course_opennow');
    }
    
    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG;

        if($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new stdClass();
        $context = context_course::instance($this->page->course->id);
        $dates = up1_meta_get_date($this->page->course->id, 'datearchivage');

        $this->set_footer();

        if ($dates['datefr']) {
            $this->content->text = '<div class="">Cours archivé depuis le ' . $dates['datefr'] .'</div>';
            return $this->content;
        }
		if (has_capability('moodle/course:update', $context)) {
			$startDate = date('d-m-Y', $this->page->course->startdate);
			$open = $this->page->course->visible;
			$this->content->text = '<div class="">' . get_string('startdate', 'block_course_opennow');
			$this->content->text .= ' : '. $startDate;
            $buttonname = get_string('opencourse', 'block_course_opennow');
            $message = get_string('close', 'block_course_opennow');
			if ($open) {
                $message = get_string('open', 'block_course_opennow');
                $buttonname = get_string('closecourse', 'block_course_opennow');
			}
            $this->content->text .= '<div>' . $message . '</div>';
            $this->content->text .= '<form action="' . $CFG->wwwroot . '/blocks/course_opennow/open.php" method="post">'
                . '<input type="hidden" value="'.$this->page->course->id.'" name="courseid" />'
                . '<input type="hidden" value="'.sesskey().'" name="sesskey" />'
                . '<input type="hidden" value="'.$open.'" name="visible" />'
                . '<button type="submit" name="datenow" value="open">'
                . $buttonname . '</button>'
                .'</form>';
			$this->content->text .= '</div>';
		}

        return $this->content;
    }

    private function set_footer() {
        $this->content->footer = '';
        if (null !== (get_config('block_course_opennow', 'faqurl'))) {
            $this->content->footer = html_writer::link(
                get_config('block_course_opennow', 'faqurl'),
                "Plus d'explications"
                );
            return true;
        }
        return false;
    }

    function hide_header() {
        return true;
    }

    function preferred_width() {
        return 210;
    }

     function applicable_formats() {
        return array('course' => true, 'mod' => false, 'my' => false, 'admin' => false,
                     'tag' => false);
    }

}


