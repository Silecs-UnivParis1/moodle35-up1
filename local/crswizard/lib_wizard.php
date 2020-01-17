<?php
/**
 * Edit course settings
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laters
 */

/* @var $DB moodle_database */

require_once("$CFG->dirroot/local/roftools/roflib.php");
require_once("$CFG->dirroot/local/cohortsyncup1/locallib.php");

/**
 * Vérifie, lors de la mise à jour d'un cours Hors Rof si l'établissement à été modifié.
 * Vérifie, lors de la création d'un cours HR à partir d'un modèle HR si l'établissement n'a pas
 * été modifié.
 * Dans ces cas, lance la méthode wizard_find_autres_rattachements()
 */
function wizard_autres_rattachements() {
    global $SESSION;
    $idetab = 0;
    $idetab_selected = 0;
    if (array_key_exists('form_step3', $SESSION->wizard) && array_key_exists('idetab', $SESSION->wizard['form_step3'])) {
        $idetab_selected = $SESSION->wizard['form_step3']['idetab'];
    } else {
        return false;
    }

    if (array_key_exists('init_course', $SESSION->wizard) && array_key_exists('category', $SESSION->wizard['init_course'])) {
        $init = $SESSION->wizard['init_course'];
        $tabpath = wizard_get_categorypath($init['category']);
        $idetab = $tabpath[2];
    }

    if ($idetab != $idetab_selected) {
        if (array_key_exists('rattachements', $SESSION->wizard['form_step3'])) {
            $rattachements = $SESSION->wizard['form_step3']['rattachements'];
            if (count($rattachements) == 0) {
                return false;
            }
            $SESSION->wizard['form_step3']['rattachements'] = wizard_find_autres_rattachements($rattachements, $idetab_selected);
        }
    }
}

/**
 * Retrouve les catégories de $rattachements correspondant à l'établissement $idetab_selected
 * @param array $rattachements contient les identifiants des catégories de rattachement secondaire
 * @param id $idetab_selected identifiant se l'établissement sélectionné en étape 2
 * @return array $mycategories
 */
function wizard_find_autres_rattachements($rattachements, $idetab_selected) {
    global $DB;
    $mycategories = $rattachements;
    $idnumber = $DB->get_field('course_categories', 'idnumber', array('depth'=>2, 'id'=>$idetab_selected), MUST_EXIST);
    if ( preg_match('@^2:([^/]+)/([^/]+)$@', $idnumber, $matches) ) {
        $yearcode = $matches[1];
        $etabcode = $matches[2];
    } else {
        return $rattachements;
    }
    foreach ($rattachements as $ra) {
        if (isset($ra) && $ra != '') {
            $category = $DB->get_record('course_categories', array('id'=>$ra), '*', MUST_EXIST);
            if ($category) {
                $eqvDiplomas = strstr(substr(strstr($category->idnumber, '/'), 1), '/');
                $masque = $category->depth . ':' . $yearcode .'/'. $etabcode . $eqvDiplomas;
                $res = $DB->get_field('course_categories', 'id', array('idnumber' => $masque));
                if ($res) {
                    $mycategories[] = $res;
                }
            }
        }
    }
    return $mycategories;
}

/**
 * récupère l'identifiant de la catégorie "établissement" (niveau 2) sélectionné en étape 2
 **/
function get_selected_etablissement_id() {
    global $SESSION;
    $tabpath = wizard_get_categorypath($SESSION->wizard['form_step2']['category']);
    $SESSION->wizard['form_step3']['idetab'] = $tabpath[2];
}

/**
 * Récupère les utilisateurs ayant des rôles de type teachers dans le cours d'identifiant $courseid
 * les utilisateurs de rôle ens_epi_archive deviennent editingteacher
 * @param int $courseid : identifiant du cours
 * @return array $users
 */
function wizard_get_teachers($courseid) {
    global $DB, $USER;
    $users = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    //ens_epi_archive
    $labels['ens_epi_archive'] = 'ens_epi_archive';
    $roles = wizard_role($labels);
    if (count($roles)) {
        $context = context_course::instance($courseid);
        foreach ($roles as $role) {
            $ra = $DB->get_records('role_assignments', array('roleid' => $role['id'], 'contextid' => $context->id));
            if (count($ra)) {
                foreach ($ra as $r) {
                   $user = $DB->get_record('user', array('id'=>$r->userid), '*');
                    if ($user) {
                        $nature = ($user->deleted ==0 && $user->suspended == 0 ? 'actif' : 'inactif');
                        $rolename = $role['shortname'];
                        if ($rolename == 'ens_epi_archive') {
                            $rolename = 'editingteacher';
                        }
                        $users[$nature][$rolename][$user->username] = $user;
                    }
                }
            }
        }
    }
    if (isset($users['actif']) && count($users['actif'])) {
        $code = 'editingteacher';
        $user = $DB->get_record('user', array('username' => $USER->username));
        $users['actif'][$code][$user->username] = $user;
    }
    return $users;
}

/**
 * Cherche les cohortes de l'année courante equivalent aux anciennes du cours modèle
 * @param int $courseid : identifiant du cours modèle
 * @return array $resultat
 */
function wizard_get_equivalent_cohorts($courseid) {
    global $DB;
    $resultat = array();
    $oldcohorts = wizard_get_cohorts($courseid);
    foreach ($oldcohorts as $role => $cohorts) {
        if (count($cohorts)) {
            $res = get_equivalent_cohorts($cohorts, false);
            $resultat['msg'][$role] = $res;
            foreach ($res as $co) {
                foreach ($co as $c) {
                    $resultat['group'][$role][] = $c;
                }
            }
        }
    }
    return $resultat;
}

