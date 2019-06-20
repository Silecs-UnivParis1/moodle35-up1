<?php

/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


//**** Listpages creation
// affected tables :
// * page (course=1)
// * course_modules (course=1, module=15, instance->page, section->course_sections ?, idnumber='')
// * course_sections (course=1, section=1, summary='<p>Section descr...</p>', sequence->course_modules)

global $CFG;

require_once($CFG->dirroot . "/lib/resourcelib.php");
require_once($CFG->dirroot . "/mod/page/lib.php");
require_once($CFG->dirroot . "/local/roftools/locallib.php");
require_once($CFG->dirroot . "/local/roftools/listpages-template.class.php");

define('MOD_PAGE', 15);

/**
 * delete existing list pages for ROF course categories
 */
function listpages_list($courseid=1) {
    global $DB;

    $crsmods = $DB->get_records('course_modules', array('course' => $courseid, 'module' => MOD_PAGE));
    $cnt = 0;
    echo "course = $courseid   s = section   p = page\n";
    foreach ($crsmods as $crsmod) {
        $pages = $DB->get_records('page', array('id' => $crsmod->instance));
        foreach ($pages as $page) {
            $cnt++;            
            printf ("%3d.  s=%d p=%d  %s \n",
                $cnt, $crsmod->section, $page->id, substr($page->name, 0, 60));
            // echo substr($page->intro, 0, 30) . "\n";
        }
    }
    return true;
}

/**
 * delete existing list pages for ROF course categories
 */
function listpages_delete() {
    global $DB;

    $cms = $DB->get_records('course_modules', array('course' => 1, 'module' => MOD_PAGE));
    foreach ($cms as $cm) {
        $DB->delete_records('page', array('id' => $cm->instance));
	course_delete_module($cm->id,true);
	//delete_course_module($cm->id);
        delete_mod_from_section($cm->id, $cm->section);
    }
}


/**
 * create the 2 automatic list pages for each of the "official" Component course-categories
 * @global moodle_database $DB
 */
function listpages_create() {
    global $DB;

    $yearcode = get_config('local_roftools', 'rof_year_code');
    $etabcode = get_config('local_roftools', 'rof_etab_code');
    $catpath = '2:' . $yearcode . '/' . $etabcode;
    $rootcat = $DB->get_field('course_categories', 'id', array('idnumber' => $catpath, 'depth' => 2), MUST_EXIST);

    $itercategories = $DB->get_records('course_categories', array('visible' => 1, 'parent' => $rootcat));
    foreach ($itercategories as $category) {
        echo "Creating page for " . $category->name . "\n";
        listpages_create_for($category);
    }
}

/**
 * Create the 2 automatic list pages for the given course category
 *
 * @global moodle_database $DB
 * @param DBrecord $category record from table 'course_categories'
 */
function listpages_create_for($category) {
    global $DB;

    $url = array();
    $views = array(
        'tableau' => array('code' => 'tableau', 'name' => 'vue tableau', 'format' => 'table', 'sister' => +1),
        'arborescence' => array('code' => 'arborescence','name' => 'vue arborescence', 'format' => 'tree', 'sister' => -1),
    );
    $courseId = 1;
    $modulePage = $DB->get_field('modules', 'id', array('name' => 'page'));

    course_create_sections_if_missing($courseId, 1);

    $template = new ListpagesTemplates($category);
    $template->sisterpagelink = ''; /** @todo sisterpagelink */

    $cmsId = array();
    foreach ($views as $viewcode => $view) {
        $template->view = $view;

        $newcm = new stdClass();
        $newcm->course = $courseId;
        $newcm->module = $modulePage;
        $newcm->instance = 0; // not known yet, will be updated later (this is similar to restore code)
        $newcm->visible = 1;
        $newcm->visibleold = 1;
        $newcm->groupmode = 0;
        $newcm->groupingid = 0;
        $newcm->groupmembersonly = 0;
        $newcm->completion = 0;
        $newcm->completiongradeitemnumber = NULL;
        $newcm->completionview = 0;
        $newcm->completionexpected = 0;
        $newcm->availablefrom = 0;
        $newcm->availableuntil = 0;
        $newcm->showavailability = 0;
        $newcm->showdescription = 0;
        /**
         * @todo Optimize with a direct DB action, then call rebuild_course_cache() once the loop has ended.
         */
        $cmid = add_course_module($newcm);
        $cmsId[$viewcode] = $cmid;

        $pagedata = new stdClass();
        $pagedata->coursemodule  = $cmid;
        $pagedata->printheading = 1; /** @todo Check format */
        $pagedata->printintro = 1; /** @todo Check format */
        $pagedata->section = 1;
        $pagedata->course = $courseId;
        $pagedata->introformat = FORMAT_HTML;
        $pagedata->contentformat = FORMAT_HTML;
        $pagedata->legacyfiles = 0;
        $pagedata->display = RESOURCELIB_DISPLAY_AUTO;
        $pagedata->revision = 1;
        $pagedata->name = $template->getName();
        $pagedata->intro = $template->getIntro();
        $pagedata->content = $template->getContent();

        page_add_instance($pagedata);
        course_add_cm_to_section($courseId, $cmid, $pagedata->section);

        $url = new moodle_url('/mod/page/view.php', array('id' => $cmid));
        echo "    {$view['name']} : $url\n";
    }
    // update crossed links
    foreach ($view as $viewcode => $view) {
        foreach ($cmsId as $othercode => $cmId) {
            if ($othercode !== $viewcode) {
                $otherUrl = new moodle_url('/mod/page/view.php', array('id' => $cmId));
                $DB->execute(
                        "UPDATE {page} SET content = REPLACE(content, '{link-$othercode}', ?)",
                        array($otherUrl->out(true))
                );
            }
        }
    }
}


function delete_pages($firstpage, $lastpage) {
    global $DB;

    $firstcm = $DB->get_field('course_modules', 'id', array('instance' => $firstpage), MUST_EXIST);
    $lastcm  = $DB->get_field('course_modules', 'id', array('instance' => $lastpage), MUST_EXIST);

    $where = "id <= ". $lastpage . " AND id >= " . $firstpage;
    $DB->delete_records_select('page', $where, null);

    $where = "id <= ". $lastcm . " AND id >= " . $firstcm;
    $DB->delete_records_select('course_modules', $where, null);

    $wherecs = "course = 1 AND section = 1 AND sequence LIKE '%," . $lastcm . "'";
    $DB->delete_record_select('page', $wherecs, null);

}
