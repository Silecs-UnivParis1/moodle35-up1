<?php

/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $PAGE moodle_page */

require_once(dirname(dirname(__DIR__)) . "/config.php");
require_once __DIR__ . '/Courselist_common.php';
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");
require_once($CFG->dirroot.'/course/lib.php');


class courselist_format {
    private $format = 'tree';
    private $cellelem;
    private $sep;
    private $header;
    private $footer;

    private $role;
    private $responsable;
    private $courseboard; /** bool : only if supervalidator */

    /**
     * Constructor.
     *
     * @param string $format = 'tree' | 'table' | 'list'
     */
    public function __construct($format = 'tree') {
        global $DB;

        $this->format = $format;

        switch ($this->format) {
            case 'tree':
                $this->cellelem = 'span';
                $this->sep = '';
                $this->header = '';
                $this->footer = '';
                break;
            case 'table':
                $this->cellelem = 'td';
                $this->sep = '';
                $this->header = <<<EOL
<table class="generaltable sortable" style="width: 100%;" %%DATA%%>
<thead>
    <tr>
        <th>Code</th>
        <th title="Niveau">Niv.</th>
        <th title="Semestre">Sem.</th>
        <th>Nom de l'espace de cours</th>
        <th>Enseignants</th>
        <th>&nbsp;</th>
    </tr>
</thead>
<tbody>
EOL;
                $this->footer = '</tbody></table>
<div style="clear: both;"></div>';
                break;
            case 'list':
                $this->cellelem = 'span';
                $this->sep = ' - ';
                $this->header = '<ul>';
                $this->footer = '</ul>';
                break;
            default:
                throw new Exception("coding error");

        }

        $this->role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->responsable = $DB->get_record('role', array('shortname' => 'responsable_epi'));
        // disabled for complete compatibility with filter cache, which cannot depend upon user
        // $this->courseboard = has_capability('local/crswizard:supervalidator', context_system::instance());
        $this->courseboard = false;
        /** @todo disabled only if displayed via a filter */
    }

    /**
     * Return a formated course label.
     *
     * @param int $courseid
     * @param boolean $leaf opt, true
     * @return string formatted label
     */
    public function format_entry($courseid, $leaf = true) {
        global $DB;

        $dbcourse = $DB->get_record('course', array('id' => (int) $courseid));
        if (empty($dbcourse)) {
            return '';
        }
        return $this->format_course($dbcourse, $leaf);
    }

    /**
     * Return a formated course label.
     *
     * @param stdClass $course
     * @param boolean $leaf (opt) true
     * @return string formatted label
     */
    public function format_course($course, $leaf = true) {
        $teachers = '';
        $icons = '';

        // compute the elements
        if ($this->format == 'table'|| $this->format == 'list') {
            $code = self::format_code($course);
            $level = self::format_level($course);

        }
        if ($this->format == 'table' || $this->format == 'tree') {
            $teachers = self::format_teachers($course, 'coursetree-teachers');
            $icons = self::format_icons($course, 'coursetree-icons');
        }
        $crslink = self::format_name($course, 'coursetree-' . ($leaf ? "name" : "dir")) ;

        // renders the line
        switch ($this->format) {
            case 'tree':
                return $crslink . $teachers . $icons ;
                break;
            case 'table':
                return '<tr>' . $code . $level . $crslink . $teachers . $icons . '</tr>';
                break;
            case 'list':
                return '<li>' . $level . ' - ' . $crslink . ' ('. $code . ')' . '</li>';
        }
    }

    /**
     * Returns the header appropriate to the format.
     *
     * @param string $data (opt) attributes to insert into the table header
     * @return string HTML header
     */
    public function get_header($data = '') {
        return str_replace('%%DATA%%', $data, $this->header);
    }

    public function get_footer() {
        return $this->footer;
    }

