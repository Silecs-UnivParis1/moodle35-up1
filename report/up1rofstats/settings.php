<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage up1rofstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', new admin_externalpage('reportup1rofstats', get_string('pluginname', 'report_up1rofstats'), "$CFG->wwwroot/report/up1rofstats/index.php",'report/up1rofstats:view'));

// no report settings
$settings = null;
