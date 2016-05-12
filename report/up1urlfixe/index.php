<?php

/**
 * Version info
 *
 * @package    report
 * @subpackage up1urlfixe
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/report/up1urlfixe/locallib.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

// Print the header.
admin_externalpage_setup('reportup1urlfixe', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading('Url fixes');

$url = "$CFG->wwwroot/report/up1urlfixe/index.php";

echo "<h3>Doublons</h3>\n";
$table = new html_table();
$table->head = array('Nombre', 'Url fixe', 'Cours', 'ID cours');
$table->data = report_up1urlfixe_doublons();
echo html_writer::table($table);


echo "<h3>Cours supprimés</h3>\n";
$table = new html_table();
$table->head = array('Url fixe', 'Cours ?', 'ID cours');
$table->data = report_up1urlfixe_supprimes();
echo html_writer::table($table);


echo $OUTPUT->footer();
