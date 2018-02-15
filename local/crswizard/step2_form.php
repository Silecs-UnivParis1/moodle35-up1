<?php

/**
 * @package    local
 * @subpackage crswizard
 * @copyright  2012 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once('lib_wizard.php');

class course_wizard_step2_form extends moodleform {

    function definition() {
        global $OUTPUT, $SESSION;

        $isnew = TRUE;
        if (isset($SESSION->wizard['idcourse'])) {
            $isnew = FALSE;
        }

        $urlPfixe = $SESSION->wizard['urlpfixe'];
        $urlfixeExist = false;
        if (isset($SESSION->wizard['form_step2']['modelurlfixe']) && $SESSION->wizard['form_step2']['modelurlfixe'] != '') {
            $urlfixeExist = true;
        }

        $mform = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];

        if (isset($SESSION->wizard['form_step1']['erreurs'])) {
            $mform->addElement('html', html_writer::tag('div', $SESSION->wizard['form_step1']['erreurs'], array('class' => 'fitem')));
        }

        $bockhelpE2 = get_string('bockhelpE2', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE2, array('class' => 'fitem')));

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header', 'categoryheader', get_string('categoryblock', 'local_crswizard'));
        $mform->addElement(
                'select', 'category', '', wizard_get_mydisplaylist(),
                array('class' => 'transformIntoSubselects')
        );
        $mform->addRule('category', "Ces 4 champs doivent tous être remplis.", 'required', null, 'client');
        $mform->addRule('category', "Ces 4 champs doivent tous être remplis.", 'nonzero', null, 'client');

        if (isset($SESSION->wizard['form_step2']['lostcategory']) && $SESSION->wizard['form_step2']['lostcategory'] != '') {
            $lostcategory = '<div class="felement fselect error"><span class="error">'
                . 'Attention, catégorie de rattachement inexistante pour la période ';
            $periode = wizard_get_default_periode();
            if ($periode) {
                $lostcategory .= $periode->name;
            }
            $lostcategory .= '</div>';
            $mform->addElement('html', $lostcategory);
        }

        $mform->addElement('header', 'general', get_string('generalinfoblock', 'local_crswizard'));

        $coursegeneralhelp = get_string('coursegeneralhelp', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursegeneralhelp, array('class' => 'fitem')));

        $mform->addElement('text', 'fullname', get_string('fullnamecourse', 'local_crswizard'), 'maxlength="254" size="50"');
        //$mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('text', 'shortname', get_string('shortnamecourse', 'local_crswizard'), 'maxlength="100" size="20"');
        //$mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_MULTILANG);

        $mform->addElement('editor', 'summary_editor', get_string('coursesummary', 'local_crswizard'), null, $editoroptions);
        //$mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header', 'parametre', get_string('coursesettingsblock', 'local_crswizard'));

        $coursesettingshelp = get_string('coursesettingshelp', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursesettingshelp, array('class' => 'fitem')));

        $mform->addElement('date_selector', 'startdate', get_string('coursestartdate', 'local_crswizard'));
        // $mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', time());

        $datefermeture = 'up1datefermeture';
        $mform->addElement('date_selector', $datefermeture, get_string('up1datefermeture', 'local_crswizard'));
        $mform->setDefault($datefermeture, time());

        $mform->addElement('header', 'URL', 'Souhaitez-vous utiliser une URL pérenne ?');
        $mform->setExpanded('URL');

        $urloklabel = 'Je souhaite utiliser une URL pérenne';
        if ($isnew == false && $SESSION->wizard['form_step2']['urlok'] == 1) {
            $urloklabel = 'J\'utilise une URL pérenne';
        }
        $mform->addElement('checkbox', 'urlok', $urloklabel);
        $mform->setDefault('urlok', 0);

        $infoHtml = wizard_urlfixe_info();
        $mform->addElement('html', $infoHtml[1]);

        if ($urlfixeExist) {
            $htmlMyUrlModel = '<div id="myurlmodel">L\'URL pérenne de l\'EPI modèle sélectionné est la suivante : <b>'.
                $urlPfixe . $SESSION->wizard['form_step2']['modelurlfixe'].'</b></div>';
            $mform->addElement('hidden', 'modelurlfixe', null);
            $mform->setType('modelurlfixe', PARAM_MULTILANG);
            $mform->setConstant('modelurlfixe', $SESSION->wizard['form_step2']['modelurlfixe']);

            $mform->addElement('html', $htmlMyUrlModel);
            $mform->addElement('radio', 'urlmodel', '', 'Je souhaite transférer cette URL pérenne au nouveau cours',  'fixe');
            $mform->addElement('radio', 'urlmodel', '', 'Je souhaitre utiliser une autre URL pérenne',  'myurl');
        }

        $mform->addElement('text', 'myurl', '<span title="Partie fixe de l\'URL">' . $urlPfixe . '</span>',
            'maxlength="50" size="50" title="Extension à compléter par l\'expression résumée de votre cours en 50 caractères maximum"');
        $mform->setType('myurl', PARAM_MULTILANG);
        $mform->addElement('html', $infoHtml[2]);

        /**
         * liste des paramètres de cours ayant une valeur par défaut
         */
        // si demande de validation à 0
        if ($isnew) {

            $courseconfig = get_config('moodlecourse');

            $mform->addElement('hidden', 'visible', null);
            $mform->setType('visible', PARAM_INT);
            $mform->setConstant('visible', 0);

            $mform->addElement('hidden', 'format', null);
            $mform->setType('format', PARAM_ALPHANUM);
            $mform->setConstant('format', $courseconfig->format);

            $mform->addElement('hidden', 'coursedisplay', null);
            $mform->setType('coursedisplay', PARAM_INT);
            $mform->setConstant('coursedisplay', COURSE_DISPLAY_SINGLEPAGE);

            $mform->addElement('hidden', 'numsections', null);
            $mform->setType('numsections', PARAM_INT);
            $mform->setConstant('numsections', $courseconfig->numsections);

            $mform->addElement('hidden', 'hiddensections', null);
            $mform->setType('hiddensections', PARAM_INT);
            $mform->setConstant('hiddensections', $courseconfig->hiddensections);

            $mform->addElement('hidden', 'newsitems', null);
            $mform->setType('newsitems', PARAM_INT);
            $mform->setConstant('newsitems', $courseconfig->newsitems);

            $mform->addElement('hidden', 'showgrades', null);
            $mform->setType('showgrades', PARAM_INT);
            $mform->setConstant('showgrades', $courseconfig->showgrades);

            $mform->addElement('hidden', 'showreports', null);
            $mform->setType('showreports', PARAM_INT);
            $mform->setConstant('showreports', $courseconfig->showreports);

            $mform->addElement('hidden', 'maxbytes', null);
            $mform->setType('maxbytes', PARAM_INT);
            $mform->setConstant('maxbytes', $courseconfig->maxbytes);

            $mform->addElement('hidden', 'groupmode', null);
            $mform->setType('groupmode', PARAM_INT);
            $mform->setConstant('groupmode', $courseconfig->groupmode);

            $mform->addElement('hidden', 'groupmodeforce', null);
            $mform->setType('groupmodeforce', PARAM_INT);
            $mform->setConstant('groupmodeforce', $courseconfig->groupmodeforce);

            $mform->addElement('hidden', 'defaultgroupingid', null);
            $mform->setType('defaultgroupingid', PARAM_INT);
            $mform->setConstant('defaultgroupingid', 0);

            $mform->addElement('hidden', 'lang', null);
            $mform->setType('lang', PARAM_INT);
            $mform->setConstant('lang', $courseconfig->lang);
        }

        // à supprimer ?
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
//--------------------------------------------------------------------------------
        $mform->addElement('hidden', 'stepin', null);
        $mform->setType('stepin', PARAM_INT);
        $mform->setConstant('stepin', 2);

