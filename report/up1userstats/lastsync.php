<?php

/**
 * UP1 Users Statistics - Last synchronizations page
 *
 * @package    report
 * @subpackage up1userstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->dirroot.'/report/up1userstats/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$howmany = optional_param('number', 50, PARAM_INT);

// Print the header.
admin_externalpage_setup('reportup1userstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading('Last '.$howmany. ' synchronizations');

$url = "$CFG->wwwroot/report/up1userstats/index.php";

echo "<h3>Last LDAP synchronizations</h3>\n";
$table = new html_table();
$table->head = array('Début', 'Fin', 'Action', 'Info');
$table->data = report_up1userstats_syncs("ldap", $howmany);
echo html_writer::table($table);

echo "<h3>Last cohort synchronizations</h3>\n";
$table = new html_table();
$table->head = array('Début', 'Fin', 'Action', 'Info');
$table->data = report_up1userstats_syncs("cohort", $howmany);
echo html_writer::table($table);

echo $OUTPUT->footer();
