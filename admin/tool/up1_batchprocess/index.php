<?php

/**
 * Multi-criteria selection and batch processing for courses
 *
 * @package    tool
 * @subpackage up1_batchprocess
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__DIR__))) . "/config.php");
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/batch_form.php');
require_once(__DIR__ . '/batch_lib.php');
require_once(__DIR__ . '/batch_libactions.php');

global $DB, $PAGE;

$action = optional_param('action', '', PARAM_ALPHA);
$coursesid = optional_param_array('c', array(), PARAM_INT);  // which courses to act on
$page      = optional_param('page', 0, PARAM_INT);     // which page to show
$perpage   = optional_param('perpage', 100, PARAM_INT); // how many per page

require_login(get_site());
$PAGE->set_pagelayout('admin');
$PAGE->set_url('/admin/tool/index.php');
$PAGE->set_title(get_string("coursebatchactions", 'tool_up1_batchprocess'));
$PAGE->set_heading(get_string("coursebatchactions", 'tool_up1_batchprocess'));

$preview = array();
$regexp = '';
$replace = '';
$confirm = false;

if ($action) {
    $courses = $DB->get_records_list('course', 'id', $coursesid);
    switch ($action) {
        case 'prefix':
            $prefix = optional_param('batchprefix', '', PARAM_RAW);
            batchaction_prefix($courses, $prefix, true);
            break;

        case 'suffix':
            $suffix = optional_param('batchsuffix', '', PARAM_RAW);
            batchaction_suffix($courses, $suffix, true);
            break;

        case 'regexp':
            $regexp = optional_param('batchregexp', '', PARAM_RAW);
            $replace = optional_param('batchreplace', '', PARAM_RAW);
            $confirm = optional_param('batchconfirm', '', PARAM_BOOL);
            if ($regexp) {
                if ($confirm) {
                    batchaction_regexp($courses, $regexp, $replace, true);
                } else {
                    foreach ($courses as $course) {
                        $preview[$course->id] = preg_replace('/' . $regexp . '/', $replace, $course->fullname);
                    }
                }
            }
            break;

        case 'close':
            batchaction_visibility($courses, 0, false);
            break;

        case 'open':
            batchaction_visibility($courses, 1, false);
            break;

       case 'substitute':
           $rolefrom = optional_param('batchsubstfrom', '', PARAM_INT);
           $roleto = optional_param('batchsubstto', '', PARAM_INT);
           batchaction_substitute($courses, $rolefrom, $roleto, false);
           break;

        case 'archdate':
            $isodate = optional_param('batcharchdate', '', PARAM_RAW);
            $tsdate = isoDateToTs($isodate);
            //** @todo valider la date **
            batchaction_archdate($courses, $tsdate, false);
            break;

        case 'disableenrols':
            batchaction_disable_enrols($courses, false);
            break;
    }
}

$form = new course_batch_search_form();
$data = $form->get_data();
$totalcount = 0;
$courses = null;
if ($data) {
    $courses = get_courses_batch_search($data, "c.fullname ASC", $page, $perpage, $totalcount);
} else if ($coursesid) {
    $courses = $DB->get_records_list('course', 'id', $coursesid);
}

require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('coursebatchactions', '', array(), $CFG->wwwroot . '/admin/tool/up1_batchprocess/index.php');

$settingsnode = $PAGE->settingsnav->find_active_node();
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string("coursebatchactions", 'tool_up1_batchprocess'));

if (empty($courses)) {
    if (is_array($courses)) {
        echo $OUTPUT->heading(get_string("nocoursesyet"));
    }
} else {
?>
    <form id="movecourses" action="index.php" method="post">
        <div class="generalbox boxaligncenter">
            <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>" />
            <table border="0" cellspacing="2" cellpadding="4" class="course-selection">
                <tr>
                    <th><input type="checkbox" name="course-selectall" id="course-selectall" value="0" /></th>
                    <th class="header" scope="col"><?php echo get_string('courses'); ?></th>
                    <?php if ($preview) { ?>
                    <th class="header" scope="col"><?php echo get_string('preview'); ?></th>
                    <?php } ?>
                </tr>
                <?php
                foreach ($courses as $course) {
                    echo '<tr>';
                    echo '<td align="center">';
                    echo '<input type="checkbox" name="c[]" value="' . $course->id . '" class="course-select" />';
                    echo '</td>';
                    $linkcss = $course->visible ? '' : ' class="dimmed" ';
                    $coursename = get_course_display_name_for_list($course);
                    echo '<td><a '.$linkcss.' href="view.php?id='.$course->id.'">'. format_string($coursename) .'</a></td>';
                    if ($preview && isset($preview[$course->id])) {
                        echo "<td>";
                        if ($course->fullname !== $preview[$course->id]) {
                            echo "<strong>" . format_string($preview[$course->id]) . "</strong>";
                        } else {
                            echo '<span class="dimmed_text">' . format_string($preview[$course->id]) . "</span>";
                        }
                        echo "</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </table>
            <fieldset><legend><?php echo get_string('actions'); ?></legend>
                <ul>
                    <li>
                        <button name="action" value="close"><?php echo get_string('close', 'tool_up1_batchprocess'); ?></button>
                    </li>
                    <li>
                        <button name="action" value="open"><?php echo get_string('open', 'tool_up1_batchprocess'); ?></button>
                    </li>
                    <li>
                        <input type="text" name="batchprefix" />
                        <button name="action" value="prefix"><?php echo get_string('prefix', 'tool_up1_batchprocess'); ?></button>
                    </li>
                    <li>
                        <input type="text" name="batchsuffix" />
                        <button name="action" value="suffix"><?php echo get_string('suffix', 'tool_up1_batchprocess'); ?></button>
                    </li>
                    <li>
                        s/<input type="text" name="batchregexp" value="<?php echo htmlspecialchars($regexp); ?>" />/
                        <input type="text" name="batchreplace" value="<?php echo htmlspecialchars($replace); ?>" />/
                        <button name="action" value="regexp">Regexp</button>
                        <?php if ($action === 'regexp') { ?>
                        <label>
                            <input type="checkbox" name="batchconfirm" value="1" />
                            <?php echo get_string('confirm'); ?>
                        </label>
                        <?php } ?>
                    </li>
                    <li>
                        <?php
                        $roles = get_assignableroles();
                        echo "Substituer " . html_select('batchsubstfrom', $roles) . " par " . html_select('batchsubstto', $roles) ;
                        echo '<button name="action" value="substitute">' . 'Substituer' . '</button>';
                        ?>
                    </li>
                    <li>
                        <input type="text" value="<?php echo isoDate(); ?>" name="batcharchdate" />
                        <button name="action" value="archdate">Date archivage</button>
                    </li>
                    <li>
                        <button name="action" value="disableenrols">Désactiver les inscriptions</button>
                    </li>
                </ul>
            </fieldset>
        </div>
    </form>
    <script type="text/javascript">
<?php
    include "batch_js.php";
?>
    </script>
<?php
}

$form->display();
echo $OUTPUT->footer();
