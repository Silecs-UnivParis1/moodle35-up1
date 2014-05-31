<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require(__DIR__ . '/../listpageslib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array(
        'help'=>false, 'verb'=>1,
        'create'=>false, 'delete'=>false, 'list'=>false, 'status'=>false,
        'crsid'=>1,
    ),
    array('h'=>'help', 'i'=>'init'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Create or delete index pages according to the ROF cache and Course categories

Options:
-h, --help            Print out this help
--verb=N              Verbosity (0 to 3), 1 by default

--create              Create the index pages
--delete              Delete the index pages
--list                List all the index pages already present in the database
  --crsid             courseid (1 is the default)
";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;

if ( $options['list'] ) {
    echo "Listing index pages for ROF course categories... \n";
    listpages_list($options['crsid']);
    return 0;
}

if ( $options['create'] ) {
    echo "Creating index pages... \n";
    listpages_create();
    echo "OK.\n";
    return 0;
}

if ( $options['delete'] ) {
    echo "Deleting index pages... \n";
    listpages_delete();
    echo "OK.\n";
    return 0;
}