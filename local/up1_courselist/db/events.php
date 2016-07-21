<?php
/**
 * @package    local
 * @subpackage up1_courselist
 * @copyright  2013-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
$handlers = array (
    'course_created' => array (
        'handlerfile'      => '/local/up1_courselist/eventslib.php',
        'handlerfunction'  => 'handle_course_modified',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'course_updated' => array (
        'handlerfile'      => '/local/up1_courselist/eventslib.php',
        'handlerfunction'  => 'handle_course_modified',
        'schedule'         => 'instant',
        'internal'         => 1,
    )
);
*/

$observers = array (
    array (
        'eventname' => '\core\event\course_created',
        'callback'  => 'local_up1_courselist_observer::course_modified',
        'internal'  => false, // This means that we get events only after transaction commit.
        'includefile' => 'local/up1_courselist/db/classes/observer.php',  // NE DEVRAIT PAS ETRE NECESSAIRE
    ),
    array (
        'eventname' => '\core\event\course_updated',
        'callback'  => 'local_up1_courselist_observer::course_modified',
        'internal'  => false, // This means that we get events only after transaction commit.
    ),
);