<?php
/**
 * Strings for component 'wizard', language 'en'
 *
 * @package    local
 * @subpackage crswizard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Course Wizard';
// capabilities
$string['crswizard:creator'] = 'Create a course with the wizard';
$string['crswizard:validator'] = 'Validate a course created with the wizard';
$string['crswizard:supervalidator'] = 'Validate ANY course created with the wizard';
$string['crswizard:localsupervalidator'] = 'Validate ANY course created with the wizard, local context';
$string['crswizard:rofreferenceeditor'] = 'Modifier les rattachements au ROF d\'un cours';
$string['crswizard:hybridattachment'] = 'Ajouter/modifier des rattachements au ROF dans cours hybrides';
$string['crswizard:duplicate'] = 'Dupliquer le cours avec l\'assistant.';

$string['blocHelp1SModel'] = 'Vos contenus (textes, documents, fichiers audio-visuels...) sont conservés. ' .
  '<br>Les activités (forums, devoirs...) sont remises à zéro. ' .
  '<br>Vos participants sont conservés et les cohortes sont annualisées.';
$string['blocHelp2SModel'] = '<ul><li>Si l\'identifiant Apogée (ou ROF) du cours existe toujours dans le cache ROF ;</li>'
    . '<li>si les cohortes concernées existent encore pour l\'année courante ;</li>'
    . '<li>si les enseignant.e.s sont les mêmes.</li></ul>'
    . 'La duplication rapide vous permettra de passer directement à la dernière étape, sinon cliquez sur "étape suivante".';
$string['blocTitleS1'] = "<h2 class='crswizardWarning'>Bienvenue, laissez-vous guider par l'Assistant de création</h2>";
$string['blocIntroS1'] = "Vous souhaitez créer un EPI pour : ";
$string['blocHelloS1'] = '<p>Bienvenue dans l\'assistant de création d\'espace de cours. '
    . 'Laissez-vous guider et définissez en quelques étapes les caractéristiques, les contributeurs '
    . 'et le public visé de votre EPI (Espace Pédagogique Interactif).</p>'
    . '<p>Pour commencer, choisissez si votre espace :'
    .'<ul><li>concerne un élément pédagogique de l\'offre de formation (diplôme, enseignement, groupe de TD, etc.)</li>'
    . '<li>ou répond à un autre besoin (projet particulier, formation pour les personnels, etc.).</li></ul></p>';
$string['bockhelpE2'] = "<h2 class='crswizardWarning'>Attention : le rattachement détermine l'intitulé de votre espace de cours et sa position dans l'index des EPI de l'UFR.</h2>";
$string['bockhelpE2Rof1'] = "<h2 class='crswizardWarning'>Attention : le rattachement détermine l'intitulé de votre espace de cours et sa position dans l’index des EPI de l'UFR.</h2>";
$string['bockhelpE2Rof2'] = '';
$string['bockhelpE3'] = '<p>Vous avez défini à l\'étape précédente le rattachement principal de votre espace '
    . 'de cours.<br/>Si ce dernier s\'adresse aux étudiants d\'une autre composante et/ou inscrits '
    . 'à un autre niveau de diplôme, il vous est possible de le spécifier ci-dessous.</p>';
$string['bockhelpE3validator'] = '<p>Toute demande de création d\'espace de cours fait l\'objet d’une modération. '
    . 'Notez qu\'il vous sera possible de désigner les enseignants contributeurs et d\'inscrire les '
    . 'groupes d\'étudiants aux étapes suivantes.</p>'
    .'<p><b>Cas 1</b> : Si vous êtes l\'enseignant responsable du diplôme, de l\'UE ou de l\'élément '
    . 'pédagogique pour lequel vous créez cet espace, ne renseignez pas d\'approbateur et passez à l\'étape suivante.<br/>'
    . '<b>Cas 2</b> : Si vous n\'êtes pas responsable de l\'élément pédagogique concerné (chargé de TD par '
    . 'exemple), désignez un enseignant chargé d\'approuver la création de l\'espace (en général le '
    . 'responsable du diplôme, de l\'UE ou de l\'enseignement pour lequel cet espace est créé).</p>'
    . '<ol><li>Recherchez l\'utilisateur dans l\'annuaire de l\'université, en saisissant, par exemple, son nom '
    . 'ou son identifiant Paris 1 ou le couple Prénom Nom. Notez que vous ne pouvez pas vous '
    . 'désigner comme approbateur de l\'espace que vous êtes entrain de créer.</li>'
    . '<li>Cliquez sur le symbole « + » pour désigner cet utilisateur comme approbateur de cet espace.</li></ol>';
$string['bockhelpE3autovalidator'] = "<h2 class='crswizardWarning'>NB : L'approbateur est la personne qui assume la responsabilité éditoriale de l'EPI et valide son rattachement</h2>";
$string['bockhelpE4'] = "<h2 class='crswizardWarning'>NB : Si plusieurs enseignants contribuent à l'EPI, veuillez renseigner soigneusement cette étape.</h2>";
$string['bockhelpE5'] = "<h2 class='crswizardWarning'>Attention : Cette étape permet <ul> "
    . "<li>d'autoriser l'accès à l'EPI pour vos groupes d'étudiants participants au cours"
    . "<li>d'envoyer aux groupes inscrits des annonces et des notifications pas mailing liste"
    . "<li>de répertorier l'EPI sur la page de chaque étudiant participant afin qu'il y accède directement"
    . "</ul></h2>";
$string['bockhelpE6'] = "<h2 class='crswizardWarning'>NB : Les clés sont des mots de passe que vous choisissez librement. "
  . "<br>Transmettre une clé à un utilisateur lui permet de s'auto-inscrire à l'EPI avec un rôle défini. "
  . "<br>En cas d'oubli de votre clé, vous pourrez la retrouver sur cette page en activant la case Révéler.";
$string['bockhelpE6clev'] = "Exemples : Collègue souhaitant visiter l'EPI, auditeur libre, ancien étudiant...";
$string['bockhelpE6cleu'] = "Exemples : Etudiant UP1 en attente d'inscription pédagogique, étudiants d'une université partenaire, autres situations d'exception diverses "
  . "<br><u>Définir cette clé est très recommandé.</u>";
$string['bockhelpE7p1'] = "<h2 class='crswizardWarning'>En cliquant sur le bouton Terminer vous finaliserez la création de votre EPI"
  . "<br>Affichez le récapitulatif de la demande pour vérifier les éléments que vous avez saisis"
  . "<br>Vous pourrez modifier à tout moment ces éléments via le menu <i>Navigation > Assistant de création > Paramètres</i></h2>";
$string['bockhelpE7p2'] = '</li></ul></p>'
    . '<p>Conseil : affichez le récapitulatif de votre demande de manière à vérifier les éléments que vous avez saisis. '
    . 'En cas d\'erreur ou d\'omission, il vous est possible revenir en arrière en cliquant '
    . 'sur le bouton « Etape précédente ».</p>';
$string['blocktitleE3Rof1'] = 'Enseignant non responsable de l\'élément pédagogique : désignation d\'un approbateur';
$string['blocktitleE4'] = 'Enseignant(s) contributeur(s) de l\'espace de cours';
$string['blocktitleE5'] = 'Étudiants : inscriptions par cohorte(s)';
$string['categoryblock'] = 'Catégorie (rattachement principal de l\'espace de cours)';
$string['categoryblockE2F'] = 'Catégorie (rattachement(s) de l\'espace de cours)';
$string['categoryblockE3'] = 'Rattachement principal de l\'espace';
$string['categoryblockE3s1'] = 'Autre(s) rattachement(s) de l\'espace (optionnel)';
$string['categoryerrormsg1'] = 'Le niveau sélectionné est invalide.';
$string['categoryerrormsg2'] = 'Veuillez sélectionner une période et un établissement.';
$string['cohort'] = 'Cohorte';
$string['cohortname'] = 'Libellé de groupe ou nom d\'étudiant';
$string['cohorts'] = 'Groupes';
$string['complementlabel'] = 'Complément : ';
$string['confirmation'] = 'Vos remarques ou questions concernant cet espace de cours';
$string['confirmationtitle'] = 'Étape 7 - finalisation de la demande';
$string['consigneremarque'] = 'Ici, vous pouvez apporter vos remarques/questions concernant la création de cet espace, '
    . 'solliciter un rendez-vous pour un accompagnement personnalisé (prise en main de l\'outil, conseil d\'utilisation, '
    . 'appui à la réalisation d\'un projet pédagogique, etc.).';
$string['coursedefinition'] = 'Étape 2 - identification de l\'espace';
$string['coursedescription'] = 'Étape 3 - autres rattachements (facultatif)';
$string['coursegeneralhelp'] = '<p>Le nom complet de l\'espace est affiché en haut de chacune des pages du cours et sur la '
    . 'liste des cours.<br/>Le nom abrégé de l\'espace est affiché dans le menu de navigation (en haut à gauche de '
    . 'l\'écran), dans le fil d\'Ariane et dans l\'objet de certains courriels. Le texte de présentation '
    . 'est en accès public : il est affiché sur la fiche signalétique de l\'espace accessible à partir '
    . 'de la page d\'accueil de la plateforme et dans les résultats d\'une recherche.</p>';
$string['coursegeneralhelpRof'] = "Si nécessaire, complétez l'intitulé de votre EPI dans le champ vierge."
    . "Le texte de présentation s'affichera en accès public sur la fiche signalétique de votre cours.";
$string['coursemodel'] = 'Modèle de création : ';
$string['courserequestdate'] = 'Date de la demande de création : ';
$string['coursesettingsblock'] = 'Paramétrage de l\'espace de cours';
$string['coursesettingshelp'] = 'Les dates ci-dessous sont purement informatives et correspondent au début '
    . 'et à la fin de la période d\'enseignement.';
$string['coursestartdate'] = 'Date de début : ';
$string['coursesummary'] = 'Texte de présentation : ';
$string['editingteacher'] = "Enseignant éditeur";
$string['responsable_epi'] = "Enseignant responsable EPI";
$string['enrolcohorts'] = 'Étape 5 - inscription des groupes étudiants';
$string['enrolkey'] = 'Clé d\'inscription';
$string['enrolteachers'] = 'Étape 4 - désignation des contributeurs enseignants';
$string['fastcopyerrormsg'] = 'Attention : toutes les conditions ne sont pas remplies pour permettre une duplication rapide';
$string['findcohort'] = 'Rechercher un groupe d\'étudiants';
$string['findteacher'] = 'Rechercher un enseignant';
$string['findvalidator'] = 'Rechercher un approbateur';
$string['finish'] = 'Terminer';
$string['fullnamecourse'] = 'Nom complet de l\'espace : ';
$string['generalinfoblock'] = 'Informations générales de l\'espace de cours';
$string['guest'] = 'Visiteur anonyme';
$string['guestkey'] = 'Clé d\'inscription pour le rôle "visiteur anonyme"';
$string['indexationE3'] = 'Métadonnées d\'indexation';
$string['labelE7ratt2'] = 'Autre(s) rattachement(s) de l\'espace : ';
$string['labelteachersuspended'] = 'Les enseignant suivants ne sont plus valables : ';
$string['managecourseblock'] = 'Informations concernant la demande';
$string['msgredirect'] = 'L\'espace a bien a été créé.';
$string['nextstage'] = 'Étape suivante';
$string['noeditingteacher'] = 'Enseignant non éditeur';
$string['previousstage'] = 'Étape précédente';
$string['rofselected1'] = 'Rattachement de référence';
$string['rofselected2'] = 'Rattachement(s) secondaire(s)';
$string['role'] = 'Rôle';
$string['selectcourse'] = 'Étape 1 - démarrage de l\'assistant';
$string['selectedcohort'] = 'Groupes sélectionnés';
$string['selectedteacher'] = 'Enseignants sélectionnés';
$string['selectedvalidator'] = 'Approbateur sélectionné';
$string['selectvalidator'] = 'Étape 3 : approbation de l\'espace';
$string['shortnamecourse'] = 'Nom abrégé de l\'espace : ';
$string['summaryof'] = 'Récapitulatif de la demande';
$string['student'] = 'Étudiant';
$string['stepkey'] = 'Étape 6 - configuration des clés d\'inscription';
$string['stepredirect'] = 'Étape 8 - espace créé - redirection';
$string['studentkey'] = 'Clé d\'inscription pour le rôle "étudiant"';
$string['teachername'] = 'Nom de l\'enseignant';
$string['teacher'] = 'Enseignant';
$string['up1composante'] = 'Autre(s) composante(s) : ';
$string['up1datefermeture'] = 'Date de fin : ';
$string['up1niveau'] = 'Autre(s) type(s) de diplôme(s) : ';
$string['userlogin'] = 'Login du demandeur : ';
$string['username'] = 'Nom du demandeur : ';
$string['teachers'] = 'Enseignants';
$string['validatorname'] = 'Nom de l\'approbateur';
$string['wizardcase1'] = 'Un élément pédagogique dans lequel j\'enseigne';
$string['wizardcase2'] = 'Un élément pédagogique de l\'offre de formation';
$string['wizardcase3'] = 'Un autre besoin en dehors de l\'offre de formation';
$string['wizardcourse'] = 'Création d\'un espace';

/** update **/
$string['upcancel'] = 'Annuler';
$string['upcoursedefinition'] = 'Étape 1 - identification de l\'espace';
$string['upcoursedescription'] = 'Étape 2 - identification de l\'espace (suite)';
$string['updatetitlecase2'] = 'Étape 4 : confirmation des modifications';
$string['updatetitlecase3'] = 'Étape 5 : confirmation des modifications';
$string['upenrolcohortscase2'] = 'Étape 2 - inscription des groupes étudiants';
$string['upenrolcohortscase3'] = 'Étape 3 - inscription des groupes étudiants';
$string['uprofreadonlymess'] = 'Les rattachements sont protégés, vous ne disposez pas des droits de modification.';
$string['upsavechanges'] = 'Enregistrer les modifications';
$string['upsummaryof'] = 'Récapitulatif des modifications';
$string['upstepkeycase2'] = 'Étape 3 - configuration des clés d\'inscription';
$string['upstepkeycase3'] = 'Étape 4 - configuration des clés d\'inscription';
$string['upwizardcourse'] = 'Modification des paramètres de l\'espace';

/** delete **/
$string['deletecourseexplain'] = "texte d'avertissement texte d'avertissement texte d'avertissement texte d'avertissement"
 . "<br /> répondre par OUI ou par NON";
$string['deletecoursebutton'] = "Supprimer définitivement l'espace";
$string['deleteconfirmationmsg'] = 'Répondre OUI / NON';

/** archive **/
$string['archivecourseexplain'] = "Veuillez répondre par OUI pour confirmer ou par NON pour sortir.";
$string['archivecoursebutton'] = "Archiver définitivement l'espace";
$string['archiveconfirmationmsg'] = 'Répondre OUI / NON';
$string['archivingcourse'] = 'Archivation de {$a}';

/** old **/
$string['up1domaine'] = 'Domaine(s) d\'enseignement : ';
$string['up1mention'] = 'Mention(s) : ';
$string['up1parcours'] = 'Parcours(s) : ';
$string['up1specialite'] = 'Spécialité(s) : ';
