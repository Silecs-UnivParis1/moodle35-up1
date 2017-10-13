<?php
/**
 * Edit course settings
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laters
 */

class wizard_core {
    private $formdata;
    private $user;
    private $mydata;
    public $course;

    public function __construct($formdata, $user) {
        $this->formdata = $formdata;
        $this->user = $user;
    }

    public function create_course_to_validate() {
        // créer cours
        $mydata = $this->prepare_course_to_validate();
        // ajout commentaire de creation
        $mydata->profile_field_up1commentairecreation = strip_tags($this->formdata['form_step7']['remarques']);

        if (isset($this->formdata['form_step1']['coursedmodelid']) && $this->formdata['form_step1']['coursedmodelid'] != '0') {
            $options = array();
            $options[] = array('name' => 'users', 'value' => 0);
            $duplicate = new wizard_modele_duplicate($this->formdata['form_step1']['coursedmodelid'], $mydata, $options);
            $duplicate->create_backup();
            $course = $duplicate->retore_backup();
            $mydata->profile_field_up1modele = '[' . $this->formdata['form_step1']['coursedmodelid'] . ']'
                . $this->formdata['form_step1']['coursemodelshortname'];
        } else {
            $course = create_course($mydata);
        }

        $event = \core\event\course_created::create(array(
            'objectid' => $course->id,
            'context' => context_course::instance($course->id),
            'other' => array('plugin' => 'crswizard',
                        'shortname' => $course->shortname,
                         'fullname' => $course->fullname)
        ));
        $event->trigger();

        $this->course = $course;
        // on supprime les enrols par défaut
        $this->delete_default_enrol_course($course->id);
        // save custom fields data
        $mydata->id = $course->id;
        $custominfo_data = custominfo_data::type('course');

        // metadata supp.
        if ($this->formdata['wizardcase'] == 3) {
            $metadonnees = get_array_metadonees(FALSE);
            foreach ($metadonnees as $md) {
                if (!empty($this->formdata['form_step3'][$md])) {
                    $donnees = '';
                    foreach ($this->formdata['form_step3'][$md] as $elem) {
                        $donnees = $donnees . $elem . ';';
                    }
                    $donnees = substr($donnees, 0, -1);
                    $name = 'profile_field_' . $md;
                    if (isset($this->mydata->$name) && $this->mydata->$name != '') {
                        $this->mydata->$name .= ';' . $donnees;
                    } else {
                        $this->mydata->$name = $donnees;
                    }
                }
            }
        }

        $cleandata = $this->customfields_wash($mydata);
        $custominfo_data->save_data($cleandata);

        $this->update_session($course->id);
        //! @todo tester si le cours existe bien ?
        // inscrire des enseignants
        if (isset($this->formdata['form_step4']['user']) && count($this->formdata['form_step4']['user'])) {
            $tabUser = $this->formdata['form_step4']['user'];
	    $tabUser = normalize_enrolment_users($tabUser);
            myenrol_teacher($course->id, $tabUser);
        }

        // inscrire des cohortes
        if (isset($this->formdata['form_step5']['group']) && count($this->formdata['form_step5']['group'])) {
            $tabGroup = $this->formdata['form_step5']['group'];
            $erreurs = myenrol_cohort($course->id, $tabGroup);
            if (count($erreurs)) {
                $this->formdata['form_step5']['cohorterreur'] = $erreurs;
                return affiche_error_enrolcohort($erreurs);
            }
        }
        // inscrire des clefs
        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $clefs = wizard_list_clef($form6);
            if (count($clefs)) {
                $this->myenrol_clef($course, $clefs);
            }
        }

