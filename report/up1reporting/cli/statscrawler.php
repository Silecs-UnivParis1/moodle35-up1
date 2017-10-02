<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage up1reporting
 * @copyright  2014-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__DIR__))) . '/config.php'); // global moodle config file.
require_once($CFG->libdir . '/clilib.php');      // cli only functions
require_once(dirname(__DIR__) . '/locallib.php');
require_once(dirname(__DIR__) . '/ExportReportingCsv.php');

require_once($CFG->dirroot . '/local/coursehybridtree/libcrawler.php');
require_once($CFG->dirroot . '/local/rof_sync/locallib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array(
        'help'=>false, 'metastats'=>0, 'verb'=>1, 'config'=>0,
        'stats'=>false, 'csv'=>false, 'maxdepth'=>6, 'node'=>''),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"CourseHybridTree Statistics Crawler (CLI)

Options:
-h, --help            Print out this help
--config              Dump the Moodle configuration
--metastats           Show the statistics on the recorded statistics (number and dates)
--verb                Verbosity, 0 to 3 (default is 1)

--stats               (action) compute stats and update database (normally, launched by cron)
--csv                 (action) generates a csv output  (normally, launched by web UI)
--maxdepth            Maximal tree depth ; 0=no max.
--node                Root node for action, eg. '/cat10/cat11/cat12' or simply '/cat12'
";


if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}

if ( ! empty($options['config']) ) {
    var_dump($CFG);
    return 0;
}

if ( ! empty($options['metastats']) ) {
    cli_meta_statistics();
    return 0;
}

// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if ($options['stats']) {
    statscrawler($options['node'], $options['maxdepth'], $options['verb']);
} elseif ($options['csv']) {
    if (empty($options['node'])) {
        echo "Please specify --node.\n";
        return 0;
    }

    $export = new ExportReportingCsv($options['node'], $options['maxdepth']);
    $export->reportcsvcrawler();
} else {
    echo "You must specify --help or --stats or --csv.\n";
}