/**
 * récupère des métadonnées du modèle si même cas
*/
function wizard_get_metadonnees() {
    global $SESSION, $DB;

    if (isset($SESSION->wizard['form_step1']['coursedmodelid']) && $SESSION->wizard['form_step1']['coursedmodelid'] != '0') {
        $id = $SESSION->wizard['form_step1']['coursedmodelid'];
        if ($id == SITEID){
            $SESSION->wizard['form_step1']['coursedmodelid'] = '0';
        }
        if (isset($SESSION->wizard['modele']) && $SESSION->wizard['modele'] != $id) {
            // on efface les données du prédédent choix
            wizard_clear_metadonnees();
        }
        $SESSION->wizard['modele'] = $id;
        $course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);
        $SESSION->wizard['modelecase'] = null;
        if ($course) {
            $custominfo_data = custominfo_data::type('course');
            $custominfo_data->load_data($course);

            //inscriptions teachers
            $teachers = wizard_get_teachers($course->id);
            if (isset($teachers['actif']) && count($teachers['actif'])) {
                $SESSION->wizard['form_step4']['all-users'] = $teachers['actif'];
            }
            if (isset($teachers['inactif']) && count($teachers['inactif'])) {
                $SESSION->wizard['form_step4']['users-inactif'] = $teachers['inactif'];
            }

            // cohortes
            $newcohorts = wizard_get_equivalent_cohorts($course->id);
            if (count($newcohorts)) {
                $SESSION->wizard['form_step5']['group'] = $newcohorts['group'];
                $SESSION->wizard['form_step5']['groupmsg'] = $newcohorts['msg'];
                $SESSION->wizard['form_step5']['all-cohorts'] = wizard_get_enrolement_cohorts();
            }

			$summary = array('text' => $course->summary, 'format' => $course->summaryformat);
			$SESSION->wizard['form_step2']['summary_editor'] = $summary;

            $case = wizard_get_generateur($course);
            $SESSION->wizard['modelecase'] = $case;
            if ($case == $SESSION->wizard['wizardcase']) {
                switch ($case) {
                    case 2:
                        $idcategory = $course->category;
                        $tabpath = wizard_get_categorypath($idcategory);
                        $SESSION->wizard['form_step2']['category'] = $tabpath[2];
                        $SESSION->wizard['form_step2']['rofestablishment'] = wizard_get_wizard_get_categoryname($tabpath[2]);
                        $SESSION->wizard['form_step2']['rofyear'] = wizard_get_wizard_get_categoryname($tabpath[1]);
                        $SESSION->wizard['form_step2']['fullname'] = $course->profile_field_up1rofname;
                        if (strpos($course->profile_field_up1rofid, ';') && strpos($course->profile_field_up1rofname, ';')) {
                            $SESSION->wizard['form_step2']['fullname'] = substr($course->profile_field_up1rofname, 0, strpos($course->profile_field_up1rofname, ';'));
                        }
                        // on peut vérifier si le premier rattachement est cohérent avec le reste des données
                        wizard_rof_connection($course->profile_field_up1rofpathid);
                        $SESSION->wizard['form_step2']['all-rof'] = wizard_get_rof();
                        $SESSION->wizard['init_course']['form_step2']['item'] = $SESSION->wizard['form_step2']['item'];

                        //ajout complément
                        $SESSION->wizard['form_step2']['complement'] = $course->profile_field_up1complement;

                        //ajout champ urlfixe
                        $SESSION->wizard['form_step2']['modelurl'] = make_url_fixe(
                            $course->profile_field_up1composante,
                            $course->profile_field_up1type,
                            $course->profile_field_up1rofname);
                        if (isset($SESSION->wizard['form_step2']['myurl']) == false || $SESSION->wizard['form_step2']['myurl'] == '') {
                            $SESSION->wizard['form_step2']['myurl'] = $SESSION->wizard['form_step2']['modelurl'];
                        }
                        $SESSION->wizard['form_step3']['up1approbateurpropid'] ='';
                        if (isset($course->profile_field_up1approbateurpropid)) {
                            $SESSION->wizard['form_step3']['up1approbateurpropid'] = $course->profile_field_up1approbateurpropid;
                        }
                        break;

                    case 3:
                        $SESSION->wizard['form_step2']['category'] = $course->category;
                        $SESSION->wizard['init_course']['category'] = $course->category;
                        if (isset($course->profile_field_up1categoriesbis)) {
                            $SESSION->wizard['form_step3']['rattachements'] = explode(';', $course->profile_field_up1categoriesbis);
                        }
                        // rattachement ROF
                        wizard_rof_connection($course->profile_field_up1rofpathid, false, 'form_step3');
                        $SESSION->wizard['form_step3']['all-rof'] = wizard_get_rof('form_step3');
                        $SESSION->wizard['init_course']['form_step3']['item'] = $SESSION->wizard['form_step3']['item'];

                        //metadonnees indexation pour cas 3 + gestion hybride
                        wizard_get_metadonnees_indexation($course);
                        //fin metadonnees indexation pour cas 3 + gestion hybride
                        break;
                }
                if (isset($course->profile_field_up1urlfixe) && $course->profile_field_up1urlfixe != '') {
                    $SESSION->wizard['form_step2']['modelurlfixe'] = $course->profile_field_up1urlfixe;
                    $SESSION->wizard['form_step2']['urlok'] = 1;
                    $SESSION->wizard['form_step2']['urlmodel'] = 'fixe';
                }
            }
        }
    } else {
        $SESSION->wizard['form_step1']['coursedmodelid'] = '0';
        wizard_clear_metadonnees();
    }
}

/**
 * Récupère les idnumber des cohortes de role student ou guest
 * inscrites a cours d'identifiant $courseid
 * @param int $courseid : identifiant du cours
 * @return array - un tableau de tableau array('role' => array())
 */
function wizard_get_cohorts($courseid) {
    global $DB;
    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $roles = wizard_role($labels);
    $roleint = array();
    foreach ($roles as $role) {
        $roleint[$role['id']] = $role['shortname'];
    }
    $enrols = $DB->get_records('enrol', array('courseid' => $courseid, 'enrol' => 'cohort'));
    foreach ($enrols as $enrol) {
        $cohortname = $DB->get_field('cohort', 'idnumber', array('id' => $enrol->customint1));
        if (isset($roleint[$enrol->roleid]) && $cohortname) {
            $list[$roleint[$enrol->roleid]][] = $cohortname;
        }
    }
    return $list;
}

/**
 * Construit la liste des cours dans lesquels $USER est inscrits avec la capacité course:update
 * @return array $course_list
 */
function wizard_get_course_list_teacher() {
    global $USER, $DB;
    $course_list = array();
    if ($courses = enrol_get_my_courses(NULL, 'id DESC')) {

        $course_list = array(' - / - ');
        $periodes = $DB->get_records('course_categories', array(), 'sortorder ASC');
        foreach ($courses as $course) {
            $coursecontext = context_course::instance($course->id);
            if ( has_capability('local/crswizard:duplicate', $coursecontext, $USER->id) ) {
                $path = substr($periodes[$course->category]->path, 1);

                if (strstr($path, '/')) {
                    $idperiode = (int) trim(substr($path, 0, strpos($path, '/')));
                } else {
                    $idperiode = trim($path);
                }
                $periode = $periodes[$idperiode];
                $fullname = trim($course->fullname);
                $fullname = preg_replace('/ +/', ' ', $fullname);
                $course_list[$course->id] = trim($periode->name)
                    . ' / ' . $fullname . ' (' . $course->id . ')';
            }
        }
    }
    return $course_list;
}

/**
 * construit la liste des cours de la catégorie désignée comme catégorie modèle.
 * Utilise le paramètre category_model des settings du plugin crswizard
 * @return array $course_list
 */
function wizard_get_course_model_list() {
    global $DB;
    $course_list = array();
    $category_model = get_config('local_crswizard','category_model');
    if ($category_model != 0) {
        $courses = $DB->get_records('course', array('category'=> $category_model), 'id, shortname');
        if (count($courses)) {
            foreach ($courses as $course) {
                $course_list['model_name'][$course->id] = $course->shortname;
                $course_list['model_summary'][$course->id] = $course->summary;
            }
        }
    }
    return $course_list;
}

/**
 * Fonction construsiant la liste des catégories de cours
 * @return array $wizard_make_categories_model_list
 */
function wizard_make_categories_model_list() {
    $displaylist = array();
    $displaylist = coursecat::make_categories_list('moodle/course:create');
    $wizard_make_categories_model_list = array(0 => 'Aucune');
    foreach ($displaylist as $key => $value) {
        $wizard_make_categories_model_list[$key] = $value;
    }
    return $wizard_make_categories_model_list;
}

