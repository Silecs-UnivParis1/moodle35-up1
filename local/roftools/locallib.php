<?php
/**
 * @package    local
 * @subpackage roftools
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once($CFG->dirroot . "/course/lib.php");
require_once($CFG->dirroot . "/lib/coursecatlib.php");

/* @var $DB moodle_database */

// Classes d'équivalence des diplômes pour les catégories
function equivalent_diplomas() {

    $diplomaEqv = array(
        'Licences' => 'L1,L2,L3,DP',
        'Masters' => 'M1,E1,M2,E2,30',
        'Doctorats' => '40',
        'Autres' => 'U2,U3,U4,U5,U6,PG,PC,PA,P1'
    );

    foreach ($diplomaEqv as $eqv => $strdiplomas) {
        $diplomas = explode(',', $strdiplomas);
        foreach ($diplomas as $diploma) {
            $idxEqv[$diploma] = $eqv;
        }
    }
    return $idxEqv;
}

function high_level_categories() {
    return
        array(
            array(
                'name' => get_config('local_roftools', 'rof_year_name'),
                'idnumber' => get_config('local_roftools', 'rof_year_code')
                ),
            array(
                'name' =>  get_config('local_roftools', 'rof_etab_name'),
                'idnumber' => get_config('local_roftools', 'rof_etab_code'),
                ),
        );
}


function delete_rof_categories() {
    global $DB;
    
    $yearcode = get_config('local_roftools', 'rof_year_code');
    $etabcode = get_config('local_roftools', 'rof_etab_code');
    
    $catpath = $yearcode . '/' . $etabcode;
    $sql = "DELETE FROM {course_categories} "
        . " WHERE idnumber REGEXP '[234]:" . $catpath . ".*$'";
    $DB->execute($sql);
    $sql = "DELETE FROM {course_categories} "
        . " WHERE depth=1 AND idnumber = '1:". $yearcode ."'";
    $DB->execute($sql);
}

function list_rof_categories() {
    global $DB;

    $yearcode = get_config('local_roftools', 'rof_year_code');
    $etabcode = get_config('local_roftools', 'rof_etab_code');

    $catpath = $yearcode . '/' . $etabcode;
    $sql = "SELECT id, idnumber, name FROM {course_categories} "
        . " WHERE idnumber = '1:". $yearcode ."' OR idnumber REGEXP '[234]:" . $catpath . ".*$'";
    $rows = $DB->get_records_sql($sql);
    $count = 0;
    if (! $rows ) {
        echo "No rof categories for $catpath \n";
    } else {
        foreach ($rows as $row) {
            $count++;
            echo sprintf('%3d.  ',$count) . $row->id . "  [" . $row->idnumber . "]  " . $row->name . "\n";
        }
    }
}

function create_rof_categories($verb=0) {
    global $DB;

    $dipOrdre = array('Licences', 'Masters', 'Doctorats', 'Autres');
    $idxEqv = equivalent_diplomas();
    $hlCategories = high_level_categories();
    $parentid=0;

    // Crée les deux niveaux supérieurs
    $level = 0;
    $catpath = '';
    foreach ($hlCategories as $hlcat) {
        $level++;
        $newcategory = new stdClass();
        $newcategory->name = $hlcat['name'];
        $newcategory->idnumber = $level .':'. $catpath . $hlcat['idnumber'];
        $newcategory->parent = $parentid;
        $category = coursecat::create($newcategory);

        $parentid = $category->id;
        $catpath = $hlcat['idnumber'] . '/';
        fix_course_sortorder();
     }

    $rofRootId = $parentid;

    // Crée les niveaux issus du ROF : composantes (3) et types-diplômes simplifiés (4)
    $components = $DB->get_records('rof_component');
    foreach ($components as $component) {
        roftools_progressBar($verb, 0, "\n$component->number $component->name \n");
        $newcategory = new stdClass();
        $newcategory->name = $component->name;
        $newcategory->idnumber = '3:' . $hlCategories[0]['idnumber'] . '/' . $hlCategories[1]['idnumber'] . '/' . $component->number;
        $newcategory->parent = $rofRootId;
        $category = coursecat::create($newcategory);
        $compCatId = $category->id;
        fix_course_sortorder();
        list ($inSql, $inParams) = $DB->get_in_or_equal(explode(',', $component->sub));
        $sql = 'SELECT * FROM {rof_program} WHERE rofid ' . $inSql;
        $programs = $DB->get_records_sql($sql, $inParams);

        $diplomeCat = array();
        foreach ($programs as $program) {
            roftools_progressBar($verb, 1, '.');
            roftools_progressBar($verb, 2, " $program->rofid ");
            $typesimple = simplifyType($program->typedip, $idxEqv);
            $diplomeCat[$typesimple] = TRUE;
        } // $programs

        foreach ($dipOrdre as $classeDiplome) {
            if ( isset($diplomeCat[$classeDiplome]) ) {
                $newcategory = new stdClass();
                $newcategory->name = $classeDiplome;
                $newcategory->idnumber = '4:' . $hlCategories[0]['idnumber'] . '/' . $hlCategories[1]['idnumber'] . '/' . $component->number .'/'. $classeDiplome;
                $newcategory->parent = $compCatId;
                roftools_progressBar($verb, 1, " $classeDiplome");
                $category = coursecat::create($newcategory);
                // $progCatId = $category->id;
                fix_course_sortorder();
            }
        } // $dipOrdre
        roftools_progressBar($verb, 2, "\n");
    } // $components

}

/**
 * returns a simplified category for the diploma, ex. 'L2' -> 'Licences'
 * @param string $typedip
 * @return string
 */
function simplifyType($typedip, $idxEqv) {
    if (isset($idxEqv[$typedip])) {
        return $idxEqv[$typedip];
    } else {
        return 'Autres';
    }
}

/**
 * progress bar display
 * @param int $verb verbosity
 * @param int $verbmin minimal verbosity
 * @param string $strig to display
 */
function roftools_progressBar($verb, $verbmin, $string) {
    if ($verb >= $verbmin) {
        echo $string;
    }
}
