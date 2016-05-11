<?php
/**
 * Version info
 *
 * @package    local
 * @subpackage redirection
 * @copyright  2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);
global $DB;

require('../../config.php');
require(__DIR__ . '/../up1_metadata/lib.php');

require_login();

$target = optional_param('target', '', PARAM_ALPHANUMEXT);

if (isset($target) && ! empty($target)) {
    $courseid = up1_meta_get_objects_by_field('course', 'up1urlfixe', $target);

    if (count($courseid) > 1) {
        throw new moodle_exception(
            'Multiple courses for one up1urlfixe: '. $target. ' '. print_r($courseid, true),
            'local_redirection');
    }
    if (empty($courseid)) {
        redirect(new moodle_url('/') , 'No course matching this url target: ' . $target, 3);
    }
    redirect(new moodle_url('/course/view.php', ['id' => $courseid[0]]), '', 0);

} else {
    redirect(new moodle_url('/') , 'Undefined url target. Should not happen. Redirecting to home.', 3);
}