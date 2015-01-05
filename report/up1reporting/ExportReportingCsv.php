<?php

/**
 * Administrator reporting
 *
 * @package    report
 * @subpackage up1reporting
 * @copyright  2013-2015 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/up1_courselist/courselist_tools.php');
require_once($CFG->dirroot . '/local/coursehybridtree/libcrawler.php');

class ExportReportingCsv {
    public $rootnode; // ex. '/cat10/cat11/cat12' or simply '/cat12' // must match ChtNode::absolutePath()
    public $maxdepth; // 3 (UFR) to 6 (semestre), optional
    private $reportingTimestamp; // target unix timestamp
    private $csvFileHandle;

    
    public function __construct($rootnode, $maxdepth=6) {
        global $DB;
        $this->rootnode = $rootnode;
        $this->maxdepth = $maxdepth;
        $this->reportingTimestamp = $DB->get_field_sql('SELECT MAX(timecreated) FROM {report_up1reporting} ');
    }

    /**
     * This is the main public method
     */
    public function reportcsvcrawler() {
        $tree = CourseHybridTree::createTree($this->rootnode);

        $this->csvFileHandle = fopen("php://output", "w");
        $this->csvheader();
        internalcrawler($tree, $this->maxdepth, array($this, 'crawl_csvrow'), array());
        fclose($this->csvFileHandle);
        return true;
    }

    function csvheader() {
        global $CFG;
        $metadata = array(
            'Créé le ', date(DATE_ISO8601),
            'Noeud ' . $this->rootnode, '', '', '',
            $CFG->wwwroot
        );
        fputcsv($this->csvFileHandle, $metadata, ';'); // metadata
        $row = array_merge(array_values($this->csvheaderleft()), array_values($this->csvheaderreport()));
        fputcsv($this->csvFileHandle, $row, ';'); // first row

    }
    /**
     * this is the callback method for reportcsvcrawler()
     * @param string $node // must match ChtNode::absolutePath()
     */
    function crawl_csvrow($node) {
        global $DB;
        $nodepath = $node->getAbsolutePath();

        $row = array($node->getAbsoluteDepth(), $node->name, $nodepath);
        $criteria = $DB->get_records_menu(
            'report_up1reporting',
            array('timecreated' => $this->reportingTimestamp, 'object' => 'node', 'objectid' => $nodepath),
            '', 'name, value'
        );

        foreach ($this->csvheaderreport() as $crit => $critnamefr) {
            if (isset($criteria[$crit])) {
                $row[$crit] = $criteria[$crit];
            } else {
                $row[$crit] = '';
            }
        }
        fputcsv($this->csvFileHandle, array_values($row), ';');
    }

    /**
     * defines the first (leftish) columns for the export
     * @return array
     */
    function csvheaderleft() {
        return array(
            'level' => 'Niveau',
            'title' => 'Libellé',
            'chtpath' => 'Chemin Cht'
        );
    }

    /**
     * defines the content columns for the export.
     * The keys must match the content of the table report_up1reporting, otherwise -> empty columns
     * @return array
     */
    function csvheaderreport() {
        return array(
            'coursenumber:all' => 'Cours',
            'coursenumber:visible' => 'Cours ouverts',
            'coursenumber:active' => 'Courts actifs',
            'enrolled:editingteacher:all' => 'Enseignants',
            'enrolled:editingteacher:neverconnected' => 'Enseignants jamais connectés',
            'enrolled:teacher:all' => 'Enseignants non-éd',
            'enrolled:teacher:neverconnected' => 'Enseignants non-éd jamais connectés',
            'enrolled:student:all' => 'Etudiants',
            'enrolled:student:neverconnected' => 'Etudiants jamais connectés',
            'enrolled:total:all' => 'Tous inscrits',
            'enrolled:total:neverconnected' => 'Tous jamais connectés',
            'module:instances' => 'Activités gén.',
            'module:views' => 'Activités vues',
            'file:instances' => 'Fichiers',
            'file:views' => 'Fichiers vus',
            'forum:instances' => 'Forums',
            'forum:views' => 'Fichiers vus',
            'forum:posts' => 'Messages',
            'assign:instances' => 'Devoirs',
            'assign:views' => 'Devoirs vus',
            'assign:posts' => 'Rendus',
        );
    }

}