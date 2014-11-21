<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

class course_wizard_step1_form {
    public function display() {
        global $OUTPUT;
        $url = '/local/crswizard/index.php';

        ?>

<?php echo get_string('blocTitleS1', 'local_crswizard');?>

<div style="margin:5em 20px 20px 20px;">
    <?php echo get_string('blocIntroS1', 'local_crswizard');?>

    <div style="margin-top: 1.5em;">

<table style="border-top: 0">
  <tr><td>
        <?php
        /**
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 2, 'wizardcase' => 1)), get_string('wizardcase1', 'local_crswizard'),
                    'get', array('disabled' => 'disabled')
        );
        **/
        ?>
  </td></tr>
  <tr><td>
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 1, 'wizardcase' => 2)), get_string('wizardcase2', 'local_crswizard'), 'get'
        );
        ?>
     </td><td style="padding-bottom: 10px">(CM, TD, Diplôme dans la maquette UFR)</td>
  </tr>
  <tr><td>
        <?php
        echo $OUTPUT->single_button(
                new moodle_url($url, array('stepin' => 1, 'wizardcase' => 3)), get_string('wizardcase3', 'local_crswizard'), 'get'
        );
        ?>
     </td><td style="padding-bottom: 10px">(Enseignement transversal, Bibliothèque, Formation des personnels)</td>
  </td></tr>
</table>

    </div>
</div>
    <?php
    }
}