//--------------------------------------------------------------------------------
        $labelprevious = get_string('previousstage', 'local_crswizard');
        if (!$isnew) {
            $labelprevious = get_string('upcancel', 'local_crswizard');
        }
        $buttonarray = array();
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($SESSION->wizard['wizardurl'], array('stepin' => 1)),
                $labelprevious, array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_3', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        if (array_key_exists('shortname', $data)) {
            $data['shortname'] = trim($data['shortname']);
        }
        $data['fullname'] = trim($data['fullname']);
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            $this->validation_shortname($data['shortname'], $errors);
            $this->validation_category($data['category'], $errors);
        }
        $urlok = 0;
        $myurl = '';
        $urlmodel = '';
        $modelurlfixe = '';
        if (isset($data['urlok'])) {
            $urlok = $data['urlok'];
        }
        if (isset($data['myurl'])) {
            $myurl = $data['myurl'];
        }
        if (isset($data['urlmodel'])) {
            $urlmodel = $data['urlmodel'];
        }
        if (isset($data['modelurlfixe'])) {
            $modelurlfixe = $data['modelurlfixe'];
        }
        $this->validation_myurl($urlok, $myurl, $urlmodel, $modelurlfixe, $errors);
        return $errors;
    }

    private function validation_shortname($shortname, &$errors) {
        global $DB, $SESSION;

        $foundcourses = $DB->get_records('course', array('shortname' => $shortname));
        if (isset($SESSION->wizard['idcourse'])) {
            unset($foundcourses[$SESSION->wizard['idcourse']]);
        }

        if ($foundcourses) {
            foreach ($foundcourses as $foundcourse) {
                $foundcoursenames[] = $foundcourse->fullname;
            }
            $foundcoursenamestring = implode(',', $foundcoursenames);
            $errors['shortname'] = get_string('shortnametaken', '', $foundcoursenamestring);
        }
        return $errors;
    }

    private function validation_category($idcategory, &$errors) {
        global $DB;

        $category = $DB->get_record('course_categories', array('id' => $idcategory));
        if ($category) {
            if ($category->depth < 3) {
                $errors['category'] = get_string('categoryerrormsg1', 'local_crswizard');
            }
        } else {
            $errors['category'] = get_string('categoryerrormsg2', 'local_crswizard');
        }
        return $errors;
    }

    private function validation_myurl($urlok, $myurl, $urlmodel, $modelurlfixe, &$errors) {
        global $DB, $SESSION;
        if ($urlok == 1) {
            $url = trim($myurl);
            if ($modelurlfixe != '' && $urlmodel == '') {
                $errors['urlmodel'] = 'Vous devez sélectionner une URL pérenne';
            } elseif($urlmodel != 'fixe') {
                if ($url == '') {
                    $errors['myurl'] = 'Vous devez défnir une L’URL pérenne';
                } else {
                    $idcourse = false;
                    if (isset($SESSION->wizard['idcourse'])) {
                        $idcourse = $SESSION->wizard['idcourse'];
                    }
                    $myerrors = wizard_form2_validation_myurl($url, $idcourse);
                    if (count($myerrors)) {
                        $errors['myurl'] = implode(',<br/>', $myerrors);
                    }
                }
            }
        }
    }
}
