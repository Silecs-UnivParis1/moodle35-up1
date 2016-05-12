<?php

/**
 * Settings and links
 *
 * @package    report
 * @subpackage up1urlfixe
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$ADMIN->add('reports', 
            new admin_externalpage(
                'reportup1urlfixe',
                get_string('pluginname', 'report_up1urlfixe'),
                "$CFG->wwwroot/report/up1urlfixe/index.php",
                'report/up1urlfixe:view'
                )
        );

// no report settings
$settings = null;
