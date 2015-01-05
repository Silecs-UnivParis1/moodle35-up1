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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/report/up1reporting/locallib.php');
require_once($CFG->dirroot.'/report/up1reporting/cattreecountlib.php');

require_once($CFG->libdir.'/adminlib.php');

require_login();
// admin_externalpage_setup('up1reporting', '', null, '', array('pagelayout'=>'report'));
$parentcat = optional_param('period', 0, PARAM_INT);
$displaycompact = optional_param('compact', true, PARAM_BOOL);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$url = "$CFG->wwwroot/report/up1reporting/index.php";
$PAGE->set_url($url);

require_capability('moodle/site:config', $systemcontext);
if ( ! is_siteadmin() ) {
    error('Only for admins');
}
// Print the header.
admin_externalpage_setup('reportup1reporting', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_up1reporting'));

$periodes = get_parentcat();
if ($parentcat == 0 || array_key_exists($parentcat, $periodes) == FALSE) {
    $parentcat = get_config('local_crswizard','cas2_default_etablissement');
}
global $DB;
$grandparentcat = $DB->get_field('course_categories', 'parent', array('id' => $parentcat));


// Main page parameters: parentcat, display mode (compact/complet)
echo '<form method="GET">Période&nbsp/&nbspÉtablissement&nbsp:&nbsp';
echo html_writer::select($periodes, 'period', $parentcat, false);
echo "&nbsp  &nbsp";
echo '<input type="submit" value="ok">';
echo '</form>';

$displaymode = array(true => 'Compact', false => 'Complet');
echo "<span>Affichage actuel : " . $displaymode[$displaycompact] . "</span> <br />";
$paramsurl = new moodle_url($url, array('compact' => ! $displaycompact, 'period' => $parentcat));
echo $OUTPUT->single_button($paramsurl, $displaymode[ ! $displaycompact], 'get');
?>

<h2>Statistiques présentées selon l'arbre hybride Catégories / ROF</h2>

<style type="text/css">
.jqtree-hidden {
    display: inherit;
}
</style>

<script type="text/javascript" src="<?php echo new moodle_url('/local/mwscoursetree/widget.js'); ?>"></script>
<div class="coursetree" data-root="<?php echo "/cat" . $grandparentcat; ?>" data-title="1" data-stats="1" data-debug="0"></div>


<?php
$linkdetails = html_writer::link(
        new moodle_url('/report/up1reporting/lastrecords.php', array('number' => 100)),
        'détails');
echo "<p>" . up1reporting_count_timestamps() . " enregistrements (" .$linkdetails. ").</p>\n";
$table = new html_table();
$table->head = array('Dernier calcul', 'Critères', 'Noeuds', 'Enregistrements');
$table->data = up1reporting_last_records(1);
echo html_writer::table($table);


/*
echo "<h2>Statisitiques par catégories - niveaux 3 et 4</h2>\n";
echo "<p>Note : pour les étudiants et les enseignants, les comptages sont dédoublonnés au niveau le plus bas (4 = niveau-LMD)
      puis pour le regroupement par Composante (niveau 3), les deux informations sont affichées : inscrits totalisés,
      et inscrits dédoublonnés.</p>";

echo cat_tree_display_table($parentcat, ! $displaycompact);
*/


echo $OUTPUT->footer();
