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

class course_wizard_step2_rof_form extends moodleform {

    function definition() {
        global $SESSION, $USER;
        $isnew = TRUE;
        $urlPfixe = $SESSION->wizard['urlpfixe'];
        $urlfixeExist = false;
        if (isset($SESSION->wizard['form_step2']['modelurlfixe']) && $SESSION->wizard['form_step2']['modelurlfixe'] != '') {
            $urlfixeExist = true;
        }

        $mform = $this->_form;

        $editoroptions = $this->_customdata['editoroptions'];

        $bockhelpE2 = get_string('bockhelpE2Rof1', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $bockhelpE2, array('class' => 'fitem')));

/// form definition with new course defaults
//--------------------------------------------------------------------------------
        $mform->addElement('header', 'categoryheader', get_string('categoryblockE2F', 'local_crswizard'));

        $classreadonly = '';
        $messagerof = '';
        $rofeditor_permission = true;
        if (isset($SESSION->wizard['idcourse'])) {
            $rofeditor_permission = wizard_has_rofreferenceeditor_permission($SESSION->wizard['idcourse'], $USER->id);
            $isnew = FALSE;
        }
        if ($rofeditor_permission) {
            $default_cat = get_config('local_crswizard','cas2_default_etablissement');
            $mform->addElement(
                'select', 'category', '', wizard_get_catlevel2(),
                array(
                    'class' => 'transformIntoSubselects',
                    'data-labels' => '["Période :", "Établissement :"]'
                )
            );
            $mform->setDefault('category', $default_cat);
        } else {
            $tabfreeze = array();
            $mform->addElement('text', 'rofyear', 'Période : ');
            $mform->addElement('text', 'rofyear', 'Période : ');
            $mform->setConstant('rofyear', $SESSION->wizard['form_step2']['rofyear']);
            $tabfreeze[] = 'rofyear';

            $mform->addElement('text', 'rofestablishment', 'Établissement : ');
            $mform->setType('rofestablishment', PARAM_TEXT);
            $mform->setConstant('rofestablishment', $SESSION->wizard['form_step2']['rofestablishment']);
            $tabfreeze[] = 'rofestablishment';
            $mform->hardFreeze($tabfreeze);

            $mform->addElement('hidden', 'category', null);
            $mform->setType('category', PARAM_INT);

            $classreadonly = 'readonly';
            $messagerof = '<div><span>' .get_string('uprofreadonlymess', 'local_crswizard')  . '</span></div>';
        }

        $labelrof =  '<br/><div class="fitemtitle required mylabel"><label>Elément pédagogique : *</label></div>';
        $mform->addElement('html',  $labelrof);
        $mform->addElement('html', '<div id="mgerrorrof"></div>');

        $preselected = wizard_preselected_rof();
        $codeJ = '<script type="text/javascript">' . "\n"
            . '//<![CDATA['."\n"
            . 'jQuery(document).ready(function () {'
            . '$(\'#items-selected\').autocompleteRof({';
            if ($classreadonly !='') {
                $codeJ .= 'readonly: true,';
            }
            $codeJ .= 'preSelected: '.$preselected
            .'});'
            . '});'
            . '//]]>'. "\n"
            . '</script>';

        // ajout du selecteur ROF
        $rofseleted = '<div class="by-widget"><h3>Rechercher un élément pédagogique</h3>'
            . '<div class="item-select ' . $classreadonly . '" id="choose-item-select"></div>'
            . '</div>'
            . '<div class="block-item-selected" style="position: relative">'
            . '<div style="position: absolute; bottom: 0;">'
	    . "  Un même espace de cours peut être rattaché à"
	    . "  plusieurs niveaux sans limite de nombre"
	    . "  <br>Exemples : <div class='indented-block-top'>"
	    . "      M1 et M2 histoire, M2 géographie"
	    . "      <br>CM et TD"
	    . "      <br>Licence et Bi-licence</div>"
	    . "</div>"
            . '<h3>Éléments pédagogiques sélectionnés</h3>'
            . '<div id="items-selected">'
            . '<div id="items-selected1"><span>' . get_string('rofselected1', 'local_crswizard') . '</span></div>'
            . '<div id="items-selected2"><span>' . get_string('rofselected2', 'local_crswizard') . '</span></div>'
            . $messagerof
            . '</div>'
            . '</div>'
            . $codeJ;

        $mform->addElement('html', $rofseleted);

        $mform->addElement('header', 'general', get_string('generalinfoblock', 'local_crswizard'));
        $mform->setExpanded('general');
        $coursegeneralhelp = get_string('coursegeneralhelpRof', 'local_crswizard');
        $mform->addElement('html', html_writer::tag('div', $coursegeneralhelp, array('class' => 'fitem')));