/**
 * Fonction de redirection ad hoc avec message en dernière étape de création
 * Reprise partielle des fonctions redirect() et $OUTPUT->redirect_message()
 * @param string $url
 * @param string $message
 * @param int $delay
 */
function wizard_redirect_creation($url, $message='', $delay=5) {
    global $OUTPUT, $PAGE, $SESSION, $CFG;

    $localoutput = $OUTPUT;
    $localpage = $PAGE;

    if ($url instanceof moodle_url) {
        $url = $url->out(false);
    }
    $debugdisableredirect = false;

    $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);
    $url = str_replace('"', '%22', $url);
    $encodedurl = preg_replace("/\&(?![a-zA-Z0-9#]{1,8};)/", "&amp;", $url);
    $encodedurl = preg_replace('/^.*href="([^"]*)".*$/', "\\1", clean_text('<a href="'.$encodedurl.'" />', FORMAT_HTML));
    $url = str_replace('&amp;', '&', $encodedurl);


    if (!empty($message)) {
        if ($delay === -1 || !is_numeric($delay)) {
            $delay = 3;
        }
        $message = clean_text($message);
    } else {
        $message = get_string('pageshouldredirect');
        $delay = 0;
    }

    $CFG->docroot = false;

    if (!$debugdisableredirect) {
        // Don't use exactly the same time here, it can cause problems when both redirects fire at the same time.
        $localpage->requires->js_function_call('document.location.replace', array($url), false, ($delay + 3));
    }
    $PAGE->requires->css(new moodle_url('/local/crswizard/css/crswizard.css'));

    $site = get_site();
    $straddnewcourse = get_string("addnewcourse");
    $PAGE->navbar->add($straddnewcourse);

    $PAGE->set_title("$site->shortname: $straddnewcourse");
    $PAGE->set_heading($site->fullname);


    $output = $localoutput->header();

    $output .= $localoutput->box(get_string('wizardcourse', 'local_crswizard'), 'titlecrswizard');
    $output .= $localoutput->box(get_string('stepredirect', 'local_crswizard'), 'titlecrswizard');

    $output .= $localoutput->notification($message, 'redirectmessage');
    $output .= '<div class="continuebutton">(<a href="'. $encodedurl .'">'. get_string('continue') .'</a>)</div>';
    if ($debugdisableredirect) {
        $output .= '<p><strong>Error output, so disabling automatic redirect.</strong></p>';
    }
    $output .= $localoutput->footer();
    echo $output;
    exit;
}

/**
 * Reconstruit le tableau de chemins (période/é/c/niveau) pour le plugin jquery select-into-subselects.js
 * @return array
 * */
function wizard_get_mydisplaylist() {
    $displaylist = array();
    $displaylist = coursecat::make_categories_list();
    $mydisplaylist = array(" Sélectionner la période / Sélectionner l'établissement / Sélectionner la composante / Sélectionner le type de diplôme");

    foreach ($displaylist as $id => $label) {
        $parents = coursecat::get($id)->get_parents();
        $depth = count($parents);
        if ($depth > 1) {
            if ( $depth == 2) {
                if (coursecat::get($id)->get_children_count() == 0) {
                    $mydisplaylist[$id] = $label;
                }
            } else {
                $mydisplaylist[$id] = $label;
            }
        }
    }
    return $mydisplaylist;
}

/**
 * Reconstruit le tableau de chemins (période/établissement) pour le plugin jquery select-into-subselects.js
 * hack de la fonction wizard_get_mydisplaylist()
 * @return array
 * */
function wizard_get_catlevel2() {
    $displaylist = array();
    $displaylist = coursecat::make_categories_list();
    $mydisplaylist = [];

    foreach ($displaylist as $id => $label) {
        if (count(coursecat::get($id)->get_parents()) == 1) {
            $mydisplaylist[$id] = $label;
        }
    }
    return $mydisplaylist;
}

/**
 * Reconstruit le tableau de chemins (composantes/diplômes) pour le plugin jquery select-into-subselects.js
 * @param $idcat identifiant de la catégorie diplôme sélectionné à l'étape précédente
 * @param bool $fullpath chemin complet des catégories
 * @return array
 * */
function wizard_get_myComposantelist($idcat, $fullpath=false) {
    global $DB;
    $displaylist = array();
    $labelpath = '';
    $category = $DB->get_record('course_categories', array('id' => $idcat));
    $tpath = explode('/', $category->path);
    $annee = $DB->get_field('course_categories', 'name', array('id' => $tpath[1]));
    $selected = $DB->get_record('course_categories', array('id' => $tpath[2]));

    if ($fullpath) {
        $labelpath = $annee . ' / ';
    }

    $composantes = coursecat::get($selected->id)->get_children(array('sort' => 'idnumber ASC'));
    $mydisplaylist = array(" Sélectionner la composante / Sélectionner le type de diplôme");
    foreach ($composantes as $comp) {
        $mydisplaylist[$comp->id]  = $labelpath . $comp->name;
        if (coursecat::get($comp->id)->get_children_count() > 0) {
            $diplomes = coursecat::get($comp->id)->get_children(array('sort' => 'idnumber ASC'));
            foreach ($diplomes as $dip) {
                $mydisplaylist[$dip->id]  = $labelpath . $comp->name . ' / ' . $dip->name;
            }
        }

    }
    return $mydisplaylist;
}

/**
 * Returns the list of the names of the ancestor categories, including the target.
 * @global moodle_database $DB
 * @param integer $idcategory
 * @return array
 */
function get_list_category($idcategory) {
    global $DB;
    $selected = $DB->get_record('course_categories', array('id' => $idcategory));
    $tabidpath = explode('/', $selected->path);
    if (count($tabidpath) < 4) {
        throw new Exception("Wrong category [ $idcategory ] with path [ {$selected->path} ]");
    }
    $tabcategory = array();
    /**
     * @todo Fetch all names in one call to $DB->get_records_menu()
     */
    foreach ($tabidpath as $id) {
        if ($id) {
            $name = $DB->get_field('course_categories', 'name', array('id' => $id));
            if ($name) {
                $tabcategory[] = $name;
            }
        }
    }
    return $tabcategory;
}

/**
 * Renvoie le liste de valeurs pour la métadonnée de nom $type
 * @param string $type : nom de la métaddonnée de cours
 * @return array $list
 */
function get_list_metadonnees($type) {
    $list = array();
    switch ($type) {
        case 'up1semestre':
            $list[0] = 'Aucun';
            $list['1'] = '1';
            $list['2'] = '2';
            $list['3'] = '3';
            $list['4'] = '4';
            $list['5'] = '5';
            $list['6'] = '6';
            break;
        case 'up1niveauannee':
            $list[0] = 'Aucun';
            $list['1'] = '1';
            $list['2'] = '2';
            $list['3'] = '3';
            $list['4'] = '4';
            $list['5'] = '5';
            $list['6'] = '6';
            break;
        case 'up1niveau':
            $list[0] = 'Aucun';
            $list['L1'] = 'L1';
            $list['L2'] = 'L2';
            $list['L3'] = 'L3';
            $list['M1'] = 'M1';
            $list['M2'] = 'M2';
            $list['D'] = 'D';
            $list['Autre'] = 'Autre';
            break;
    }

    return $list;
}

