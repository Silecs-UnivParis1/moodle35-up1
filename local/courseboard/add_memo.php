<?php

/**
 * @package    local
 * @subpackage courseboard
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require('locallib.php');
global $USER;
require_login();

$memo = $_POST['memo'];
$crsid = $_POST['crsid'];
add_memo($crsid, addslashes($memo));

$url = new moodle_url('/local/courseboard/view.php', array('id' => $crsid));
$url->set_anchor('course-log');
redirect($url);