        $labelname = '';
        $valcomplement = '';
        if (isset($SESSION->wizard['form_step2']['fullname'])) {
            $labelname = $SESSION->wizard['form_step2']['fullname'];
        }
        if (isset($SESSION->wizard['form_step2']['complement'])) {
            $valcomplement = $SESSION->wizard['form_step2']['complement'];
        }

        $htmlcn = '<div id="fgroup_id_coursename" class="fitem required fitem_fgroup">'
            . '<div class="fitemtitle">'
            . '<div class="fgrouplabel">'
            . '<label>' . get_string('fullnamecourse', 'local_crswizard') . ' * </label>'
            . '</div>'
            . '</div>'
            . '<fieldset class="felement fgroup">'
            . '<span id="fullnamelab">' . $labelname . ' - </span>'
            . '<label class="accesshide" for="id_complement"> </label>'
            . '<input maxlength="254" size="50" name="complement" type="text" '
            . 'id="id_complement" value="' . $valcomplement . '">'
            . '</fieldset>'
            . '</div>';
        $mform->addElement('html',$htmlcn);

        $mform->addElement('hidden', 'fullname', null, array('id' => 'fullname'));
        $mform->setType('fullname', PARAM_MULTILANG);

        $mform->addElement('editor', 'summary_editor', get_string('coursesummary', 'local_crswizard'), null, $editoroptions);
        //$mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);

        $mform->addElement('header', 'parametre', get_string('coursesettingsblock', 'local_crswizard'));
        $mform->setExpanded('parametre');

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

        $mform->addElement('checkbox', 'urlok', 'Je souhaite utiliser une URL pérenne.' );
        $mform->setDefault('urlok', 0);

        $htmlUrl = '<div id="blocUrl" class="cache"><div class="urlHeader">Qu’est-ce qu’une URL pérenne ? </div>';
        $htmlUrl .= '<p>Une URL pérenne (ou fixe) est un lien permanent que vous pourrez transmettre à '
            . 'vos étudiants et à vos collègues inscrits à votre EPI. <br/><font color="red">Fonctionnant comme un alias, l’URL '
            . 'pérenne permet notamment de réutiliser une même adresse d’une année sur l’autre : </font></p>';
        $htmlUrl .= '<ul>'
            . '<li>lors de la duplication, l’URL pérenne pointera automatiquement vers le nouvel EPI,</li>'
            . '<li>les liens créés avec votre URL pérenne resteront à jour.</li>'
            . '</ul>';
        $htmlUrl .= '<div class="urlHeader">Comment rédiger votre URL pérenne ?</div>';
        $htmlUrl .= '<p>L’URL pérenne doit être complète, courte et explicite.<br/>'
            . 'Elle doit notamment permettre, le cas échéant, d\'identifier l\'UFR et le niveau de l\'EPI '
            . '(à la fois pour éviter toute confusion avec un EPI au titre similaire et pour situer l\'EPI dans le ROF).</p>';
        $htmlUrl .= '<p><u>Voici quelques exemples d’URL pérennes bien écrites : </u></p>'
            . '<ul>'
            . '<li>02-L2-economie-politiques-europeennes</li>'
            . '<li>08-L1-initiation-histoire-economique-europe-USA</li>'
            . '<li>13-M2-management-RH-responsabilite-sociale-entreprise</li>'
            . '<li>11-M2-politique-comparee-monde-arabe-contemporain</li>'
            . '</ul>';
        $htmlUrl .= '<div class="urlHeader">Compléter le champ ci-dessous avec l’URL souhaitée</div>';
        $mform->addElement('html', $htmlUrl);

        if ($urlfixeExist) {
            $htmlMyUrlModel = '<div id="myurlmodel">L\'URL pérenne de l\'EPI modèle sélectionné est la suivante : <b>'.
                $urlPfixe . $SESSION->wizard['form_step2']['modelurlfixe'].'</b></div>';
            $mform->addElement('hidden', 'modelurlfixe', null);
            $mform->setType('modelurlfixe', PARAM_MULTILANG);
            $mform->setConstant('modelurlfixe', $SESSION->wizard['form_step2']['modelurlfixe']);

            $mform->addElement('html', $htmlMyUrlModel);
            $mform->addElement('radio', 'urlmodel', '', 'Je souhaite transférer cette l\'URL pérenne au nouveau cours',  'fixe');
            $mform->addElement('radio', 'urlmodel', '', 'Je souhaitre utiliser une autre URL pérenne',  'myurl');
        }

        $mform->addElement('text', 'myurl', '<span title="Partie fixe de l\'URL">' . $urlPfixe . '</span>',
            'maxlength="50" size="50" title="Extension à compléter par l\'expression résumée de votre cours en 50 caractères maximum"');
        $mform->setType('myurl', PARAM_MULTILANG);

