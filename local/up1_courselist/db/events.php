<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array (
        'eventname' => '\core\event\course_created',
        'callback'  => 'local_up1_courselist_observer::course_modified',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 0,
    ),
    array (
        'eventname' => '\core\event\course_updated',
        'callback'  => 'local_up1_courselist_observer::course_modified',
        'internal'  => false, // This means that we get events only after transaction commit.
        'priority'  => 0,
    ),
);