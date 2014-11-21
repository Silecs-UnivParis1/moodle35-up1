<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage mwscoursetree
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once(dirname(dirname(__DIR__)) . '/coursehybridtree/locallib.php');


// now get cli options
list($options, $unrecognized) = cli_get_params(array(
        'help'=>false, 'verb'=>1, 'node'=>''),
    array('h'=>'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

$help =
"Testing service-children.php

Options:
-h, --help            Print out this help
--node                Test the webservice for the given node.
                      node = eg /cat0 (interpolated), /cat10, /cat14/UP1-PROG12345 ...
";

if ( ! empty($options['help']) ) {
    echo $help;
    return 0;
}


// Ensure errors are well explained
$CFG->debug = DEBUG_NORMAL;


if ( $options['node'] ) {
    $tree = CourseHybridTree::createTree($options['node']);

    // header('Content-Type: application/json; charset="UTF-8"');
    if (! $tree) {
        echo "Erreur de chargement de '{$options['node']}'...";
    } else {
        $tree->debug = true;
        print_r($tree->listJqtreeChildren());
    }
    return 0;
}