        $htmlUrl2 = '<p>Veuillez respecter la charte suivante : </p>';
        $htmlUrl2 .= '<div class="bloc_pink"><ul>'
            . '<li>laissez votre numéro d\'UFR et le niveau d\'enseignement pré-remplis,</li>'
            . '<li>complétez le titre court de votre enseignement,</li>'
            . '<li>ne mettez pas d\'accents, pas d\'apostrophe ni signes de ponctuation dans vos URL,</li>'
            . '<li>ne mettez pas d\'espaces et utilisez le tiret simple (ou tiret du 6) comme séparateur,</li>'
            . '<li>n\'excédez pas 50 caractères tout compris.</li>'
            . '</ul></div>';
        $htmlUrl2 .= '<p>NB : Les URL pérennes ne sont pas recommandées pour les TD. '
            . 'Si toutefois vous avez besoin d\'une URL pérenne pour un TD, veuillez '
            . 'lui attribuer une extension comportant le n° de TD.<br/>'
            . 'Exemple : 04-L3-analyse-cinema-experimental-TD2</p>';
        $htmlUrl2 .= '</div>';
        $mform->addElement('html', $htmlUrl2);

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
        $urlwizard = '';
        if (isset($SESSION->wizard['wizardurl'])) {
            $urlwizard = $SESSION->wizard['wizardurl'];
        }
        $buttonarray[] = $mform->createElement(
            'link', 'previousstage', null,
            new moodle_url($urlwizard, array('stepin' => 1)),
                $labelprevious, array('class' => 'previousstage'));
        $buttonarray[] = $mform->createElement('submit', 'stepgo_3', get_string('nextstage', 'local_crswizard'));
        $mform->addGroup($buttonarray, 'buttonar', '', null, false);
        $mform->closeHeaderBefore('buttonar');
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($errors)) {
            //$this->validation_shortname($data['shortname'], $errors);
            $this->validation_category($data['category'], $errors);
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
        }
        return $errors;
    }

    private function validation_shortname($shortname, &$errors) {
        global $DB;

        $foundcourses = $DB->get_records('course', array('shortname' => $shortname));
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
            if ($category->depth < 1) {
                $errors['category'] = get_string('categoryerrormsg1', 'local_crswizard');
            }
            $cat_annee_courante = get_config('local_crswizard', 'cas2_default_etablissement');
            if ( ! empty($cat_annee_courante)) {
                if (!preg_match('#' . $cat_annee_courante . '#i', $category->path)) {
                   $category2 =  $DB->get_record('course_categories', array('id' => $cat_annee_courante));
                   if (!empty($category2->path)) {
                       $array_cat = explode('/',$category2->path);
                       if (!empty($array_cat[1])) {
                        $category3 =  $DB->get_record('course_categories', array('id' => $array_cat[1]));
                        if (!empty($category3->name)) $errors['category'] = 'Attention, veuillez sélectionner l\''.strtolower($category3->name).', puis l\'établissement !';
                       }

                   }
                }
            }
        } else {
            $errors['category'] = get_string('categoryerrormsg2', 'local_crswizard');
        }
        return $errors;
    }

    private function validation_myurl($urlok, $myurl, $urlmodel, $modelurlfixe, &$errors) {
        global $DB;
        if ($urlok == 1) {
            $url = trim($myurl);
            if ($modelurlfixe != '' && $urlmodel == '') {
                $errors['urlmodel'] = 'Vous devez sélectionner une URL pérenne';
            } elseif($urlmodel != 'fixe') {
                if ($url == '') {
                    $errors['myurl'] = 'Vous devez défnir une L’URL pérenne';
                } else {
                    $myerrors = [];
                    if (strlen($url) > 50) {
                        $myerrors[] = 'L\'URL ne doit pas faire plus de 50 caractères';
                    }
                    if (strpos($url, ' ') != false) {
                        $myerrors[] = 'L\'URL ne doit contenir d\'espaces';
                    }
                    $url1 = iconv("UTF-8", "ASCII//TRANSLIT", $url);
                    $url1 = preg_replace('/[^-_A-Za-z0-9]*/', '', $url1);

                    if ($url1 != $url) {
                        $myerrors[] = 'L\'URL ne doit pas contenir de caratères accentués ou spéciaux';
                    }
                    $sql = "SELECT count(objectid) FROM {custom_info_field} cf "
                        . "JOIN {custom_info_data} cd ON (cf.id = cd.fieldid) "
                        . "WHERE cf.objectname='course' AND cd.objectname='course' "
                        . "AND cf.shortname=? AND data =?";
                    $res = $DB->get_field_sql($sql, array('up1urlfixe', $url));
                    if ($res) {
                        // je contrôle si doublon
                        $myerrors[] = 'Désolé, cette URL est déjà utilisée. Veuillez choisir un autre nom';
                    }
                    if (count($myerrors)) {
                        $errors['myurl'] = implode(',<br/>', $myerrors);
                    }
                }
            }
        }
    }
}
