<?php
/**
 * Version info
 *
 * @package    local
 * @subpackage redirection
 * @copyright  2016 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2016051100;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'local_redirection'; // Full name of the plugin (used for diagnostics)

$plugin->dependencies = array(
    'local_up1_metadata' => 2016051101,
);
