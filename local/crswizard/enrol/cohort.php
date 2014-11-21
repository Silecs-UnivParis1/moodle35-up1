<?php
/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../../config.php');
require_once('../lib_wizard.php');
require_once('../libaccess.php');

require_login();

if (isset($SESSION->wizard['idcourse'])) {
    $idcourse = $SESSION->wizard['idcourse'];
    wizard_require_update_permission($idcourse, $USER->id);
    $course = $DB->get_record('course', array('id'=>$idcourse), '*', MUST_EXIST);
    require_login($course);
    $coursecontext = context_course::instance($course->id);
    $PAGE->set_context($coursecontext);
    $pageparams = array('id'=>$idcourse);
    $PAGE->set_url('/local/crswizard/update/index.php', $pageparams);
    $streditcoursesettings = get_string("editcoursesettings");
    $PAGE->navbar->add($streditcoursesettings);
} else {
    $systemcontext   = context_system::instance();
    $PAGE->set_context($systemcontext);
    wizard_require_permission('creator', $USER->id);
    $PAGE->set_url('/local/crswizard/index.php');
}

$PAGE->set_title($SESSION->wizard['form_step2']['fullname'] . ': ' . get_string('cohort', 'local_crswizard'));
$PAGE->requires->js(new moodle_url('/local/jquery/jquery.js'), true);
$PAGE->requires->js(new moodle_url('/local/jquery/jquery-ui.js'), true);
$PAGE->requires->js(new moodle_url('/local/widget_groupsel/groupsel.js'), true);
$PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

echo $OUTPUT->header();
if (isset($SESSION->wizard['idcourse'])) {
    echo $OUTPUT->box(get_string('upwizardcourse', 'local_crswizard'), 'titlecrswizard');
} else {
    echo $OUTPUT->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
}

$titlepage = get_string('enrolcohorts', 'local_crswizard');
if (isset($SESSION->wizard['idcourse'])) {
    if ($SESSION->wizard['wizardcase'] == 2) {
        $titlepage = get_string('upenrolcohortscase2', 'local_crswizard');
    } else {
        $titlepage = get_string('upenrolcohortscase3', 'local_crswizard');
    }
}
echo $OUTPUT->box($titlepage, 'titlecrswizard');

echo $OUTPUT->box(get_string('bockhelpE5', 'local_crswizard'), '');

$myconfig = new my_elements_config();
$labels = $myconfig->role_cohort;
$roles = wizard_role($labels);


$up1codes = array(); // Apogee codes to find related groups and suggest them to user
if (isset($SESSION->wizard['form_step2']['all-rof'])) {
    $rattachements = $SESSION->wizard['form_step2']['all-rof'];
    foreach ($rattachements as $path => $rattachement) {
        $up1code = '';
        if (isset($rattachement['object']->code)) {
            $up1code = $rattachement['object']->code;
        }
        if ($up1code != '') {
            $up1codes[] = $up1code;
        }
    }
}

if (isset($SESSION->wizard['form_step5']['groupmsg'])) {
    $msgcohorts = $SESSION->wizard['form_step5']['groupmsg'];
   foreach ($msgcohorts as $role => $msg) {
       echo '<p><b>' . get_string('role', 'local_crswizard') . ' "'
        . format_string(get_string($role, 'local_crswizard')) . '" : </b></p>';
       explain_equivalent_cohorts($SESSION->wizard['form_step5']['groupmsg'][$role]);
    }
}

echo '<form action="' . $CFG->wwwroot . $SESSION->wizard['wizardurl'] . '" method="post">';
?>
<div class="role" style="display: none">
    <h3><?php echo get_string('role', 'local_crswizard');?></h3>
    <select name="role" size="1" id="group-role">
        <?php
        foreach ($roles as $r) {
            $label = $r['name'];
            if (array_key_exists($r['shortname'], $labels)) {
                $label = $labels[$r['shortname']];
            }
            echo '<option value="' . s($r['shortname']) . '">' . format_string(get_string($label, 'local_crswizard')) . '</option>';
        }
        ?>
	</select>
</div>

<div id="group-select">
    <div class="widgetselect-panel-left">
        <h3><?php echo get_string('findcohort', 'local_crswizard'); ?></h3>
        <input type="text" class="group-selector" name="something" data-inputname="group" size="50"
               placeholder="<?php echo s(get_string('cohortname', 'local_crswizard')); ?>" />
        <div>Recherchez un groupe d'étudiant et cliquez sur + pour l'ajouter aux Groupes sélectionnés
         <p>N'inscrivez <u>que</u> les étudiants devant participer à votre cours - et non ceux du diplôme ou de l'UFR. 
         <br>Vous pourrez paramétrer d'autres types d'accès à l'étape suivante.
        </div>
    </div>
    <div class="widgetselect-panel-left">
        <h3><?php echo get_string('selectedcohort', 'local_crswizard'); ?></h3>
        <div class="group-selected"></div>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
jQuery(document).ready(function () {
    $('#group-select').autocompleteGroup({
        urlGroups: '<?php echo new moodle_url('/local/mwsgroups/service-groups.php'); ?>',
        urlUserToGroups: '<?php echo new moodle_url('/local/mwsgroups/service-userGroups.php'); ?>',
        minLength: 4,
        wsParams: { maxRows: 10 },
        labelMaker: function(item) {
            var label = $('#group-role > option:selected').text();
            var chaine = item.label;
            var nameg=chaine.substring(3,chaine.indexOf("</b>"));
            var nbi = '';
            var reg=new RegExp("[0-9]+ inscrits","g");
            nbi = chaine.match(reg);
            return nameg  + ' — ' + nbi +' (' + label + ')';
        },
        showRelatedGroupsOnEmpty: <?php echo json_encode($up1codes); ?>,
        preSelected: <?php echo wizard_preselected_cohort(); ?>
    });

    $('#group-role').on('change', function() {
        var sel = $(this).val();
        $('#group-select').data('autocompleteGroup').settings.fieldName = 'group[' + sel + ']';
    });
    $('#group-role').change();
});
//]]>
</script>

<?php
echo '<input type="hidden" id="cohort" value="1" />';
if (isset($SESSION->wizard['idcourse'])) {
    require __DIR__ . '/../update/footer.php';
} else {
    require __DIR__ . '/footer.php';
}
