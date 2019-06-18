<?php
/**
 * @package    block
 * @subpackage course_opennow
 * @copyright  2012-2019 Silecs {@link http://www.silecs.info/societe}
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

        if ($this->content !== NULL) {
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
        }
		if (has_capability('moodle/course:update', $context)) {
			$startDate = date('d-m-Y', $this->page->course->startdate);
			$isvisible = $this->page->course->visible;
            $status = [ 0 => 'statusclosed', 1 => 'statusopen'];
            $message = get_string($status[$isvisible], 'block_course_opennow');
            $archiveyear = $this->get_course_year();

			$this->content->text = '<div class="">' . get_string('startdate', 'block_course_opennow');
			$this->content->text .= ' : '. $startDate;
            $this->content->text .= '<div>' . $message . '</div>';
            if ( ! $archiveyear || has_capability('block/course_opennow:openarchived', $context)) {
                $this->content->text .= $this->get_button_form($isvisible);
                if ($archiveyear) {
                    $this->content->text .= "à la consultation des étudiants de l'année " . $archiveyear;
                }
            }
			$this->content->text .= '</div>';
		}

        return $this->content;
    }

    private function get_button_form($isvisible) {
        $verb = [ 0 => 'opencourse', 1 => 'closecourse'];
        $buttonname = get_string($verb[$isvisible], 'block_course_opennow');

        return sprintf('<form action="%s" method="post">', new moodle_url('/blocks/course_opennow/open.php'))
             . sprintf('<input type="hidden" value="%d" name="courseid" />', $this->page->course->id)
             . sprintf('<input type="hidden" value="%s" name="sesskey" />', sesskey())
             . sprintf('<input type="hidden" value="%d" name="visible" />', $isvisible)
             . sprintf('<button type="submit" name="datenow" value="open">%s</button>', $buttonname)
             .'</form>';
    }

    /**
     * renvoie l'année scolaire du cours courant s'il est archivé,
     * d'après la catégorie ancestrale, champ idnumber
     * @param array $dates
     * @return string or null
     */
    private function get_course_year() {

        if (up1_meta_get_text($this->page->course->id, 'up1datearchivage') == 0) {
            return null;
        }
        $cat = coursecat::get($this->page->course->category);
        if (preg_match('@^\d:(\d{4}-\d{4})/@', $cat->idnumber, $matches)) {
            return $matches[1];
        } else {
            return null;
        }
    }

    private function set_footer() {
        global $OUTPUT;

        $this->content->footer = '';
        if (null !== (get_config('block_course_opennow', 'faqurl'))) {
            $url = get_config('block_course_opennow', 'faqurl');
            $this->content->footer = html_writer::link($url, "Plus d'explications")
            . " " . $OUTPUT->action_icon($url, new pix_icon('i/info', 'FAQ EPI'));
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
        return [
            'course' => true,
            'mod'    => false,
            'my'     => false,
            'admin'  => false,
            'tag'    => false
            ];
    }

}


