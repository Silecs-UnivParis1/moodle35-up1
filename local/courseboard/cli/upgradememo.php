<?php
// This is a debugging/testing script.
// It should not be run on the production instance

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(__DIR__))).'/config.php'); // global moodle config file.
require(dirname(__DIR__) . '/upgradelib.php');

upgrade_to_memo();

exit(0);