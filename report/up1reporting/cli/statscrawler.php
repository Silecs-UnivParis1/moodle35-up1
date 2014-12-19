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
        'help'=>false, 'stats'=>false, 'csv'=>false, 'maxdepth'=>6, 'node'=>'', 'verb'=>1),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"CourseHybridTree Statistics Crawler (CLI)

Options:
-h, --help            Print out this help
--stats               (action) compute stats and update database (normally, launched by cron)
--csv                 (action) generates a csv output  (normally, launched by web UI)
--maxdepth            Maximal tree depth ; 0=no max.
--node                Root node for action
";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}

// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if ($options['stats']) {
    statscrawler($options['maxdepth']);
} elseif ($options['csv']) {
    if (empty($options['node'])) {
        echo "Please specify --node.\n";
        return 0;
    }
    reportcsvcrawler($options['node'], $options['maxdepth']);
} else {
    echo "You must specify --help or --stats or --csv.\n";
}

