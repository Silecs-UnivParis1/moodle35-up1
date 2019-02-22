<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class containing data for courses view in the myoverview block.
 *
 * @package    block_myoverview
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_myoverview\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;
use core_course\external\course_summary_exporter;

/**
 * Class containing data for courses view in the myoverview block.
 *
 * @copyright  2017 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class courses_view implements renderable, templatable {
    /** Quantity of courses per page. */
    const COURSES_PER_PAGE = 100;

    /** @var array $courses List of courses the user is enrolled in. */
    protected $courses = [];

    /** @var array $coursesprogress List of progress percentage for each course. */
    protected $coursesprogress = [];

    /**
     * The courses_view constructor.
     *
     * @param array $courses list of courses.
     * @param array $coursesprogress list of courses progress.
     */
    public function __construct($courses, $coursesprogress) {
        $this->courses = $courses;
        $this->coursesprogress = $coursesprogress;
    }

    private function summary_completed($courseid) {
	global $DB, $CFG;
	// select responsable EPI : id=22
	// select enseignat editeur : id=3
	$select = "
	select ra.roleid, u.firstname as prenom, u.lastname as nom
	from {user} u 
	inner join {role_assignments} ra on (ra.userid=u.id) 
	inner join {context} ctx on (ctx.id = ra.contextid) 
	where (ctx.contextlevel = 50 and ctx.instanceid = ?) 
	and ra.roleid in (22,3)
";
/*
	$select = "select e.roleid, u.firstname as prenom, u.lastname as nom
		   from {user} u
		   inner join {user_enrolments} ue on (ue.userid=u.id)
		   inner join {enrol} e on (ue.enrolid=e.id)
		   where e.courseid=? 
		   and e.roleid in (22,3)
		   order by nom, prenom";
 */
	$obj_ens = $DB->get_records_sql($select,array($courseid));
	$tab_ens_resp = array();
	$tab_ens_edit = array();
	foreach ($obj_ens as $i=>$row) {
		if ($row->roleid == 22) 
		    $tab_ens_resp[] = $row->prenom .' ' . $row->nom;
		else
		    $tab_ens_edit[] = $row->prenom . ' ' . $row->nom;	
	}
	$chaine_courte = '';
	$chaine_longue = '';
	if (count($tab_ens_resp)==0) {
		if (count($tab_ens_edit)>0) {
		    $chaine_longue = implode(', ',$tab_ens_edit); 
	            $chaine_courte = $tab_ens_edit[0];
		}
	} else {
	    $chaine_longue = implode(', ',$tab_ens_resp);
	    $chaine_courte= $tab_ens_resp[0] ;
	    if (!empty($tab_ens_edit))
		    $chaine_longue.= ', ' . implode(', ',$tab_ens_edit);
	}
	$cpt = count($tab_ens_resp) + count($tab_ens_edit);
	if ($cpt >1)
		$chaine_courte .= ', ...';
	$span_summary = '';
	$span_summary = '<span class="myoverview_summary_link"><a href="'.$CFG->wwwroot.'/report/up1synopsis/index.php?id='.$courseid.'">[+ info]</a></span>';
	
	$retour ='';
	if (!empty($chaine_courte))
		$retour = '
			<div class="myoverview_added_description">
				<span title="'.$chaine_longue.'" class="myoverview_author">'.$chaine_courte.'</span>
				'.$span_summary.'
			</div>		
';	
	return $retour;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $CFG;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/lib/coursecatlib.php');

        // Build courses view data structure.
        $coursesview = [
            'hascourses' => !empty($this->courses)
        ];

        // How many courses we have per status?
        $coursesbystatus = ['past' => 0, 'inprogress' => 0, 'future' => 0];
        foreach ($this->courses as $course) {
            $courseid = $course->id;
            $context = \context_course::instance($courseid);
            $exporter = new course_summary_exporter($course, [
                'context' => $context
            ]);
            $exportedcourse = $exporter->export($output);
	    // Convert summary to plain text.
	    
	    
	    $summary_completed = $this->summary_completed($course->id);
            //$exportedcourse->summary = content_to_text($exportedcourse->summary, $exportedcourse->summaryformat);
	    $exportedcourse->summary = $summary_completed;
            $course = new \course_in_list($course);
            foreach ($course->get_course_overviewfiles() as $file) {
                $isimage = $file->is_valid_image();
                if ($isimage) {
                    $url = file_encode_url("$CFG->wwwroot/pluginfile.php",
                        '/'. $file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename(), !$isimage);
                    $exportedcourse->courseimage = $url;
                    $exportedcourse->classes = 'courseimage';
                    break;
                }
            }

            $exportedcourse->color = $this->coursecolor($course->id);

            if (!isset($exportedcourse->courseimage)) {
                $pattern = new \core_geopattern();
                $pattern->setColor($exportedcourse->color);
                $pattern->patternbyid($courseid);
                $exportedcourse->classes = 'coursepattern';
                $exportedcourse->courseimage = $pattern->datauri();
            }

            // Include course visibility.
            $exportedcourse->visible = (bool)$course->visible;

            $courseprogress = null;

            $classified = course_classify_for_timeline($course);

            if (isset($this->coursesprogress[$courseid])) {
                $courseprogress = $this->coursesprogress[$courseid]['progress'];
                $exportedcourse->hasprogress = !is_null($courseprogress);
                $exportedcourse->progress = $courseprogress;
            }

            if ($classified == COURSE_TIMELINE_PAST) {
                // Courses that have already ended.
                $pastpages = floor($coursesbystatus['past'] / $this::COURSES_PER_PAGE);

                $coursesview['past']['pages'][$pastpages]['courses'][] = $exportedcourse;
                $coursesview['past']['pages'][$pastpages]['active'] = ($pastpages == 0 ? true : false);
                $coursesview['past']['pages'][$pastpages]['page'] = $pastpages + 1;
                $coursesview['past']['haspages'] = true;
                $coursesbystatus['past']++;
            } else if ($classified == COURSE_TIMELINE_FUTURE) {
                // Courses that have not started yet.
                $futurepages = floor($coursesbystatus['future'] / $this::COURSES_PER_PAGE);

                $coursesview['future']['pages'][$futurepages]['courses'][] = $exportedcourse;
                $coursesview['future']['pages'][$futurepages]['active'] = ($futurepages == 0 ? true : false);
                $coursesview['future']['pages'][$futurepages]['page'] = $futurepages + 1;
                $coursesview['future']['haspages'] = true;
                $coursesbystatus['future']++;
            } else {
                // Courses still in progress. Either their end date is not set, or the end date is not yet past the current date.
                $inprogresspages = floor($coursesbystatus['inprogress'] / $this::COURSES_PER_PAGE);

                $coursesview['inprogress']['pages'][$inprogresspages]['courses'][] = $exportedcourse;
                $coursesview['inprogress']['pages'][$inprogresspages]['active'] = ($inprogresspages == 0 ? true : false);
                $coursesview['inprogress']['pages'][$inprogresspages]['page'] = $inprogresspages + 1;
                $coursesview['inprogress']['haspages'] = true;
                $coursesbystatus['inprogress']++;
            }
        }

        // Build courses view paging bar structure.
        foreach ($coursesbystatus as $status => $total) {
            $quantpages = ceil($total / $this::COURSES_PER_PAGE);

            if ($quantpages) {
                $coursesview[$status]['pagingbar']['disabled'] = ($quantpages <= 1);
                $coursesview[$status]['pagingbar']['pagecount'] = $quantpages;
                $coursesview[$status]['pagingbar']['first'] = ['page' => '&laquo;', 'url' => '#'];
                $coursesview[$status]['pagingbar']['last'] = ['page' => '&raquo;', 'url' => '#'];
                for ($page = 0; $page < $quantpages; $page++) {
                    $coursesview[$status]['pagingbar']['pages'][$page] = [
                        'number' => $page + 1,
                        'page' => $page + 1,
                        'url' => '#',
                        'active' => ($page == 0 ? true : false)
                    ];
                }
            }
        }

        return $coursesview;
    }

    /**
     * Generate a semi-random color based on the courseid number (so it will always return
     * the same color for a course)
     *
     * @param int $courseid
     * @return string $color, hexvalue color code.
     */
    protected function coursecolor($courseid) {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8', '#6c5ce7'];

        $color = $basecolors[$courseid % 10];
        return $color;
    }
}