/**
 * renvoie le tableau des métadonnées ajouté dans le cas 3
 * @param bool $label
 * @return array $metadonnees
 */
function get_array_metadonees($label = TRUE) {
    $metadonnees = array();
    if ($label) {
        $metadonnees = array('up1niveauannee' => 'Niveau année :',
        'up1semestre' => 'Semestre :',
        'up1niveau' => 'Niveau :');
    } else {
        $metadonnees = array('up1niveauannee', 'up1semestre', 'up1niveau');
    }
    return $metadonnees;
}

/**
 * Envoie un email à l'adresse mail spécifiée
 * @param string $email
 * @param string $subject,
 * @param string $message
 * @return false ou resultat de la fonction email_to_user()
 **/
function wizard_send_email($email, $subject, $message) {
    if (!isset($email) && empty($email)) {
        return false;
    }
    $supportuser = core_user::get_support_user();
    $user = new stdClass();
    $user->email = $email;
    return email_to_user($user, $supportuser, $subject, $message);
}

function myenrol_cohort($courseid, $tabGroup) {
    global $DB, $CFG;
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    $error = array();
    $enrol = 'cohort';
    $status = 0;   //ENROL_INSTANCE_ENABLED
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'student'));

    foreach ($tabGroup as $role => $groupes) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($groupes as $idgroup) {
            $cohort = $DB->get_record('cohort', array('idnumber' => $idgroup));
            if ($cohort) {
                if (!$DB->record_exists('enrol', array('enrol' => $enrol, 'courseid' => $courseid, 'customint1' => $cohort->id))) {
                    $instance = new stdClass();
                    $instance->enrol = $enrol;
                    $instance->status = $status;
                    $instance->courseid = $courseid;
                    $instance->customint1 = $cohort->id;
                    $instance->roleid = $roleid;
                    $instance->enrolstartdate = 0;
                    $instance->enrolenddate = 0;
                    $instance->timemodified = time();
                    $instance->timecreated = $instance->timemodified;
                    $instance->sortorder = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', array('courseid' => $courseid));
                    $DB->insert_record('enrol', $instance);
                }
            } else {
                $error[] = 'groupe "' . $idgroup . '" n\'existe pas dans la base';
            }
        }
    }

    require_once("$CFG->dirroot/enrol/cohort/locallib.php");
    $trace = new null_progress_trace();
    enrol_cohort_sync($trace, $courseid);
    return $error;
}

/**
 * désinscrit un ensemble de groupe à un cours
 * @param int $courseid
 * @param array $tabGroup
 */
function wizard_unenrol_cohort($courseid, $tabGroup) {
    global $DB, $CFG;
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    require_once("$CFG->dirroot/enrol/cohort/locallib.php");

    $enrol = 'cohort';
    $plugin_enrol = enrol_get_plugin($enrol);
    foreach ($tabGroup as $role => $groupes) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($groupes as $idgroup) {
            $cohort = $DB->get_record('cohort', array('idnumber' => $idgroup));
            if ($cohort) {
                $instance = $DB->get_record('enrol', array('courseid' => $courseid, 'enrol' => $enrol,
                    'roleid' => $roleid, 'customint1' => $cohort->id));
                if ($instance) {
                    $plugin_enrol->delete_instance($instance);
                }
            }
        }
    }
}

/**
 * supprime la cle d'inscription de type enrol du cours $course
 * @param string $enrol
 * @param course object $course
 */
function wizard_unenrol_key($enrol, $course) {
    global $DB, $CFG;
    $instance = $DB->get_record('enrol', array('courseid' => $course->id,
        'enrol' => $enrol, 'timecreated' => $course->timecreated));
    if ($instance) {
        require_once("$CFG->dirroot/enrol/".$enrol."/locallib.php");
        $plugin_enrol = enrol_get_plugin($enrol);
        $plugin_enrol->delete_instance($instance);
    }
}

/**
 * met à jour les paramètre d'une cle d'inscription existante
 * @param string $enrol nature de l'inscription (enrol.enrol)
 * @param object course $course
 * @param array $tabkey nouvelle valeur de la cle
 * @retun bool $modif
 */
function wizard_update_enrol_key($enrol, $course, $tabkey) {
    global $DB;
    $modif = false;
    $instance = $DB->get_record('enrol', array('courseid' => $course->id,
        'enrol' => $enrol, 'timecreated' => $course->timecreated));
    if ($instance) {
        $startdate = 0;
        $enddate = 0;
        if (isset($tabkey['enrolstartdate'])) {
                $startdate  = $tabkey['enrolstartdate'];
        }
        if (isset($tabkey['enrolenddate'])) {
            $enddate = $tabkey['enrolenddate'];
        }
        if (isset($tabkey['password']) && $tabkey['password'] != $instance->password) {
            $modif = true;
        }
        if ($startdate != $instance->enrolstartdate) {
            $modif = true;
        }
        if ($enddate != $instance->enrolenddate) {
            $modif = true;
        }
        if ($modif) {
            $DB->update_record('enrol', array('id' => $instance->id, 'password' => $tabkey['password'],
                'enrolstartdate' => $startdate, 'enrolenddate' => $enddate,
                'timemodified' => time()));
        }
    }
    return $modif;
}

function affiche_error_enrolcohort($erreurs) {
    $message = '';
    $message .= '<div><h3>Messages </h3>';
    $message .= '<p>Des erreurs sont survenues lors de l\'inscription des groupes :</p><ul>';
    foreach ($erreurs as $e) {
        $message .= '<li>' . $e . '</li>';
    }
    $message .= '</ul></div>';
    return $message;
}

function wizard_navigation($stepin) {
    global $SESSION;
    $SESSION->wizard['navigation']['stepin'] = $stepin;
    $SESSION->wizard['navigation']['suite'] = $stepin + 1;
    $SESSION->wizard['navigation']['retour'] = $stepin - 1;
}

/**
 * renvoie les rôles permis pour une inscription
 * @param $labels array role_shortname => label
 * @return array object role
 */
function wizard_role($labels) {
    global $DB;
    $roles = array();
    $sql = "SELECT * FROM {role} WHERE shortname = ?";
    foreach (array_keys($labels) as $key) {
        $record = $DB->get_record_sql($sql, array($key));
        if ($record != false) {
            $roles[$record->shortname] = array(
                'shortname' => $record->shortname,
                'name' => $record->name,
                'id' => $record->id
            );
        }
    }
    return $roles;
}

/**
 * Construit le tableau des enseignants sélectionnés
 * @return array
 */
function normalize_enrolment_users($tabUsers) {
    if (isset($tabUsers['responsable_epi'])) {
      foreach ($tabUsers['responsable_epi'] as $u) {
	if (!isset($tabUsers['editingteacher']))
	  $tabUsers['editingteacher'] = array();
	$tabUsers['editingteacher'][] = $u;
      }
    }
    return $tabUsers;
}

/**
 * Inscrit des utilisateurs à un cours sous le rôle sélectionné
 * @param int $courseid identifiant du cours
 * @param array $tabUsers array[rolename]=>array(iduser)
 */
