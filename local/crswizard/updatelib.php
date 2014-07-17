<?php

global $CFG;

require_once(__DIR__ . '/lib_wizard.php');

function update_course_idnumber() {
    global $DB;
    $timestart = microtime();
    echo "<p>Mise à jour des idnumber</p>";
    $idgenerateur = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1generateur'));
    $idrofid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofid'));
    $idcomplement = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1complement'));

    $sqlgenerateur = "select objectid from {custom_info_data} where objectname='course' and fieldid=$idgenerateur and data ='Manuel via assistant (cas n°2 ROF)'";
    $tabgenerateur = $DB->get_records_sql($sqlgenerateur);

    echo "<p>Nombre total de cours de type 2 : ";
    echo count($tabgenerateur);
    echo "</p>\n";

    $sqlcomplement = "select objectid, data from {custom_info_data} where objectname='course' and fieldid=$idcomplement and data != ''";
    $tabcomplement = $DB->get_records_sql($sqlcomplement);

    // cas2 et cas3 hybrides
    $sqlrofid = "select objectid, data from {custom_info_data} where objectname='course' and fieldid=$idrofid";
    $tabrofid = $DB->get_records_sql($sqlrofid);

    $sql = "select id, fullname, shortname, idnumber from {course} where idnumber =''  order by id";
    $courses = $DB->get_records_sql($sql);
    echo "<p>Nombre total de cours sans idnumber : ";
    echo count($courses);
    echo "</p>\n";
    $nbcoursecorr = 0;

    foreach ($courses as $course) {
        if (array_key_exists($course->id, $tabgenerateur)) {
            $rofid = '';
            if (array_key_exists($course->id, $tabrofid)) {
                echo ' . ';
                $rofids = $tabrofid[$course->id]->data;
                if (strstr($rofids, ';') == false) {
                    $rofid = trim($rofids);
                } else {
                    $tabrof= array();
                    $tabrof = explode(';', $rofids);
                    if (count($tabrof)) {
                        $rofid = trim($tabrof[0]);
                    }
                }
                if ($rofid != '') {
                    $idnumber = wizard_rofid_to_idnumber($rofid);
                    $shortname = $idnumber;
                    if (array_key_exists($course->id, $tabcomplement)) {
                        $shortname .= ' - ' . $tabcomplement[$course->id]->data;
                    }
                    $DB->update_record('course', array('id' => $course->id, 'idnumber' => $idnumber, 'shortname' => $shortname));
                }
            }
            echo "\n";
            ++$nbcoursecorr;
        }
    }
    echo "\n<p>";
    echo "Nombre de cours corrigé : " . $nbcoursecorr;
    echo "</p>\n";
    $timeend = microtime();

    $time = $timeend - $timestart;
    echo "<p>temps opération (en seconde) : " . $time;
    echo "</p>\n";
}

function update_enrol_self() {
    global $DB;
    $timestart = microtime();
    echo "<p>Correction des Clefs d'inscription étudiante</p>";
    $sql = "UPDATE {enrol} SET customint5=0, customint6=1 WHERE enrol = 'self' and name = 'clef Etudiante' and customint6 is null";
    $DB->execute($sql);
    $timeend = microtime();
    $time = $timeend - $timestart;
    echo "<p>Mise à jour terminée</p>";
    echo "<p>temps opération (en seconde) : " . $time;
    echo "</p>\n";

}
