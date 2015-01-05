<?php

/**
 * Administrator reporting
 *
 * @package    report
 * @subpackage up1reporting
 * @copyright  2013-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);
require dirname(dirname(__DIR__)) . '/config.php';
require_once __DIR__ . '/locallib.php';
require_once __DIR__ . '/ExportReportingCsv.php';

$PAGE->set_context(context_system::instance());

$node = required_param('node', PARAM_RAW);

header('Content-Type: text/csv; charset="UTF-8"; header=present');

// reportcsvcrawler($node, 6);
$export = new ExportReportingCsv($node, 6);
$export->reportcsvcrawler();