function myenrol_teacher($courseid, $tabUsers) {
    global $DB, $CFG;
    require_once("$CFG->dirroot/lib/enrollib.php");
    if ($courseid == SITEID) {
        throw new coding_exception('Invalid request to add enrol instance to frontpage.');
    }
    foreach ($tabUsers as $role => $users) {
        $roleid = $DB->get_field('role', 'id', array('shortname' => $role));
        foreach ($users as $user) {
            $userid = $DB->get_field('user', 'id', array('username' => $user));
            if ($userid) {
                enrol_try_internal_enrol($courseid, $userid, $roleid);
            }
        }
    }
}

/**
 * Construit le tableau des groupes sélectionnés
 * @return array
 */
function wizard_get_enrolement_cohorts() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step5']['group'])) {
        return false;
    }

    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $roles = wizard_role($labels);
    $form5g = $SESSION->wizard['form_step5']['group'];

    foreach ($roles as $r) {
        $code = $r['shortname'];
        if (array_key_exists($code, $form5g)) {
            foreach ($form5g[$code] as $g) {
                $group = $DB->get_record('cohort', array('idnumber' => $g));
                if ($group) {
                    $size = $DB->count_records('cohort_members', array('cohortid' => $group->id));
                    $group->size = $size;
                    $list[$code][$group->idnumber] = $group;
                }
            }
        }
    }
    return $list;
}

/**
 * Construit le tableau des enseignants sélectionnés
 * @return array
 */
function wizard_get_enrolement_users() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step4']['user'])) {
        return false;
    }

    $list = array();
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    $roles = wizard_role($labels);
    $form4u = $SESSION->wizard['form_step4']['user'];
    foreach ($roles as $r) {
        $code = $r['shortname'];
        if (array_key_exists($code, $form4u)) {
            foreach ($form4u[$code] as $u) {
                $user = $DB->get_record('user', array('username' => $u));
                if ($user) {
                    $list[$code][$user->username] = $user;
                }
            }
        }
    }
    return $list;
}

/**
 * Enrole par défaut l'utilisateur comme teacher à son cours
 * @return array
 */
function wizard_enrolement_user() {
    global $DB, $USER;
    $list = array();
    $code = 'responsable_epi';
    $user = $DB->get_record('user', array('username' => $USER->username));
    $list[$code][$user->username] = $user;
    return $list;
}

/**
 * Construit le tableau des validateurs sélectionnés
 * @return array
 */
function wizard_get_validators() {
    global $DB, $SESSION;

    if (!isset($SESSION->wizard['form_step3']['user'])) {
        return false;
    }

    $list = array();
    $form3v = $SESSION->wizard['form_step3']['user'];
    foreach ($form3v as $u) {
        $user = $DB->get_record('user', array('username' => $u));
        if ($user) {
            $list[$user->username] = $user;
        }
    }
    return $list;
}

/**
 * Construit le tableau des objets pédagogiques du rof sélectionnés
 * @param string $form_step
 * @return array
 */
function wizard_get_rof($form_step = 'form_step2') {
    global $DB, $SESSION;
    if (!isset($SESSION->wizard[$form_step]['item'])) {
        return false;
    }
    $list = array();
    $formRof = $SESSION->wizard[$form_step]['item'];
    foreach ($formRof as $nature => $rof) {
        foreach ($rof as $rofpath => $rofid) {
            if ($rofid === FALSE ) {
                return $list;
            }
            $list[$rofpath]['nature'] = $nature;
            $list[$rofpath]['rofid'] = $rofid;
            $list[$rofpath]['path'] = $rofpath;
            $tabrof = rof_get_combined_path(explode('_', $rofpath));
            $list[$rofpath]['chemin'] = substr(rof_format_path($tabrof, 'name', false, ' / '), 3);

            $tabSource = '';
            if (substr($rofid, 0, 5) == 'UP1-P') {
                $tabSource = 'rof_program';
            } else {
                $tabSource = 'rof_course';
            }
            $object = $DB->get_record($tabSource, array('rofid' => $rofid));
            if ($object) {
                $list[$rofpath]['object'] = $object;
            }
        }
    }
    return $list;
}

/**
 * construit le tableau $form['item'] à partir de $_POST['item']
 * @param array() $postItem : $_POST['item']
 * @return array() $form['item']
 */
function wizard_get_array_item($postItem) {
    $tabitem = array();
    foreach ($postItem as $nature => $rof) {
        foreach($rof as $path) {
            $rofid = substr(strrchr($path, '_'), 1);
            $tabitem[$nature][$path] = $rofid;
        }
    }
    return $tabitem;
}

/*
 * construit la liste des groupes sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_cohort() {
    global $SESSION;
    if (empty($SESSION->wizard['form_step5']['all-cohorts'])) {
        return '[]';
    }
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_cohort;
    $liste = array();
    foreach ($SESSION->wizard['form_step5']['all-cohorts'] as $role => $groups) {
        $labelrole = '';
        if (isset($labels[$role])) {
            $labelrole = "<span>" . get_string($labels[$role], 'local_crswizard') . "</span>";
        }
        foreach ($groups as $id => $group) {
            $desc = '';
            if (isset($group->size) && $group->size) {
                $desc =  $group->size . ' inscrits';
            }
            $liste[] = array(
                "label" => $group->name . ' — ' . $desc . ' (' . $labelrole . ')',
                "value" => $id,
                "fieldName" => "group[$role]",
            );
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des enseignants sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_users() {
    global $SESSION;
    global $USER;
    if (!isset($SESSION->wizard['form_step4']['all-users'])) {
        return '[]';
    }
    $myconfig = new my_elements_config();
    $labels = $myconfig->role_teachers;
    $liste = array();
    if (!empty($SESSION->wizard['form_step4']['all-users'])) {
        foreach ($SESSION->wizard['form_step4']['all-users'] as $role => $users) {
            $labelrole = '';
            if (isset($labels[$role])) {
                $labelrole = ' (' . get_string($labels[$role], 'local_crswizard') . ')';
            }

            foreach ($users as $id => $user) {
                //si $USER - responsable_epi
                if ($user->id == $USER->id) {
                    $liste[] = array(
                        "label" => fullname($user) . ' — ' . $user->username . get_string('responsable_epi', 'local_crswizard'),
                        "value" => $id,
                        "fieldName" => "user[responsable_epi]",
                    );
                } else {
                    $liste[] = array(
                        "label" => fullname($user) . ' — ' . $user->username . ' (' . $labelrole . ')  ',
                        "value" => $id,
                        "fieldName" => "user[$role]",
                    );
                }
            }
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des objets pédagogiques du rof sélectionnés encodée en json
 * @param string $form_step
 * @return string
 */
function wizard_preselected_rof($form_step = 'form_step2') {
    global $SESSION;
    if (isset($SESSION->wizard[$form_step]['broken-rof'])) {
        unset($SESSION->wizard[$form_step]['broken-rof']);
    }
    if (!isset($SESSION->wizard[$form_step]['all-rof'])) {
        return '[]';
    }
    $liste = array();
    if (!empty($SESSION->wizard[$form_step]['all-rof'])) {
        foreach ($SESSION->wizard[$form_step]['all-rof'] as $rofpath => $rof) {
            if (isset($rof['object']) && stristr($rof['chemin'], 'Référence cassée') == false) {
                $object = $rof['object'];
                $tabrof = rof_get_combined_path(explode('_', $rof['path']));
                $chemin = substr(rof_format_path($tabrof, 'name', false, ' > '), 3);
                $liste[] = array(
                        "label" => rof_combined_name($object->localname, $object->name),
                        "path" => $rof['path'],
                        "nature" => $rof['nature'],
                        "chemin" => $chemin,
                );
            } else {
                $SESSION->wizard[$form_step]['broken-rof'][] = $rof;
            }
        }
    }
    return json_encode($liste);
}

