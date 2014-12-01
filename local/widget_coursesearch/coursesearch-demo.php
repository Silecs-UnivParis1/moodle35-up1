<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

global $OUTPUT, $PAGE;

require_login();

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/widget_coursesearch/coursesearch-demo.php');
$PAGE->set_title('Démo de la recherche de cours');

$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_coursesearch/coursesearch.js'), true);
// Cf http://paris1-dev.silecs.info/wiki/doku.php/catalogue_des_cours:liste_des_cours pour les paramètres possibles
$PAGE->requires->js_init_code('
$(document).ready(function () {
    $("#widget-coursesearch").coursesearch(
        {
            //topcategory: 22,
            //node: "",
            // enrolled: "Dupond",
            fieldset: "Recherche principale",
            "fields": "*" // default: every category and every field
            // "fields": { "Identification": ["up1code", ...], "Diplome": "*" }, ...
        }
    );
});');

$PAGE->set_pagelayout('admin');

echo $OUTPUT->header();
echo $OUTPUT->heading('Démo de la recherche de cours');

?>
<div id="widget-coursesearch">
</div>
<?php

echo $OUTPUT->footer();
