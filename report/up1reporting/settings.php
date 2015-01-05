<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage up1reporting
 * @copyright  2014-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports',
        new admin_externalpage('reportup1reporting',
                get_string('pluginname', 'report_up1reporting'),
                "$CFG->wwwroot/report/up1reporting/index.php")
        );

// no report settings
$settings = null;