/*
 * construit la liste des validateurs sélectionnés encodée en json
 * @return string
 */
function wizard_preselected_validators() {
    global $SESSION;
    if (!isset($SESSION->wizard['form_step3']['all-validators'])) {
        return '[]';
    }
    $liste = array();
    $labelrole = ' (approbateur)';
    if (!empty($SESSION->wizard['form_step3']['all-validators'])) {
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $id => $user) {
            $liste[] = array(
                "label" => fullname($user) . ' — ' . $user->username . $labelrole,
                "value" => $id,
            );
        }
    }
    return json_encode($liste);
}

function wizard_list_clef($form6) {
    $list = array();
    if (isset($form6['libre']) && $form6['libre'] == 1) {
        // pas de clef visiteur
        $tabCle = array('u' => 'Etudiante');
        $list['Visiteur'] = array('code' => 'v', 'password' => '');
    } else {
        $tabCle = array('u' => 'Etudiante', 'v' => 'Visiteur');
    }

    foreach ($tabCle as $c => $type) {
        $password = 'password' . $c;
        $enrolstartdate = 'enrolstartdate' . $c;
        $enrolenddate = 'enrolenddate' . $c;
        if (isset($form6[$password])) {
            $pass = trim($form6[$password]);
            if ($pass != '') {
                $list[$type]['code'] = $c;
                $list[$type]['password'] = $pass;
                if (isset($form6[$enrolstartdate])) {
                    $list[$type]['enrolstartdate'] = $form6[$enrolstartdate];
                }
                if (isset($form6[$enrolenddate])) {
                    $list[$type]['enrolenddate'] = $form6[$enrolenddate];
                }
            }
        }
    }
    return $list;
}

/**
 * Renvoie le nom du Course custom fields de nom abrégé $shortname
 * @param string $shortname nom abrégé du champ
 * @return string nom du champ
 */
function get_custom_info_field_label($shortname) {
    global $DB;
    return $DB->get_field('custom_info_field', 'name', array('objectname' => 'course', 'shortname' => $shortname));
}

/**
 * renvoie l'idendifiant d' l'user sélectionné comme validateur ou chaine vide
 * @return string
 */
function wizard_get_approbateurpropid() {
    global $SESSION;
    $approbateurpropid = '';
    if (isset($SESSION->wizard['form_step3']['all-validators']) && !empty($SESSION->wizard['form_step3']['all-validators'])) {
        foreach ($SESSION->wizard['form_step3']['all-validators'] as $user) {
            $approbateurpropid = $user->id . ';';
        }
        $approbateurpropid = substr($approbateurpropid, 0, -1);
    }
    return $approbateurpropid;
}

/**
 * Returns a unique course idnumber, by appending a serialnumber (-01, -02 ...) to code/rofid
 * takes in account the content of the course table
 * @global moodle_database $DB
 * @param string $rofid
 * @return string new idnumber to be used in course creation
 */
function wizard_rofid_to_idnumber($rofid) {
    global $DB;

    $code = rof_get_code_or_rofid($rofid);
    $sql = "SELECT idnumber FROM {course} c WHERE idnumber LIKE '" . $code . "%'";
    $res = $DB->get_fieldset_sql($sql);
    if ($res ) {
        $serials = array_map('__get_serialnumber', $res);
        return $code .'-'. sprintf('%02d', (1 + max($serials)));
    }
    return $code . '-01';
}

function __get_serialnumber($idnumber) {
    if (preg_match('/-(\d+)$/', $idnumber, $match)) {
        return (integer)$match[1];
    }
    return 0;
}

/**
 * Calcule idcat Moodle et identifiant cours partir d'un identifiant rof
 * @param array() $form2
 * @param bool $change si true, on recalcule de idnumber
 * @return array() $rof1 - idcat, apogee et idnumber
 */
function wizard_prepare_rattachement_rof_moodle($form2, $change=true) {
    global $DB;
    $rof1 = array();
    if (isset($form2['item']) && count($form2['item'])) {
        $allrof = $form2['item'];
        if (isset($allrof['p']) && count($allrof['p']) == 1) {
            $rofpath = key($allrof['p']);
            $rofid = $allrof['p'][$rofpath];
            $rof1['rofid'] = $rofid;
            $tabpath = explode('_', $rofpath);
            $rof1['tabpath'] = $tabpath;
            $idcategory = rof_rofpath_to_category($tabpath, $form2['category']);
            if ($idcategory) {
                $rof1['idcat'] = $idcategory;
                $category = $DB->get_record('course_categories', array('id' => $idcategory));
                $rof1['up1niveaulmda'] = $category->name;
                $rof1['up1composante'] = $DB->get_field('course_categories', 'name', array('id' => $category->parent));
            }
            $rof1['apogee'] = rof_get_code_or_rofid($rofid);
            if ($change == true) {
                $rof1['idnumber'] = wizard_rofid_to_idnumber($rofid);
            }
        }
    }
    return $rof1;
}

/**
 * renvoie la liste des identifiants catégorie moodle des rattachements secondaires
 * @param array() $form
 * @return string $rofidmoodle : identifiants séparés par des ;
 */
function wizard_get_idcat_rof_secondaire($form) {
    global $DB;
    $rofidmoodle = '';
    $idetab = null;
    if (array_key_exists('category', $form)) {
        $idetab = $form['category'];
    } elseif (array_key_exists('idetab', $form)) {
        $idetab = $form['idetab'];
    }

    if (isset($form['item']) && count($form['item'])) {
        $allrof = $form['item'];
        if (isset($allrof['s']) && count($allrof['s'])) {
            foreach ($allrof['s'] as $rofpath => $rofid) {
                $tabpath = explode('_', $rofpath);
                $idcategory = rof_rofpath_to_category($tabpath, $idetab);
                if ($idcategory) {
                    $rofidmoodle .= $idcategory . ';';
                }
            }
        }
    }
    if ($rofidmoodle != '' && substr($rofidmoodle, -1) == ';') {
        $rofidmoodle = substr($rofidmoodle, 0, -1);
    }
    return $rofidmoodle;
}

/**
 * Retourne le rofid, rofpathid et rofname des rattachements secondaires
 * @param array() $form
 * @return array() $rof2 - rofid, rofpathid, rofname et tabpath
 */
