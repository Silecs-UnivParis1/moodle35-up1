<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage up1userstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports',
        new admin_externalpage('reportup1userstats',
                get_string('pluginname', 'report_up1userstats'),
                "$CFG->wwwroot/report/up1userstats/index.php",
                'report/up1userstats:view')
        );

// no report settings
$settings = null;
