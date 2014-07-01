<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Display course up1teacherstats page
 *
 * @package    report
 * @subpackage up1teacherstats
 * @copyright  2012-2014 Silecs {@link http://www.silecs.info}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * derived from package report_outline
 */

require('../../config.php');
require_once($CFG->libdir.'/custominfo/lib.php');
require_once($CFG->dirroot.'/local/up1_metadata/lib.php');
require_once(__DIR__ . '/locallib.php');

global $DB, $PAGE, $OUTPUT;
 /* @var $PAGE moodle_page */

$id = required_param('id', PARAM_INT);       // course id
$layout = optional_param('layout', 'report', PARAM_ALPHA); // default layout=report
if ($layout != 'popup') {
    $layout = 'report';
}

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$PAGE->set_course($course);

$PAGE->set_url('/report/up1teacherstats/index.php', array('id'=>$id));
$PAGE->set_pagelayout($layout);
$PAGE->requires->css(new moodle_url('/report/up1teacherstats/styles.css'));

$site = get_site();
$strreport = get_string('pluginname', 'report_up1teacherstats');
$pagename = up1_meta_get_text($course->id, 'up1nomnorme', false);
if ( ! $pagename ) {
    $pagename = $course->fullname;
}

$PAGE->set_title($pagename); // $course->shortname .': '. $strreport); // tab title
$PAGE->set_heading($site->fullname);
echo $OUTPUT->header();

echo "<h2>" . $pagename . "</h2>\n";



echo "<h3>Utilisateurs inscrits dans l’EPI</h3>\n";
//html_table_stats_enrolments($course);

echo "<p>Nombre total d'utilisateurs inscrits :</p>";

$table = new html_table();
$table->head = array('Rôle', 'Nb total', 'Nb d\'actifs*', ' Pourcentage d\'actifs',
    'Jamais connecté', 'Pourcentage d\'inactifs');
$table->data = teacherstats_enrolments_roles($course->id);
echo html_writer::table($table);

echo "<p>* Utilisateurs actifs = utilisateurs s’étant connecté à l’EPI au moins une fois.</p>";

$table = new html_table();
$table->head = array('Groupe', 'Total', 'Actifs', 'Actifs %', 'Jamais', 'Jamais %');
$table->data = teacherstats_enrolments_groups($course->id);
echo html_writer::table($table);


echo "<h3>Fréquentation</h3>\n";

echo "<h4>Pour le mois écoulé</h4>\n";
//html_table_stats_daily_month($course);
teacherstats_graph_connections($course->id, 5, "Graphe de connexions sur 5 semaines");
//teacherstats_graph_connections(1, 5, "Graphe de connexions sur 5 semaines");

echo "<h4>Depuis l’ouverture</h4>\n";
teacherstats_graph_connections($course->id, 19, "Graphe de connexions sur 9 mois");
// teacherstats_graph_connections(1, 19, "Graphe de connexions sur 9 mois");
//html_table_stats_weekly_opening($course);



echo "<h3>Ressources les plus consultées</h3>\n";
$table = new html_table();
$table->head = array('Rang', 'Titre', 'Type', 'Nombre d’affichages');
$table->data = teacherstats_resources_top($course->id, 10);
echo html_writer::table($table);

$linkdetails = html_writer::link(
        new moodle_url('/report/log/index.php', array('id' => $course->id)),
        'Détails');
echo "<h3>Activités en cours " . $linkdetails . "</h3>\n";

echo "<h4>Devoirs</h4>\n";
$stats = teacherstats_assignments($course->id);
$table = new html_table();
$table->head = array('Nom', 'Date limite', 'Rendus', 'Évalués');
$table->data = $stats['global'];
echo html_writer::table($table);

$table = new html_table();
$table->head = array('Nom', 'Groupes', 'Date limite', 'Rendus', 'Évalués');
$table->data = $stats['groups'];
echo html_writer::table($table);


echo "<h4>Glossaire, base de données, forum, wiki, chat</h4>\n";
$table = new html_table();
$table->head = array('Titre', 'Type', 'Contributions', 'Contributeurs uniques');
$table->data = teacherstats_activities($course->id);
echo html_writer::table($table);

echo "<h4>Tests, Sondage, Feedback, Consultation</h4>\n";
$table = new html_table();
$table->head = array('Titre', 'Type', 'Réponses', 'Fermeture');
$table->data = teacherstats_questionnaires($course->id);
echo html_writer::table($table);

echo $OUTPUT->footer();
