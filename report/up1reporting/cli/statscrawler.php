<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage up1reporting
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__DIR__))) . '/config.php'); // global moodle config file.
require_once($CFG->libdir . '/clilib.php');      // cli only functions
require_once(dirname(__DIR__) . '/locallib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array(
        'help'=>false, 'maxdepth'=>6, 'verb'=>1),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"CourseHybridTree Statistics Crawler (CLI)

Options:
-h, --help            Print out this help
--maxdepth            Maximal tree depth ; 0=no max.

";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}

// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

statscrawler($options['maxdepth']);