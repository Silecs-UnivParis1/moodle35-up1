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

echo $OUTPUT->heading(get_string('pluginname', 'report_up1userstats'));

$url = "$CFG->wwwroot/report/up1userstats/index.php";


echo "<h2>Utilisateurs - authentification et statuts</h2>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_up1userstats_users();
echo html_writer::table($table);

$table = new html_table();
$table->head = array('Affiliation', 'Nb');
$table->data = report_up1userstats_users_by_affiliation();
echo html_writer::table($table);


$linkdetails = html_writer::link(
        new moodle_url('/report/up1userstats/statscohorts.php' ),
        'Détails');
echo "<h2>Cohortes " . $linkdetails . "</h2>\n";
$table = new html_table();
$table->head = array('Items', 'Nb');
$table->data = report_up1userstats_cohorts_generic();
echo html_writer::table($table);

$table = new html_table();
$table->head = array('Catégorie', 'Cohortes', 'Inscriptions', 'C. inscrites');
$table->data = report_up1userstats_cohorts_period();
echo html_writer::table($table);



//***** LAST syncs
$linkdetails = html_writer::link(
        new moodle_url('/report/up1userstats/lastsync.php', array('number'=>50)),
        'Détails');
echo "<h2>Dernières synchronisations ". $linkdetails ." </h2>\n";
$table = new html_table();
$table->head = array('Reference', 'Begin', 'End');
$table->data = report_up1userstats_last_sync();
echo html_writer::table($table);


/*  $table->head  = array($strissue, $strstatus, $strdesc, $strconfig);
    $table->size  = array('30%', '10%', '50%', '10%' );
    $table->align = array('left', 'left', 'left', 'left');
    $table->attributes = array('class'=>'scurityreporttable generaltable');
    $table->data  = array();
    $table->data[] = $row;
*/

echo $OUTPUT->footer();
