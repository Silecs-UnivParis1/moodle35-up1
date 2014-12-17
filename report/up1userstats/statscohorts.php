<?php

/**
 * UP1 Users Statistics
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

// Print the header.
admin_externalpage_setup('reportup1userstats', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_up1userstats') . ' : cohortes');
$url = "$CFG->wwwroot/report/up1userstats/statscohorts.php";

echo "<h2>Cohortes</h2>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_up1userstats_cohorts_generic();
echo html_writer::table($table);

$table = new html_table();
$table->head = array('Catégorie', 'Cohortes', 'Inscriptions', 'C. inscrites');
$table->data = report_up1userstats_cohorts_category();
echo html_writer::table($table);

$table = new html_table();
$table->head = array('Période', 'Cohortes', 'Inscriptions', 'C. inscrites');
$table->data = report_up1userstats_cohorts_period();
echo html_writer::table($table);


echo "<h2>Effectifs</h2>\n";

//***** TOP NN cohorts
$linkdetails = html_writer::link(
        new moodle_url('/report/up1userstats/topcohorts.php', array('number'=>50)),
        'Détails');
echo "<h3>Cohortes - top 5 ". $linkdetails ." </h3>\n";
$table = new html_table();
$table->head = array('Effectif', 'Id', 'Nom');
$table->data = report_up1userstats_cohorts_top(5, false);
echo html_writer::table($table);

echo "<h3>Cohortes - top 3 par préfixe</h3>\n";
$table = new html_table();
$table->head = array('Effectif', 'Id', 'Nom');
$table->data = report_up1userstats_cohorts_top_by_prefix(3);
echo html_writer::table($table);