function wizard_prepare_rattachement_second($form) {
    $rof2 = array();
    if (isset($form['item']) && count($form['item'])) {
        $allrof = $form['item'];
        if (isset($allrof['s']) && count($allrof['s'])) {
            foreach($allrof['s'] as $rofpath => $rofid) {
                $rof2['rofid'][] = $rofid;
                if ($rofid !== FALSE) {
                    $path = strtr($rofpath, '_', '/');
                    $rof2['rofpathid'][] = '/' . $path;

                    $tabpath = explode('_', $rofpath);
                    // nouvelle politique de rattachement secondaire
                    $rof2['tabpath'][] = $tabpath;

                    $tabrof = rof_get_combined_path($tabpath);
                    $chemin = substr(rof_format_path($tabrof, 'name', false, ' / '), 3);
                    $rof2['rofchemin'][] = $chemin;
                }
                if ($rofid !== FALSE) {
                    if (isset($form['all-rof'][$rofpath]['object'])) {
                        $rofobjet =  $form['all-rof'][$rofpath]['object'];
                        $rof2['rofname'][] = rof_combined_name($rofobjet->localname, $rofobjet->name);
                    }
                }
            }
        }
    }
    return $rof2;
}

/**
 * @param array $tabcat rattachements supplémentaires
 * @param array $tabcategories rattachements associés à la catégorie principale
 * @return array
 */
function wizard_get_rattachement_fieldup1($tabcat, $tabcategories) {
   global $DB;
    $fieldup1 = array();
    $niveau = isset($tabcategories[3]) ? $tabcategories[3] : '';
    $composante = isset($tabcategories[2]) ? $tabcategories[2] : '';
    $listecat = '';
    if (count($tabcat)) {
        $listecat = implode(",", $tabcat);
    }
    if ($listecat != '') {
        $sqlcatComp = "SELECT DISTINCT name FROM {course_categories} WHERE id IN (" . $listecat . ") AND depth=3";
        $catComp = array();
        $catComp = $DB->get_fieldset_sql($sqlcatComp);

        $sqlcatDip = "SELECT * FROM {course_categories} WHERE id IN (" . $listecat . ") AND depth=4";
        $catDip = $DB->get_records_sql($sqlcatDip);

        $tabnewcomp = array();
        $tabdip = array();
        foreach ($catDip as $dip) {
            if(!in_array($dip->parent, $tabcat)) {
                $tabnewcomp[] = $dip->parent;
            }
            if ($dip->name != $niveau) {
                 $tabdip[$dip->name] = $dip->name;
            }
        }
        foreach ($tabdip as $dip) {
            $niveau .= ';' . $dip;
        }

        $catnewComp = array();
        if (count($tabnewcomp)) {
            $listenewcat = implode(",", $tabnewcomp);
            $sqlcatComp = "SELECT DISTINCT name FROM {course_categories} WHERE id IN (" . $listenewcat . ") AND depth=3";
            $catnewComp = $DB->get_fieldset_sql($sqlcatComp);
        }

        $tabcres =  array_merge($catComp, $catnewComp);
        $comps = array_unique($tabcres);
        foreach($comps as $comp) {
            if ($comp != $tabcategories[2]) {
                $composante .= ';' . $comp;
            }
        }
    }
    $fieldup1['profile_field_up1composante'] = $composante;
    $fieldup1['profile_field_up1niveaulmda'] = $niveau;
    return $fieldup1;
}

/**
 * Stocke si besoin dans $SESSION->wizard['form_step1'] les informations relavives au cours modèle
 * sélectionné et crée le backup associé
 */
function get_selected_model() {
    global $SESSION,$USER, $DB;
    $form_model = $SESSION->wizard['form_step1'];
    $coursemodelid = 0;
    $go = true;

    if (array_key_exists($form_model['modeletype'], $form_model)) {
        $coursemodelid = $form_model[$form_model['modeletype']];
        if (isset($SESSION->wizard['form_step1']['coursedmodelid']) &&
            $SESSION->wizard['form_step1']['coursedmodelid'] == $coursemodelid) {
                $go = false;
        }
        if ($go) {
            $coursemodel = $DB->get_record('course', array('id' => $coursemodelid), '*', MUST_EXIST);
            if ($coursemodel) {
                $SESSION->wizard['form_step1']['coursedmodelid'] = $coursemodelid;
                $SESSION->wizard['form_step1']['coursemodelfullname'] = $coursemodel->fullname;
                $SESSION->wizard['form_step1']['coursemodelshortname'] = $coursemodel->shortname;
            }
        }
    }
}

/**
 * Construit la partie singulière de l'url fixe canonique
 * @param string $composante
 * @param string $typeRof
 * @param string $nomRof
 * @return string $url
 */
function make_url_fixe($composante, $typeRof, $nomRof) {
    $url = '';
    $comp = explode(';', $composante);
    if (isset($comp[0]) && $comp[0] != '') {
        $mycomp = substr($comp[0], 0, strpos($comp[0], '-'));
        $url .= $mycomp . '-';
    }

    $type = explode(';', $typeRof);
    if (isset($type[0]) && $type[0] != '') {
        $mytype = substr($type[0], 0, strpos($type[0], ']'));
        $mytype = substr($mytype, 1);
        $url .= $mytype . '-';
    }
    $name = explode(';', $nomRof);
    if (isset($name[0]) && $name[0] != '') {
        $myname = wizard_normalize($name[0]);
        $url .= $myname;
    }

    return $url;
}

/**
 * retourne les consignes d'utilisation des URL pérenne
 * @return array $html
 */
function wizard_urlfixe_info() {
    $html = [];
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

    $html[1] = $htmlUrl;

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
    $html[2] = $htmlUrl2;
    return $html;
}

/**
 * retourne la chaine de caratère sans caratères spéciaux ni caractères accentués
 * et dont les espaces sont remplacés par -
 * @param string $chaine
 * @return string
 */
function wizard_normalize($chaine) {
    $chaine = strtolower(trim($chaine));
    $chaine = str_replace(' ', '-', $chaine);
    $chaine = iconv("UTF-8", "ASCII//TRANSLIT", $chaine);
    $chaine = preg_replace('/[^-_A-Za-z0-9]*/', '', $chaine);
    return $chaine;
}

/**
 * vérifie si $url est une URL pérenne conforme
 * @param string $url
 * @param bool||id identifiant du cours
 * @return array $myerrors
 */
function wizard_form2_validation_myurl($url, $idcourse) {
    global $DB, $SESSION;
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

    $samecourse = '';
    if ($idcourse) {
        $samecourse = ' AND objectid != ' . $idcourse . ' ';
    }
    $sql = "SELECT count(objectid) FROM {custom_info_field} cf "
        . "JOIN {custom_info_data} cd ON (cf.id = cd.fieldid) "
        . "WHERE cf.objectname='course' AND cd.objectname='course' "
        . $samecourse . "AND cf.shortname=? AND data =?";
    $res = $DB->get_field_sql($sql, array('up1urlfixe', $url));
    if ($res) {
        $myerrors[] = 'Désolé, cette URL est déjà utilisée. Veuillez choisir un autre nom';
    }
    return $myerrors;
}

/**
 * Renvoie vrai si le cours peut être dupliqué rapidement
 * @return bool
 */
