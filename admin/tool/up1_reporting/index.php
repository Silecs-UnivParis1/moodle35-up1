<?php

/**
 * Administrator reporting
 *
 * @package    tool
 * @subpackage up1_reporting
 * @copyright  2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/admin/tool/up1_reporting/locallib.php');
require_once($CFG->dirroot.'/admin/tool/up1_reporting/cattreecountlib.php');

require_once($CFG->libdir.'/adminlib.php');

require_login();
// admin_externalpage_setup('up1_reporting', '', null, '', array('pagelayout'=>'report'));
$parentcat = optional_param('period', 0, PARAM_INT);
$displaycompact = optional_param('compact', true, PARAM_BOOL);

$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);
$url = "$CFG->wwwroot/admin/tool/up1_reporting/index.php";
$PAGE->set_url($url);

require_capability('moodle/site:config', $systemcontext);
if ( ! is_siteadmin() ) {
    error('Only for admins');
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_up1_reporting'));
$periodes = get_parentcat();
if ($parentcat == 0 || array_key_exists($parentcat, $periodes) == FALSE) {
    $parentcat = get_config('local_crswizard','cas2_default_etablissement');
}


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


echo "<h2>Comptages par catégories - niveaux 3 et 4</h2>\n";
echo "<p>Note : pour les étudiants et les enseignants, les comptages sont dédoublonnés au niveau le plus bas (4 = niveau-LMD)
      puis pour le regroupement par Composante (niveau 3), les deux informations sont affichées : inscrits totalisés,
      et inscrits dédoublonnés.</p>";

echo cat_tree_display_table($parentcat, ! $displaycompact);


echo "<h2>Comptages par UFR</h2>\n";
echo "<p>Note : pour les étudiants et les enseignants, les comptages sont faits au niveau de chaque cours, puis totalisés par Composante.</p>";

$table = new html_table();
$table->head = array('UFR', 'Espaces de cours', 'Étudiants inscrits', 'Enseignants inscrits');
$table->data = report_base_counts($parentcat, ! $displaycompact);
echo html_writer::table($table);

echo $OUTPUT->footer();
