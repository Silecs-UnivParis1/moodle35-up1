<?php

/**
 * @package    local
 * @subpackage coursehybridtree
 * @copyright  2014 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once __DIR__ . '/../../config.php';
require_once($CFG->dirroot . '/local/coursehybridtree/CourseHybridTree.php');
require_once($CFG->dirroot . '/local/coursehybridtree/ChtNode.php');
require_once($CFG->dirroot . '/local/coursehybridtree/ChtNodeCategory.php');
require_once($CFG->dirroot . '/local/coursehybridtree/ChtNodeRof.php');
require_once($CFG->dirroot . '/local/coursehybridtree/ChtNodeCourse.php');

function hybridcrawler($maxdepth = 0) {
    $tree = CourseHybridTree::createTree('/cat0');

    internalcrawler($tree, $maxdepth, 'printnode');
}

function internalcrawler($node, $maxdepth, $callbackfn, $extraparams=array()) {
    $total = 0;

    call_user_func($callbackfn, $node, $extraparams);
    $children = $node->listChildren();
    if ( count($children) == 0 ) {
        return 1;
    }
    if ( $maxdepth == 0  ||  $node->getAbsoluteDepth() < $maxdepth ) {        
        foreach ($children as $child) {
            $total += internalcrawler($child, $maxdepth, $callbackfn, $extraparams);
        }
    // echo $total . "\n";
    }
    return $total;
}

function printnode($node) {
    echo $node->getAbsoluteDepth() . "  " . $node->getAbsolutePath() . "  "  ;
    $descendantcourses = $node->listDescendantCourses();
    echo "    " . count($descendantcourses);
    echo "  " . join (' ', $descendantcourses);
    echo "\n";
}