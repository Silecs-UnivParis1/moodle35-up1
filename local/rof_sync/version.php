<?php
// This file is part of a plugin for Moodle - http://moodle.org/

/**
 * @package    local
 * @subpackage rof_sync
 * @copyright  2012-2013 Silecs {@link http://www.silecs.info/societe}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2013052903;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2012061700;        // Requires this Moodle version
$plugin->component = 'local_rof_sync';       // Full name of the plugin (used for diagnostics)

/** 
   2019-04-02  Silecs Guillaume Allègre

  Ce fichier sert à maintenir le plugin local/rof_sync minimaliste
  tant que les tables rof_* lui sont liées
  par le mécanisme de déclaration de table dans Moodle.

  Si ce plugin est désinstallé normalement dans Moodle,
  les tables rof_* seront supprimées. A EVITER.
**/
