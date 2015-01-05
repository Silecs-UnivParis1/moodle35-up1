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

require('../../config.php');
require_once($CFG->dirroot.'/report/up1reporting/locallib.php');
require_once($CFG->libdir.'/adminlib.php');

require_login();

$howmany = optional_param('number', 100, PARAM_INT);

// Print the header.
admin_externalpage_setup('reportup1reporting', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading('Les '.$howmany. ' derniers calculs statistiques UP1');

$url = "$CFG->wwwroot/report/up1reporting/index.php";

echo "<h3>Derniers calculs en base</h3>\n";
$table = new html_table();
$table->head = array('Date', 'Critères', 'Noeuds', 'Enregistrements');
$table->data = up1reporting_last_records($howmany);
echo html_writer::table($table);

echo $OUTPUT->footer();