        $this->mydata = $mydata;
        return '';
    }

    /**
     * créé la partie variable (selon validateur ou créateur du message de
     * notification envoyé à la création du cours
     * @param bool $autovalidation
     * @return array array("mgvalidator" => $mgv, "mgcreator" => $mgc);
     */
    public function get_messages($autovalidation) {
        global $CFG;
        $urlguide = $CFG->wwwroot .'/guide';
        $urlvalidator = $CFG->wwwroot .'/local/course_validated/index.php';
        $idval = array();
        $form3 =  $this->formdata['form_step3'];
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $allvalidators = $form3['all-validators'];
            foreach ($allvalidators as $validator) {
                 $idval['fullname'] = fullname($validator);
                 $idval['username'] = $validator->username;
            }
        }

        $nomcours = $this->mydata->course_nom_norme;

        $signature = 'Cordialement,' . "\n\n";
        $signature .= 'L\'assistance EPI' . "\n\n";
        $signature .= 'DSIUN-SUN - Service des usages numériques' . "\n";
        $signature .= 'Université Paris 1 Panthéon-Sorbonne' . "\n";
        $signature .= 'Courriel : assistance-epi@univ-paris1.fr' . "\n";

        $mgc = 'Bonjour,' . "\n\n";
        $mgc .= 'Vous venez de créer l\'espace de cours "' . $nomcours . '" sur la plateforme '. $CFG->wwwroot . "\n\n";
        if ($autovalidation == false) {
            if (count($idval)) {
                $mgc .= 'Votre demande a été transmise à ' . $idval['fullname'] . ', ainsi qu\'aux gestionnaires de '
                . 'la plateforme pour approbation, avant son ouverture aux étudiants';
            } else {
                $mgc .=  'Votre demande a été transmise aux gestionnaires de '
                . 'la plateforme pour approbation, avant son ouverture aux étudiants.';
            }
        } else {
            $mgc .= 'Votre cours n\'est pas encore ouvert aux étudiants';
        }
        $mgc .= "\n\n";
        $mgc .= 'Notez cependant que toutes les personnes auxquelles vous avez attribué '
            . 'des droits de contribution ont d\'ores et déjà la possibilité de s\'approprier ce nouvel espace de cours : '
            . 'personnaliser le texte de présentation, organiser et nommer à leur convenance '
            . 'les différentes sections, déposer des documents, etc.' . "\n\n";
        if ($autovalidation == false) {
            $mgc .= 'Vous trouverez à cette adresse ' . $urlguide . ' des informations sur le processus d\'approbation des espaces '
                . 'nouvellement créés.' . "\n\n";
        }
        $mgc .= 'N\'hésitez pas à contacter l\'un des membres de l\'équipe du service des usages numériques :' . "\n";
        $mgc .= '- si vous souhaitez participer à l\'une des sessions de prise en mains régulièrement organisées ;' . "\n";
        $mgc .= '- si vous rencontrez une difficulté ou si vous constatez une anomalie de fonctionnement.' . "\n\n";
        $mgc .= 'Conservez ce message. Le récapitulatif technique présenté ci-après peut vous être utile, '
            . 'notamment pour dialoguer avec l\'équipe d\'assistance.' . "\n\n";
        $mgc .= $signature;


        $mgv = 'Bonjour,' . "\n\n";
        $mgv .= fullname($this->user) . ' ('.$this->user->email.') '
            . 'vient de créer l\'espace de cours "' . $nomcours . '" sur la plateforme '
            . $CFG->wwwroot . ' et vous a indiqué comme la personne pouvant valider sa création. ' . "\n\n";
        $mgv .= 'Pour donner votre accord :' . "\n\n";
        $mgv .= '1. Cliquez sur le lien suivant '. $urlvalidator . ' ;' . "\n";
        if (count($idval)) {
            $mgv .= '2. Si nécessaire, authentifiez-vous avec votre compte Paris 1 : ' . $idval['username'] . ' ;' . "\n";
        } else {
            $mgv .= '2. Si nécessaire, authentifiez-vous avec votre compte Paris 1. ' . "\n";
        }
        $mgv .= '3. Cliquez sur l\'icône "coche verte" située dans la colonne "Actions" ;' . "\n";
        $mgv .= '4. '.fullname($this->user).' sera automatiquement prévenu-e par courriel de votre approbation.'  . "\n\n";
        $mgv .= 'Vous trouverez à cette adresse ' . $urlguide . ' des informations sur le processus d\'approbation des espaces '
             . 'nouvellement créés.' . "\n\n";
        $mgv .= 'Le récapitulatif technique présenté ci-après peut vous apporter des précisions sur cette demande.'  . "\n\n";
        $mgv .= 'Si cette demande ne vous concerne pas ou si vous ne souhaitez pas y donner suite, '
            . 'merci d\'en faire part à l\'équipe d\'assistance en lui transférant ce message '
            . '(assistance-epi@univ-paris1.fr).'  . "\n\n";
        $mgv .= $signature;

        return array("mgvalidator" => $mgv, "mgcreator" => $mgc);
    }

    private function update_session($courseid) {
        global $SESSION;
        $SESSION->wizard['idcourse'] = $courseid;
        $SESSION->wizard['idenrolment'] = 'manual';
    }

    /**
     * Met à jour la variable nameparam de $SESSION->wizard
     * @param string/array() $value nouvelle valeur
     * @param string $nameparam nom du parametre à mettre à jour
     * @param string $formparam nom du tableau intermédiaire
     */
    public function set_wizard_session($value, $nameparam, $formparam='') {
        global $SESSION;
        if ($formparam != '') {
            $SESSION->wizard[$formparam][$nameparam] = $value;
        } else {
            $SESSION->wizard[$nameparam] = $value;
        }
    }

    /**
     * Returns an object with properties derived from the forms data.
     * @return object
     */
    public function prepare_course_to_validate() {
        $this->mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);
        $this->setup_mydata();
        $this->mydata->course_nom_norme = '';
        $this->mydata->profile_field_up1urlfixe = '';
        $form2 = $this->formdata['form_step2'];

        // on est dans le cas 2
        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2);
            $this->set_param_rof1($rof1);

            // rattachement secondaire
            $this->set_metadata_rof2();
            // metadonnee de rof1 et 2
            $tabrofpath = $this->get_tabrofpath($rof1['tabpath'], $this->formdata['rof2_tabpath']);
            $this->set_metadata_rof($tabrofpath);

            // id category moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($form2);

            $this->set_rof_shortname($rof1['idnumber']);
            $this->set_rof_fullname();
            $this->set_rof_nom_norm();
            $this->mydata->profile_field_up1complement = trim($form2['complement']);
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°2 ROF)';

        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            $this->mydata->profile_field_up1generateur = 'Manuel via assistant (cas n°3 hors ROF)';
            //rattachement hybride
            $this->set_metadata_rof2('form_step3');
            // id category moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($this->formdata['form_step3']);

            if (count($this->formdata['rof2_tabpath'])) {
                $this->set_metadata_rof($this->formdata['rof2_tabpath']);
            }
            $this->set_categories_connection();
        }

        $this->mydata->profile_field_up1datefermeture = $form2['up1datefermeture'];
        $this->mydata->summary = $form2['summary_editor']['text'];
        $this->mydata->summaryformat = $form2['summary_editor']['format'];

        //url fixe
        if (isset($form2['urlok']) && $form2['urlok'] == 1) {
            if (isset($form2['urlmodel']) && $form2['urlmodel'] == 'fixe') {
                $this->mydata->profile_field_up1urlfixe = trim($form2['modelurlfixe']);
            } else {
                $this->mydata->profile_field_up1urlfixe = trim($form2['myurl']);
            }
        }

        // cours doit être validé
        $this->set_metadata_cycle_life();
        return $this->mydata;
    }

    /**
     * Création des custom_info_field (objectname = course) à vide
     */
    private function setup_mydata() {
        global $DB;
        $sql = "SELECT shortname, datatype FROM {custom_info_field} WHERE objectname = 'course' AND shortname like 'up1%'";
        $customfields = $DB->get_records_sql($sql);
        if (count($customfields)) {
            foreach($customfields as $label => $field) {
                $champ = 'profile_field_'.$label;
                $value = '';
                if ($field->datatype != 'text') {
                    $value = 0;
                }
                $this->mydata->$champ = $value;
            }
        }
    }

    private function set_metadata_cycle_life() {
        $this->mydata->profile_field_up1avalider = 1;
        $this->mydata->profile_field_up1datevalid = 0;
        $this->mydata->profile_field_up1approbateurpropid = wizard_get_approbateurpropid();

        $form3 = $this->formdata['form_step3'];
        // si autoevaluation
        if (isset($form3['autovalidation'])) {
            $this->mydata->profile_field_up1avalider = 1;
            $this->mydata->profile_field_up1approbateurpropid = $this->user->id;
            $this->mydata->profile_field_up1approbateureffid = $this->user->id;
            $this->mydata->profile_field_up1datevalid = time();
        }

        $this->mydata->profile_field_up1datedemande = time();
        $this->mydata->profile_field_up1demandeurid = $this->user->id;
    }

    private function set_param_rof1($rof1) {
        if ( array_key_exists('idcat', $rof1) && $rof1['idcat'] != false) {
            $this->mydata->category = $rof1['idcat'];
            $this->set_wizard_session($rof1['idcat'], 'rattachement1', 'form_step2');
            //$this->mydata->profile_field_up1niveaulmda = $rof1['up1niveaulmda'];
            //$this->mydata->profile_field_up1composante = $rof1['up1composante'];
        }
        $this->mydata->idnumber = $rof1['idnumber'];
    }

    /**
     * Construit et assigne le paramètre $shortname d'un cours rattaché au ROF
     * @param string $idnumber
     */
    private function set_rof_shortname($idnumber) {
        $form2 = $this->formdata['form_step2'];
        $shortname = $idnumber;
        if (isset($form2['complement'])) {
            $complement = trim($form2['complement']);
            if ($complement != ''){
                $shortname .= ' - ' . $complement;
            }
        }
        $this->mydata->shortname = $shortname;
    }

    /**
     * Construit et assigne le paramètre $shortname d'un cours rattaché au ROF
     * @param string $idnumber
     */
    private function set_rof_fullname() {
        $form2 = $this->formdata['form_step2'];
        if ($form2['complement'] !='') {
            $this->mydata->fullname .= ' - ' . $form2['complement'];
        }
    }

    private function set_rof_nom_norm() {
        $form2 = $this->formdata['form_step2'];
        $this->mydata->course_nom_norme = $this->mydata->idnumber . ' - ' . $form2['fullname'];
        if ($form2['complement'] !='') {
            $this->mydata->course_nom_norme .= ' - ' . $form2['complement'];
        }
    }

    /**
     * assigne les informations du rattachement
     * principal ROF aux metadonnées de cours
     * @param array $tabpath
     */
    private function set_metadata_rof($tabpath) {
        $mdrof1 = rof_get_metadata_concat($tabpath);
        foreach ($mdrof1 as $data) {
            if (count($data)) {
                foreach($data as $label => $value) {
                    $champ = 'profile_field_'.$label;
                    $this->mydata->$champ = $value;
                }
            }
        }
    }

    /**
     * assigne les informations des rattachements
     * secondaires ROF aux metadonnées de cours
     * @param string $form_step
     */
    private function set_metadata_rof2($form_step = 'form_step2') {
        $form2 = $this->formdata[$form_step];
        $rof2 = wizard_prepare_rattachement_second($form2);
        if (count($rof2)) {
            if (isset($rof2['rofchemin'])) {
                $this->set_wizard_session($rof2['rofchemin'], 'rattachement2', 'form_step2');
            }
            if (isset($rof2['rofid'])) {
                if (isset($this->mydata->profile_field_up1rofid) == false) {
                    $this->mydata->profile_field_up1rofid = '';
                }
                foreach($rof2['rofid'] as $rofid) {
                    $this->mydata->profile_field_up1rofid .= ';' . $rofid;
                }
            }
            if (isset($rof2['rofpathid'])) {
                if (isset($this->mydata->profile_field_up1rofpathid) == false) {
                    $this->mydata->profile_field_up1rofpathid = '';
                }
                foreach($rof2['rofpathid'] as $rofpath) {
                    $this->mydata->profile_field_up1rofpathid .= ';' . $rofpath;
                }
            }
            if (isset($rof2['rofname'])) {
                foreach($rof2['rofname'] as $rofname) {
                    $this->mydata->formdata['form_step2']['rofname_second'][] = $rofname;
                }
            }
            $this->formdata['rof2_tabpath'] = (isset($rof2['tabpath']) ? $rof2['tabpath'] : array());
        } else {
            $this->formdata['rof2_tabpath'] = array();
        }
    }

    /**
     * Construit le tabeau tabpath pour la fonction set_metadata_rof
     * (cf fonction rof_get_metadata_concat)
     * @param array $rof1tabpath
     * @param array $rof2tabpath (tableau de tableau)
     * @return array $tabpath (tableau de tableau)
     */
    private function get_tabrofpath($rof1tabpath, $rof2tabpath) {
        $tabpath[] = $rof1tabpath;
        if (count($rof2tabpath)) {
            foreach ($rof2tabpath as $rof2_tabpath) {
                $tabpath[] = $rof2_tabpath;
            }
        }
        return $tabpath;
    }

    /**
     * assigne les catégories supplémentaires pour les cours hors ROF
    */
    private function set_categories_connection() {
        $form2 = $this->formdata['form_step2'];
        $tabcategories = get_list_category($form2['category']);
        if (isset($this->mydata->rattachements)) {
            $ratt = wizard_get_rattachement_fieldup1($this->mydata->rattachements, $tabcategories);
            if (count($ratt)) {
                foreach ($ratt as $fieldname => $value) {
                    if (isset($this->mydata->$fieldname) && trim($this->mydata->$fieldname) != '') {
                        $this->mydata->$fieldname .= ';';
                    }
                    if (!isset($this->mydata->$fieldname)) {
                        $this->mydata->$fieldname = '';
                    }
                    $this->mydata->$fieldname .= $value;
                }
            }
            if (count($this->mydata->rattachements)) {
                $first = true;
                foreach ($this->mydata->rattachements as $rattachement) {
                    if (isset($this->mydata->profile_field_up1categoriesbis) && trim($this->mydata->profile_field_up1categoriesbis) != '') {
                        $this->mydata->profile_field_up1categoriesbis .= ';';
                    }
                    if (!isset($this->mydata->profile_field_up1categoriesbis)) {
                        $this->mydata->profile_field_up1categoriesbis = '';
                    }
                    $this->mydata->profile_field_up1categoriesbis .= $rattachement;
                }
            }
        }
    }

    // supprime les méthodes d'inscriptions guest et self
    private function delete_default_enrol_course($courseid) {
        global $DB;
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'self'));
        $DB->delete_records('enrol', array('courseid' => $courseid, 'enrol' => 'guest'));
    }

    private function myenrol_clef($course, $tabClefs) {
        global $DB;
        if ($course->id == SITEID) {
            throw new coding_exception('Invalid request to add enrol instance to frontpage.');
        }
        // traitement des données
        foreach ($tabClefs as $type => $tabClef) {
            $libre = FALSE;
            if ($tabClef['password'] == '') {
                $libre = TRUE;
            }
            $name = ($libre ? 'accès libre ' : 'clef ') . $type;

            if ($type == 'Etudiante') {
                $enrol = 'self';
                $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));
            } elseif ($type == 'Visiteur') {
                $enrol = 'guest';
                $roleid = 0;
            }
            $status = 0;   //0 pour auto-inscription
            if (isset($tabClef['enrolstartdate'])) {
                $startdate  = $tabClef['enrolstartdate'];
            } else {
                $startdate = 0;
            }
            if (isset($tabClef['enrolenddate'])) {
                $enddate = $tabClef['enrolenddate'];
            } else {
                $enddate = 0;
            }

            $instance = new stdClass();
            $instance->enrol = $enrol;
            $instance->status = $status;
            $instance->courseid = $course->id;
            $instance->roleid = $roleid;
            $instance->name = $name;
            $instance->password = $tabClef['password'];
            if ($enrol == 'self') {
                $instance->customint1 = 0; // groupkey - clef d'inscription groupe
                $instance->customint2 = 0; // longtimenosee
                $instance->customint3 = 0; // maxenrolled
                $instance->customint4 = 0; // sendcoursewelcomemessage - envoie d'un message
                $instance->customint5 = 0; // lié aux cohorts
                $instance->customint6 = 1; // newenrols - permet de s'enroler

            }
            $instance->enrolstartdate = $startdate;
            $instance->enrolenddate = $enddate;
            $instance->timemodified = $course->timecreated;
            $instance->timecreated = $course->timecreated;
            $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $course->id));
            $DB->insert_record('enrol', $instance);
        }
    }

    /**
     * Convertit les champs custom_info_field de type datetime en timestamp
     * @param object $data
     * @return object $data
     */
    private function customfields_wash($data) {
        global $DB;

        $fields = $DB->get_records('custom_info_field', array('objectname' => 'course', 'datatype' => 'datetime'));
        if ($fields) {
            foreach ($fields as $field) {
                $nomc = 'profile_field_' . $field->shortname;
                if (isset($data->$nomc) && is_array($data->$nomc)) {
                    $tab = $data->$nomc;
                    $hour = 0;
                    $minute = 0;
                    if (isset($tab['hour'])) {
                        $hour = $tab['hour'];
                    }
                    if (isset($tab['minute'])) {
                        $minute = $tab['minute'];
                    }
                    $data->$nomc = mktime($hour, $minute, 0, $tab['month'], $tab['day'], $tab['year']);
                }
            }
        }
        return $data;
    }

    public function get_recapitulatif_demande() {
        $myconfig = new my_elements_config();

        $mg = '';
        $mg .= "\n" . '---------------------' . "\n";
        $mg .= 'Récapitulatif de la demande';
        $mg .= "\n" . '---------------------' . "\n";
        $mg .= get_string('username', 'local_crswizard') . fullname($this->user) . "\n";
        $mg .= get_string('userlogin', 'local_crswizard') . $this->user->username . "\n";
        $mg .= get_string('courserequestdate', 'local_crswizard') . date('d-m-Y') . "\n";

        //$wizardcase = $this->formdata['wizardcase']; // was not used

        // categorie
        $displaylist = array();
        $displaylist = coursecat::make_categories_list();

        $form2 = $this->formdata['form_step2'];
        $form3 = $this->formdata['form_step3'];
        //  $idcat = $form2['category'];
        $idcat = $this->mydata->category;
        $mg .= get_string('categoryblockE3', 'local_crswizard') . ' : ' . $displaylist[$idcat] . "\n";
        // cas 3
        if (isset($form3['rattachements']) && count($form3['rattachements'])) {
            $first = true;
            foreach ($form3['rattachements'] as $ratt) {
                if (isset($displaylist[$ratt])) {
                    $mg .= ($first?get_string('labelE7ratt2', 'local_crswizard') . ' : ' : ', ')
                        . $displaylist[$ratt];
                    $first = false;
                }
            }
            $mg .=  "\n";
        }
        // rattachements secondaires
        if (isset($form2['rattachement2']) && count($form2['rattachement2'])) {
            $mg .= get_string('labelE7ratt2', 'local_crswizard') . ' : ';
            $racine = '';
            if ($this->formdata['wizardcase'] == 2) {
                $racine = $displaylist[$form2['category']];
            } elseif ($this->formdata['wizardcase'] == 3) {
                $tabcategories = get_list_category($form2['category']);
                $racine = $tabcategories[0] . ' / ' . $tabcategories[1];
            }
            $first = true;
            foreach ($form2['rattachement2'] as $formsecond) {
                $mg .= ($first ? '' : ', ') . $racine . ' / ' . $formsecond;
                $first = false;
            }
            $mg .=  "\n";
        }

        //cas3 - métadonnées supplémentaires
        if ($this->formdata['wizardcase'] == 3) {
            $metadonnees = get_array_metadonees();
            foreach ($metadonnees as $key => $label) {
                if (!empty($form3[$key])) {
                    $donnees = '';
                    foreach ($form3[$key] as $elem) {
                        $donnees = $donnees . $elem . ';';
                    }
                    $donnees = substr($donnees, 0, -1);
                    $mg .= $label . $donnees . "\n";
                }
            }
        }

        $mg .= get_string('fullnamecourse', 'local_crswizard') . $this->mydata->fullname . "\n";
        $mg .= get_string('shortnamecourse', 'local_crswizard') . $this->mydata->shortname . "\n";

        $mg .= get_string('coursestartdate', 'local_crswizard') . date('d-m-Y', $form2['startdate']) . "\n";
        $mg .= get_string('up1datefermeture', 'local_crswizard') . date('d-m-Y', $form2['up1datefermeture']) . "\n";

        if (!empty($this->formdata['form_step1']['coursedmodelid']) && $this->formdata['form_step1']['coursedmodelid'] != '0') {
            $mg .= get_string('coursemodel', 'local_crswizard') . '[' . $this->formdata['form_step1']['coursemodelshortname']
            . ']' . $this->formdata['form_step1']['coursemodelfullname'] . "\n";
        }

        $mg .= 'Mode de création : ' .  $this->mydata->profile_field_up1generateur . "\n";

        //url fixe
        if (isset($form2['urlok']) && $form2['urlok'] == 1) {
            $mg .= 'URL pérenne : ' . $this->formdata['urlpfixe'] . $this->mydata->profile_field_up1urlfixe . "\n";
            if (isset($form2['urlmodel']) &&  $form2['urlmodel'] == 'fixe') {
                $mg .= 'Attention,  l\'url pérenne de l\'EPI modèle est transférée à ce nouvel EPI.' . "\n";
            }
        }

        // validateur si il y a lieu
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $allvalidators = $form3['all-validators'];
            $mg .= get_string('selectedvalidator', 'local_crswizard') . ' : ';
            $first = true;
            foreach ($allvalidators as $id => $validator) {
                $mg .= ($first ? '' : ', ') . fullname($validator);
                $first = false;
            }
            $mg .=  "\n";
        }

        // liste des enseignants :
        $form4 = $this->formdata['form_step4']; // ou $SESSION->wizard['form_step4']
        $mg .= get_string('teachers', 'local_crswizard') . "\n";
        if (isset($form4['all-users']) && is_array($form4['all-users'])) {
            $allusers = $form4['all-users'];
            $labels = $myconfig->role_teachers;
            foreach ($allusers as $role => $users) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                 $mg .= '    ' . $label . ' : ';
                foreach ($users as $id => $user) {
                    $mg .=  ($first ? '' : ', ') . fullname($user);
                    $first = false;
                }
                $mg .=  "\n";
            }
        } else {
            $mg .= '    Aucun' . "\n";
        }

        // liste des groupes
        $form5 = isset($this->formdata['form_step5']) ?  $this->formdata['form_step5'] : [];
        $mg .= get_string('cohorts', 'local_crswizard') . "\n";
        if (!empty($form5['all-cohorts'])) {
            $groupsbyrole = $form5['all-cohorts'];
            $labels = $myconfig->role_cohort;
            foreach ($groupsbyrole as $role => $groups) {
                $label = $role;
                if (isset($labels[$role])) {
                    $label = get_string($labels[$role], 'local_crswizard');
                }
                $first = true;
                foreach ($groups as $id => $group) {
                    $mg .= '    ' . ($first ? $label . ' : ' : '           ') . $group->name
                        .  ' — '  . "{$group->size} inscrits" . "\n";
                    $first = false;
                }
            }
        } else {
            $mg .= '    Aucun' . "\n";
        }

        // clefs
        $mg .= get_string('enrolkey', 'local_crswizard') . "\n";
        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $clefs = wizard_list_clef($form6);
            if (count($clefs)) {
                foreach ($clefs as $type => $clef) {
                    $mg .= '    ' . $type . ' : ' . ($clef['password'] == ''? 'Accès libre' : $clef['password']) . "\n";
                    $mg .= '    ' . get_string('enrolstartdate', 'enrol_self') . ' : ';
                    if (isset($clef['enrolstartdate']) && $clef['enrolstartdate'] != 0) {
                        $mg .= date('d-m-Y', $clef['enrolstartdate']);
                    } else {
                        $mg .= 'incative';
                    }
                    $mg .= "\n";
                    $mg .= '    ' . get_string('enrolenddate', 'enrol_self') . ' : ';
                    if (isset($clef['enrolenddate']) && $clef['enrolenddate'] != 0) {
                        $mg .= date('d-m-Y', $clef['enrolenddate']);
                    } else {
                        $mg .= 'incative';
                    }
                    $mg .= "\n";
                }
            } else {
                $mg .= '    Aucune' . "\n";
            }
        }
        return $mg;
    }

    public function get_email_subject($idcourse, $type) {
        $subject = '';
        $sitename = format_string(get_site()->shortname);
        $subject .= "[$sitename] $type espace";
        $subject .=' n°' . $idcourse;
        $subject .= ' : ' . $this->mydata->course_nom_norme;
        return $subject;
    }

    /**
    * envoie un message de notification suite à la création du cours
    * @param int $idcourse : identifiant du cours créé
    * @param string $mgc destiné au demandeur
    * @param string $mgv destiné à l'approbateur et aux validateurs
    * @param string $remarques précises si des remarques ont été associées
    */
    public function send_message_notification($idcourse, $mgc, $mgv, $remarques=false) {
        global $DB;
        $userfrom = new stdClass();
        static $supportuser = null;
        if (!empty($supportuser)) {
            $userfrom = $supportuser;
        } else {
            $userfrom = $this->user;
        }

        //approbateur désigné ?
        $approbateur = false;
        $typeMessage = 'Assistance - Demande approbation';
        $form3 =  $this->formdata['form_step3'];
        if (isset($form3['all-validators']) && !empty($form3['all-validators'])) {
            $approbateur = true;
             $typeMessage = 'Demande approbation';
        }
        $subject = $this->get_email_subject($idcourse, $typeMessage);
        if ($remarques) $subject.= ' (REMARQUES ASSOCIEES)';
        $eventdata = new stdClass();
        $eventdata->component = 'moodle';
        $eventdata->name = 'courserequested';
        $eventdata->userfrom = $userfrom;
        $eventdata->subject = $subject; //** @todo get_string()
        $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
        $eventdata->fullmessage = $mgv;
        $eventdata->fullmessagehtml = '';   //$messagehtml;
        $eventdata->smallmessage = $mgv; // USED BY DEFAULT !
        // documentation : http://docs.moodle.org/dev/Messaging_2.0#Message_dispatching

        // envoi aux supervalidateurs
        $coursecontext = context_course::instance($idcourse);
        $supervalidators = get_users_by_capability($coursecontext, 'local/crswizard:supervalidator');
        foreach ($supervalidators as $userto) {
            $eventdata->userto = $userto;
            $res = message_send($eventdata);
            if (!$res) {
                // @todo Handle messaging errors
            }
        }

        // envoi à l'approbateur si besoin
        if ($approbateur) {
            $allvalidators = $form3['all-validators'];
            foreach ($allvalidators as $validator) {
                $eventdata->userto = $validator;
                $res = message_send($eventdata);
            }
        }

        // envoi à helpdesk_user si définit dans crswizard.setting
        $helpuser = get_config('local_crswizard', 'helpdesk_user');
        if (isset($helpuser)) {
            $userid = $DB->get_field('user', 'id', array('username' => $helpuser));
            if ($userid) {
                $eventdata->userto = $userid;
                $res = message_send($eventdata);
            }
        }

        // copie au demandeur
        $eventdata->userto = $this->user;
        $subject = $this->get_email_subject($idcourse, 'Création');
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $mgc;
        $eventdata->smallmessage = $mgc; // USED BY DEFAULT !
        $res = message_send($eventdata);
    }

    /**
    * envoie un message uniquement au créateur
    * @param int $idcourse : identifiant du cours créé
    * @param string $mgc destiné au demandeur
    */
    function send_message_autovalidation($idcourse, $mgc) {
        global $DB;
        $userfrom = new stdClass();
        static $supportuser = null;
        if (!empty($supportuser)) {
            $userfrom = $supportuser;
        } else {
            $userfrom = $this->user;
        }

        $subject = $this->get_email_subject($idcourse, 'Création');

        $eventdata = new stdClass();
        $eventdata->component = 'moodle';
        $eventdata->name = 'courserequested';
        $eventdata->userfrom = $userfrom;
        $eventdata->fullmessageformat = FORMAT_PLAIN;   // text format
        $eventdata->userto = $this->user;
        $subject = $this->get_email_subject($idcourse, 'Création');
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $mgc;
        $eventdata->smallmessage = $mgc; // USED BY DEFAULT !
        $eventdata->fullmessagehtml   = '';
        $res = message_send($eventdata);

        // envoi à helpdesk_user si définit dans crswizard.setting
        $helpuser = get_config('local_crswizard', 'helpdesk_user');
        if (isset($helpuser)) {
            $userid = $DB->get_field('user', 'id', array('username' => $helpuser));
            if ($userid) {
                $mgccopie = 'Pour information : ' . "\n\n" . $mgc;
                $eventdata->fullmessage = $mgccopie;
                $eventdata->smallmessage = $mgccopie; // USED BY DEFAULT !
                $eventdata->userto = $userid;
                $res = message_send($eventdata);
            }
        }
    }

    //fonctions spécifiques update_course

    public function get_course($course) {
        $this->course = $course;
    }

    /**
     * prépare les données du cours en vue de sa mise à jour
    */
    public function prepare_update_course() {
        if (isset($this->formdata['form_step3'])) {
            $this->mydata = (object) array_merge($this->formdata['form_step2'], $this->formdata['form_step3']);
        } else {
            $this->mydata = (object) $this->formdata['form_step2'];
        }
        $this->formdata['modif'] = array('identification' => false, 'attach' => false);
        $form2 = $this->formdata['form_step2'];
        $initc = $this->formdata['init_course'];

        if (isset($this->formdata['wizardcase']) && $this->formdata['wizardcase']=='2') {
            $changerof1 = $this->check_first_connection();
            $rof1 = wizard_prepare_rattachement_rof_moodle($form2, $changerof1);
            if ($changerof1 == false) {
                $rof1['idnumber'] = trim($this->formdata['init_course']['idnumber']);
            } else {
                $this->formdata['modif']['identification'] = true;
            }
            $this->set_param_rof1($rof1);
            // rattachement secondaire
            $this->set_metadata_rof2();
            $tabrofpath = $this->get_tabrofpath($rof1['tabpath'], $this->formdata['rof2_tabpath']);
            // metadonnee de rof1 et rof2
            $this->set_metadata_rof($tabrofpath);

            $this->set_rof_shortname($rof1['idnumber']);
            $this->mydata->profile_field_up1complement = trim($form2['complement']);
            $this->set_rof_fullname();
            $this->set_rof_nom_norm();

            // id category moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($form2);

            // log update attach
            $new = array();
            if (isset($this->formdata['form_step2']['item']['s'])) {
                $new = $this->formdata['form_step2']['item']['s'];
            }
            $old = array();
            if (isset($this->formdata['init_course']['form_step2']['item']['s'])) {
                $old = $this->formdata['init_course']['form_step2']['item']['s'];
            }
            if (count(array_diff($old, $new)) || count(array_diff($new, $old))) {
                $this->formdata['modif']['attach'] = true;
            }

        } else { // cas 3
            $this->mydata->course_nom_norme = $form2['fullname'];
            //rattachement hybride
            $this->set_metadata_rof2('form_step3');
            if (count($this->formdata['rof2_tabpath'])) {
                $this->set_metadata_rof($this->formdata['rof2_tabpath']);
            }
            $this->set_categories_connection();

            // log update rattach orthodoxe
            $old = array();
            if (isset($initc['profile_field_up1categoriesbis']) && $initc['profile_field_up1categoriesbis'] != '') {
                $old = explode(';', $initc['profile_field_up1categoriesbis']);
            }
            $new = array();
            if (isset($this->formdata['form_step3']['rattachements'])) {
                $new = $this->formdata['form_step3']['rattachements'];
            }
            if (count(array_diff($old, $new)) || count(array_diff($new, $old))) {
                $this->formdata['modif']['attach'] = true;
                if (count($new) == 0) {
                    $this->mydata->profile_field_up1categoriesbis = '';
                }
            }

            // id category moodle rattachements secondaires
            $this->mydata->profile_field_up1categoriesbisrof = wizard_get_idcat_rof_secondaire($this->formdata['form_step3']);

            // log update rattach hybride
            $oldhyb = array();
            if (isset($initc['profile_field_up1rofid']) && $initc['profile_field_up1rofid'] != '') {
                $oldhyb = explode(';', $initc['profile_field_up1rofid']);
            }
            $newhyb = array();
            if (isset($this->formdata['form_step3']['item']['s'])) {
                $newhyb = $this->formdata['form_step3']['item']['s'];
            }
            if (count(array_diff($oldhyb, $newhyb)) || count(array_diff($newhyb, $oldhyb))) {
                $this->formdata['modif']['attach'] = true;
                if (count($newhyb) == 0) {
                    $this->formdata['modif']['attach2null'] = 1;
                }
            }

            // metadonnees sup.
            $metadonnees = get_array_metadonees(FALSE);
            foreach ($metadonnees as $key) {
                if (!empty($this->formdata['form_step3'][$key])) {
                    $donnees = '';
                    foreach ($this->formdata['form_step3'][$key] as $elem) {
                        if ($elem != '0') {
                            $donnees = $donnees . $elem . ';';
                        }
                    }
                    $donnees = substr($donnees, 0, -1);
                    $name = 'profile_field_' . $key;

                    if ($donnees != '' && $donnees != '0') {
                        if (isset($this->mydata->$name) && $this->mydata->$name != '') {
                            $this->mydata->$name .= ';' . $donnees;
                        } else {
                            $this->mydata->$name = $donnees;
                        }
                    }
                }
            }

            //log
            if ($form2['fullname'] != $initc['fullname'] || $form2['shortname'] != $initc['shortname'] ) {
                $this->formdata['modif']['identification'] = true;
            }
            if ($form2['category'] != $initc['category']) {
                $this->formdata['modif']['attach'] = true;
            }
        }

        $this->mydata->profile_field_up1datefermeture = $form2['up1datefermeture'];
        $this->mydata->summary = $form2['summary_editor']['text'];
        $this->mydata->summaryformat = $form2['summary_editor']['format'];

        if (isset($form2['urlok']) == false || $form2['urlok'] == 0) {
            $this->mydata->profile_field_up1urlfixe = '';
        } elseif($form2['urlok'] == 1) {
            $this->mydata->profile_field_up1urlfixe = trim($form2['myurl']);
        }

        return $this->mydata;
    }

    /**
     * Vérifie si le premier rattachement ROF à été modifié
     * @return bool $check true si modification du rattachement principal
     */
    private function check_first_connection() {
        $check = false;
        $form2 = $this->formdata['form_step2'];
        if (isset($form2['item']) && count($form2['item']) == 1) {
            $allrof = $form2['item'];
            if (isset($allrof['p']) && count($allrof['p'])) {
                $rofpath = key($allrof['p']);
                $rofid = $allrof['p'][$rofpath];
                $apogee = rof_get_code_or_rofid($rofid);
                $up1rofid = trim($this->formdata['init_course']['profile_field_up1rofid']);
                $rofid = '';
                if (strstr($up1rofid, ';')) {
                    $tab = explode(';', $up1rofid);
                    $rofid = $tab[0];
                } else {
                    $rofid = $up1rofid;
                }
                $newapogee = rof_get_code_or_rofid($rofid);
                if ($newapogee != $apogee) {
                    $check = true;
                }
            }
        }
        return $check;
    }

    public function update_course() {
        $this->prepare_update_course();

        if ($this->formdata['modif']['identification']) {
            $event = \core\event\course_updated::create(array(
                'objectid' => $this->mydata->id,
                'context' => context_course::instance($this->mydata->id),
                'other' => array('shortname' => $this->mydata->shortname,
                'fullname' => $this->mydata->fullname,
                'crswizard' => 'Identification')
            ));
            $event->set_legacy_logdata(array($this->mydata->id, 'course', 'update', 'edit.php?id=' . $this->mydata->id, $this->mydata->id));
            $event->trigger();
        }
        if ($this->formdata['modif']['attach']) {
            $event = \core\event\course_updated::create(array(
                'objectid' => $this->mydata->id,
                'context' => context_course::instance($this->mydata->id),
                'other' => array('shortname' => $this->mydata->shortname,
                'fullname' => $this->mydata->fullname,
                'crswizard' => 'Rattachement')
            ));
            $event->set_legacy_logdata(array($this->mydata->id, 'course', 'update', 'edit.php?id=' . $this->mydata->id, $this->mydata->id));
            $event->trigger();
        }
        update_course($this->mydata);
        $custominfo_data = custominfo_data::type('course');
        $cleandata = $this->customfields_wash($this->mydata);

        // suppression total rattachement hybride
        if (isset($this->formdata['modif']['attach2null']) && $this->formdata['modif']['attach2null'] == 1) {
            $catsuppr = array('Identification', 'Diplome', 'Indexation');
            $custominfo_data->setCategoriesByNames($catsuppr);
            $fields = $custominfo_data->getFields(true);
            foreach ($fields as $tabfield) {
                foreach ($tabfield as $f) {
                    $name = 'profile_field_'.$f->shortname;
                    if (isset($cleandata->$name) == FALSE) {
                        $cleandata->$name = '';
                    }
                }
            }
        }

        $custominfo_data->save_data($cleandata);
        $modif = $this->update_myenrol_cohort();
        if ($modif) {
            $event = \core\event\course_updated::create(array(
                'objectid' => $this->mydata->id,
                'context' => context_course::instance($this->mydata->id),
                'other' => array('shortname' => $this->mydata->shortname,
                'fullname' => $this->mydata->fullname,
                'crswizard' => 'Cohorts')
            ));
            $event->set_legacy_logdata(array($this->mydata->id, 'course', 'update', 'edit.php?id=' . $this->mydata->id, $this->mydata->id));
            $event->trigger();
        }
        $modif = $this->update_myenrol_key();
        if ($modif) {
            $event = \core\event\course_updated::create(array(
                'objectid' => $this->mydata->id,
                'context' => context_course::instance($this->mydata->id),
                'other' => array('shortname' => $this->mydata->shortname,
                'fullname' => $this->mydata->fullname,
                'crswizard' => 'Keys')
            ));
            $event->set_legacy_logdata(array($this->mydata->id, 'course', 'update', 'edit.php?id=' . $this->mydata->id, $this->mydata->id));
            $event->trigger();
        }
        rebuild_course_cache($this->mydata->id);
    }

    /**
     * met à jour (suppression/ajout), si besoin, la liste des inscriptions de cohortes
     * @return bool $modif
    */
    public function update_myenrol_cohort()
    {
        $modif = false;
        $course = $this->formdata['init_course'];
        $oldcohorts = array();
        if (isset($course['group'])) {
            $oldcohorts = $course['group'];
        }
        $newcohorts = array();
        if (isset($this->formdata['form_step5']['group'])) {
            $newcohorts = $this->formdata['form_step5']['group'];
        }

        // ajout
        $cohortadd = array();
        foreach ($newcohorts as $role => $tabg) {
            if (array_key_exists($role, $oldcohorts) == false) {
                $cohortadd[$role] = $tabg;
            } else {
                foreach ($tabg as $g) {
                    if (! in_array($g, $oldcohorts[$role])) {
                        $cohortadd[$role][] = $g;
                    }
                }
            }
        }
        if (count($cohortadd)) {
            $modif = true;
        }
        myenrol_cohort($course['id'], $cohortadd);
        // suppression
        $cohortremove = array();
        foreach ($oldcohorts as $role => $tabg) {
            if (array_key_exists($role, $newcohorts) == false) {
                $cohortremove[$role] = $tabg;
            } else {
                foreach ($tabg as $g) {
                    if (in_array($g, $newcohorts[$role]) == false) {
                        $cohortremove[$role][] = $g;
                    }
                }
            }
        }
        if (count($cohortremove)) {
            $modif = true;
        }
        wizard_unenrol_cohort($course['id'], $cohortremove);
        return $modif;
    }

    /**
     * met à jour (suppression/ajout), si besoin, la liste des clefs
     * @return bool $modif
    */
    function update_myenrol_key() {
        global $DB;
        $modif = false;
        $tabenrol = array('Etudiante' => 'self', 'Visiteur' => 'guest');

        if (isset($this->formdata['form_step6'])) {
            $form6 = $this->formdata['form_step6'];
            $newkey = wizard_list_clef($form6);
        }
        $initcourse = $this->formdata['init_course'];
        $course = $DB->get_record('course', array('id' => $initcourse['id']));
        $oldkey = array();
        if (isset($initcourse['key'])) {
            $oldkey = wizard_list_clef($initcourse['key']);

        }

        $nbdiffk = count($newkey) - count($oldkey);
        switch ($nbdiffk) {
            case -2:
                // supprimer toutes les clefs
                foreach ($oldkey as $role => $key) {
                    $enrol = $tabenrol[$role];
                    wizard_unenrol_key ($enrol, $course);
                }
                $modif = true;
                break;
             case 2:
                $this->myenrol_clef($course, $newkey);
                $modif = true;
                break;
            default:
                foreach ($newkey as $role => $key) {
                    $enrol = $tabenrol[$role];
                    if (array_key_exists($role, $oldkey)) {
                        //update
                        if (wizard_update_enrol_key($enrol, $course, $key)) {
                            $modif = true;
                        }
                    } else {
                        $this->myenrol_clef($course, array($role => $key));
                        $modif = true;
                    }
                }
                // suppression
                foreach ($oldkey as $role => $key) {
                if (array_key_exists($role, $newkey) == false) {
                    // suppression
                    $enrol = $tabenrol[$role];
                    wizard_unenrol_key ($enrol, $course);
                    $modif = true;
                }
            }
        }
        return $modif;
    }

    /**
     * Supprime l'urlfixe de cours modele si il y a lieu
     * utilise la fonction up1_meta_set_data() de la lib /local/up1_metadata
     */
    function remove_urlfixe_model() {
        global $CFG;
        $form1 = $this->formdata['form_step1'];
        $form2 = $this->formdata['form_step2'];
        if (isset($form2['urlok']) && $form2['urlok'] == 1) {
            if (isset($form2['urlmodel']) && $form2['urlmodel'] == 'fixe') {
                if (isset($form1['coursedmodelid']) && $form1['coursedmodelid'] != '0') {
                    require_once("$CFG->dirroot/local/up1_metadata/lib.php");

                    up1_meta_set_data($form1['coursedmodelid'], 'up1urlfixe', '');
                }
            }
        }
    }
}
