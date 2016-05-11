<?php
/**
 * @package    local
 * @subpackage up1_metadata
 * @copyright  2012-2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(__DIR__ . '/insertlib.php');

function add_urlfixe() {
    $data = array(
        'Cycle de vie - Informations techniques' => array(
            'urlfixe' => array('name' => 'Url fixe', 'datatype' => 'text', 'locked' => 0, 'init' => null)
            )
        );
    if (validate_metadata($data)) {
        insert_metadata_fields($data, 'course');
    } else {
        die('Métadonnées invalides');
    }
}

/**
 * Updates the newly created categoriesbisrof metadata when this field has just been created
 */
function upgrade_categoriesbisrof() {
    global $DB;

    $dataid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1rofpathid'), MUST_EXIST);
    $rofpathids = $DB->get_records('custom_info_data', array('fieldid' => $dataid));

    $catbisrofid = $DB->get_field('custom_info_field', 'id', array('shortname' => 'up1categoriesbisrof'), MUST_EXIST);

    foreach ($rofpathids as $rofpathid) {
        // echo $rofpathid->objectid ." => ". $rofpathid->data ."\n" ;
        $rofpaths = explode(';', $rofpathid->data);
        $categoriesbisrof = array();
        if (count($rofpaths) >= 2) { //rattachements ROF secondaires
            echo "course " . $rofpathid->objectid ." => ";
            // echo $rofpathid->data ."\n    " ;
            foreach (array_slice($rofpaths, 1) as $rofpath) {
                $myrofpath = array_values(array_filter(explode('/', $rofpath)));
                $mycat = rof_rofpath_to_category($myrofpath);
                $categoriesbisrof[] = $mycat;
                // echo "cat=" . $mycat . "  ";
            }
            $data = join(';', $categoriesbisrof);
            echo "up1categoriesbisrof = $data <br />\n";
            $record = $DB->get_record('custom_info_data',
                    array('fieldid' => $catbisrofid, 'objectid' => $rofpathid->objectid, 'objectname' => 'course'));
            $record->data = $data;
            $DB->update_record('custom_info_data', $record);
        }
    }
}