    /**
     * @param StdClass $dbcourse
     * @param string $class
     * @return string HTML
     */
    public function format_name($dbcourse, $class) {
        global $OUTPUT;
        $urlCourse = new moodle_url('/course/view.php', array('id' => $dbcourse->id));
        $crsname = get_course_display_name_for_list($dbcourse); // could be completed with ROF $name ?
        $rmicon = '';
        if ($this->format == 'tree'  &&  courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $rmicon .= $OUTPUT->render(new pix_icon('t/add', 'Rattachement multiple'));
        }
        $crslink = '<' . $this->cellelem. ' class="' . $class . '">'
                . html_writer::link($urlCourse, $crsname) . '&nbsp;' . $rmicon
                . '</' . $this->cellelem . '>';
        return $crslink;
    }

    /**
     * format teachers : returns an abbreviated list with a title representing full list
     *
     * @global moodle_database $DB
     * @param stdClass $dbcourse course db record
     * @param string $class html class
     * @param integer $number number of teachers to display (1 or more)
     * @return string
     */
    public function format_teachers($dbcourse, $class, $number=1) {
        $context = context_course::instance($dbcourse->id);
        $teachers = get_role_users($this->role->id, $context);
        $responsables = get_role_users($this->responsable->id, $context);
        
        $titleteachers = array();
        foreach ($responsables as $e) { 
           $titleteachers[$e->id] = 'Responsable EPI ' . fullname($e);
        }
        foreach ($teachers as $e) { 
           if (!isset($titleteachers[$e->id]))
              $titleteachers[$e->id] = 'Enseignant éditeur ' . fullname($e); 
        }

        if (!$responsables) {
	   $responsables = $teachers;
	}

        $dispteachers = array_slice($responsables, 0, $number);
        $headteachers = join(', ', array_map('fullname', $dispteachers)) . (count($responsables) > $number ? ', …' : '');

        $fullteachers = '<' . $this->cellelem . ' class="' . $class . '">'
                . '<span style="cursor: default;" title="' . join(', ', $titleteachers) . '">'
                . $headteachers
                . '</span>'
                . '</' . $this->cellelem . '>';
        return $fullteachers;
    }

    public function format_icons($dbcourse, $class) {
        global $OUTPUT;

        $urlsynopsis = new moodle_url('/report/up1synopsis/index.php', array('id' => $dbcourse->id));
        $icons = '<' .$this->cellelem. ' class="' . $class. '">';
        $myicons = enrol_get_course_info_icons($dbcourse);
        if ($myicons) { // enrolment access icons
            foreach ($myicons as $pix_icon) {
                $icons .= $OUTPUT->render($pix_icon);
            }
        }
        if ( $dbcourse->visible == 0 ) {
            $icons .= $OUTPUT->render(new pix_icon('t/block', 'Fermé aux étudiants'));
        }
        $icons .= $OUTPUT->action_icon($urlsynopsis, new pix_icon('i/info', 'Afficher le synopsis du cours'));
        if ($this->courseboard) {
            $urlboard = new moodle_url('/local/courseboard/view.php', array('id' => $dbcourse->id));
            $icons .= $OUTPUT->action_icon($urlboard, new pix_icon('i/settings', 'Afficher le tableau de bord'));
        }
        $icons .= '</' . $this->cellelem . '>';
        return $icons;
    }

    private function format_code($dbcourse) {
        $code = strstr($dbcourse->idnumber, '-', true);
        if (courselist_common::has_multiple_rattachements($dbcourse->id)) {
            $code .= '<span title="Rattachement multiple">&nbsp;+</span>';
        }
        return   '<' . $this->cellelem . '>' . $code . '</' . $this->cellelem . '>' ;
    }

     private function format_level($dbcourse) {
        $niveau = up1_meta_html_multi($dbcourse->id, 'niveau', false, '');
        $semestre = up1_meta_html_multi($dbcourse->id, 'semestre', false, 'S.');
        return   '<' . $this->cellelem . '>' . $niveau . '</' . $this->cellelem . '>' . $this->sep
               . '<' . $this->cellelem . '>' . $semestre. '</' . $this->cellelem . '>';
    }
}
