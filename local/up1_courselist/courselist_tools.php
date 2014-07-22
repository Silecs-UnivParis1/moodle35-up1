<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* @var $PAGE moodle_page */

require_once(dirname(dirname(__DIR__)) . "/config.php");
require_once($CFG->dirroot . "/local/up1_metadata/lib.php");
require_once($CFG->dirroot . "/local/roftools/roflib.php");
require_once($CFG->dirroot . '/course/lib.php');

require_once(__DIR__ . '/Courselist_common.php');
require_once(__DIR__ . '/Courselist_cattools.php');
require_once(__DIR__ . '/Courselist_format.php');
require_once(__DIR__ . '/Courselist_roftools.php');


/**
 * return a meta author tag wiht content = course teachers
 */
function meta_author_teachers() {
    global $DB, $PAGE;

    if (isset ($PAGE)) {
        $coursecontext = $PAGE->context;
        $role = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $teachers = get_role_users($role->id, $coursecontext);
        $authorteachers = join(', ', array_map('fullname', $teachers));
        $meta = '<meta name="author" content="' . $authorteachers . '" />' . "\n";
        return $meta;
    } else {
        return false;
    }
}