function wizard_is_fastCopy() {
    global $SESSION;
    if (!isset($SESSION->wizard['modele'])) {
        return false;
    }
    if ($SESSION->wizard['modele'] == 0) {
        return false;
    }
    //le cours ne correspond à aucun modele
    if (!isset($SESSION->wizard['modelecase'])) {
        return false;
    }
    if ($SESSION->wizard['modelecase'] != $SESSION->wizard['wizardcase']) {
        return false;
    }
    if ($SESSION->wizard['wizardcase'] == 3) {
        $newcategory = wizard_get_current_category($SESSION->wizard['form_step2']['category']);
        if ($newcategory) {
            $SESSION->wizard['form_step2']['category'] = $newcategory->id;
        } else {
            $SESSION->wizard['form_step2']['lostcategory'] = $SESSION->wizard['form_step2']['category'];
            return false;
        }
    }
    if ($SESSION->wizard['wizardcase'] == 2 && isset($SESSION->wizard['form_step2']['all-rof'])) {
        foreach ($SESSION->wizard['form_step2']['all-rof'] as $rofpath => $rof) {
            if (!isset($rof['object']) || stristr($rof['chemin'], 'Référence cassée')) { // && $rof['nature'] == 'p'
                return false;
            }
        }
    }

    if (isset($SESSION->wizard['form_step4']['users-inactif']) && count($SESSION->wizard['form_step4']['users-inactif'])) {
        return false;
    }
    if (isset($SESSION->wizard['form_step5']['groupmsg']) && count($SESSION->wizard['form_step5']['groupmsg'])) {
        foreach ($SESSION->wizard['form_step5']['groupmsg'] as $infos) {
            if (isset($infos['notfound']) && count($infos['notfound'])) {
                return false;
            }
        }
    }
    return true;
}

/**
 * complète le paramètre $SESSION->wizard avec les données obligatoire
 * pour une duplication rapide
 */
function wizard_get_default_metadata() {
    global $SESSION, $USER, $DB;
    $SESSION->wizard['form_step1']['fastcopy'] = true;
    $SESSION->wizard['form_step2']['startdate'] = time();
    $SESSION->wizard['form_step2']['up1datefermeture'] = strtotime(date('m') <= 6 ? "July 31" : "next year January 31");
    $SESSION->wizard['form_step2']['visible'] = 0;
    if (isset($SESSION->wizard['wizardcase']) && $SESSION->wizard['wizardcase'] == 2) {
        $idetab = get_config('local_crswizard','cas2_default_etablissement');
        if (isset($SESSION->wizard['form_step2']['category'])  && $SESSION->wizard['form_step2']['category'] != $idetab) {
            $SESSION->wizard['form_step2']['category'] = $idetab;
        }
        if (isset($SESSION->wizard['form_step3']['up1approbateurpropid']) && $SESSION->wizard['form_step3']['up1approbateurpropid'] != '') {
            if ($SESSION->wizard['form_step3']['up1approbateurpropid'] == $USER->id) {
                $SESSION->wizard['form_step3'] = ['autovalidation' => 'on']; // autovalidation
            } else {
                $username = $DB->get_field('user', 'username', array('id' => $SESSION->wizard['form_step3']['up1approbateurpropid']));
                if ($username) {
                    $SESSION->wizard['form_step3']['user'][] = $username;
                    $SESSION->wizard['form_step3']['all-validators'] = wizard_get_validators();
                }
            }
        }
    }

    if (isset($SESSION->wizard['wizardcase']) && $SESSION->wizard['wizardcase'] == 3) {
        //TODO shortname et fullname à retravailler
        $SESSION->wizard['form_step2']['fullname'] = wizard_get_new_course_name($SESSION->wizard['form_step1']['coursemodelfullname']);
        $SESSION->wizard['form_step2']['shortname'] = wizard_get_new_course_name($SESSION->wizard['form_step1']['coursemodelshortname']);
        //attachement secondaires
        if (isset($SESSION->wizard['form_step3']['rattachements']) && count($SESSION->wizard['form_step3']['rattachements'])) {
            $attachements = [];
            foreach ($SESSION->wizard['form_step3']['rattachements'] as $attachement) {
                if ($attachement != '') {
                    $newattachement = wizard_get_current_category($attachement);
                    if ($newattachement) {
                        $attachements[] = $newattachement->id;
                    }
                }
            }
            $SESSION->wizard['form_step3']['rattachements'] = $attachements;
        }
    }

    if (isset($SESSION->wizard['form_step4']['all-users']) && count($SESSION->wizard['form_step4']['all-users'])) {
        $teachers = [];
        foreach ($SESSION->wizard['form_step4']['all-users'] as $role => $users) {
            foreach ($users as $user) {
                $teachers[$role][] = $user->username;
            }
        }
        $SESSION->wizard['form_step4']['user'] = $teachers;
    }
}

/**
 * renvoie l'indentifiant de la catégorie correspondant à l'établissement
 * défini par cas2_default_etablissement
 * @param int $idcategory
 * @return object course_categories or false
*/
function wizard_get_current_category($idcategory) {
    global $DB;
    $idetab = get_config('local_crswizard','cas2_default_etablissement');
    $category = $DB->get_record('course_categories', array('id'=>$idcategory), '*');
    if ($category) {
        $path = explode('/', $category->path);
        if (isset($path[2]) && $path[2] == $idetab) {
            return $category;
        }
        $idnumber = explode('/', $category->idnumber);
        $etab = $DB->get_record('course_categories', array('id'=>$idetab), '*', MUST_EXIST);
        $newidnumber = $category->depth . substr($etab->idnumber, 1);
        foreach ($idnumber as $pos => $label) {
            if ($pos >= $etab->depth) {
                $newidnumber .= '/' . $label;
            }
        }
        return $DB->get_record('course_categories', array('idnumber'=>$newidnumber), '*');
    }
    return false;
}

/**
 * Adapte le nom du cours à la période sélectionnée par défaut
 * @param string $name
 * @return string $newname
 */
function wizard_get_new_course_name($name) {
    $periode = wizard_get_default_periode();
    $years = substr($periode->idnumber, 2);
    $newname = '';
    if ( preg_match('@^(.+?)(20[0-9][0-9])([-_ /]*)(20[0-9][0-9])$@', $name, $matches) ) {
        $newname = trim($matches[1]);
    } elseif (preg_match('@^(.+?)([-_ /]*)(20[0-9][0-9])$@', $name, $matches)) {
        $newname = trim($matches[1]);
    } else {
        $newname = trim($name);
    }
    return $newname . ' ' . $years;
}

/**
 * renvoie la période correspondant à l'établissement par défaut défini
 * par le paramètre "Valeur par défaut de l'établissement" de l'assistant de cours
 * @return object or false
 */
function wizard_get_default_periode() {
    global $DB;
    $idetab = get_config('local_crswizard','cas2_default_etablissement');
    if (isset($idetab) && $idetab) {
        $sql = "SELECT p.name, p.idnumber FROM {course_categories} c JOIN {course_categories} p "
            . "ON (c.parent = p.id) WHERE c.id = ?";
        return $DB->get_record_sql($sql, array($idetab));
    }
    return false;
}

class my_elements_config {
    public $categorie_cours = array(
        'Période', 'Etablissement', 'Composante : ', 'Type de diplôme : '
    );
    public $role_teachers = array(
        'editingteacher' => 'editingteacher',
        'teacher' => 'noeditingteacher',
	'responsable_epi' => 'responsable_epi',
    );
    public $role_cohort = array(
        'student' => 'student',
        'guest' => 'guest'
    );
    public $categorie_deph = array(
        '1' => 'Période',
        '2' => 'Etablissement',
        '3' => 'Composante',
        '4' => 'Niveau',
    );
}
