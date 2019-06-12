<?php
/* 
 * @license http://www.gnu.org/licenses/gpl-3.0.html  GNU GPL v3
 */

/* @var $ADMIN admin_root */

defined('MOODLE_INTERNAL') || die;

$setting = new admin_setting_configtext(
    'block_course_opennow/faqurl',
    'URL de la FAQ',
    "URL du lien vers la FAQ affiché à l'enseignant",
    ''
);
$settings->add($setting);


