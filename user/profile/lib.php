<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

global $CFG;

require_once($CFG->libdir . '/custominfo/lib.php');

/***** General purpose functions for customisable user profiles *****/

function profile_load_data($user) {
    return custominfo_data::type('user')->load_data($user);
}

/**
 * Print out the customisable categories and fields for a users profile
 * @param  object   instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    $custominfo = new custominfo_form_extension('user', $userid);
    $canviewall = has_capability('moodle/user:update', context_system::instance());
    $custominfo->definition($mform, $canviewall);
}

function profile_definition_after_data($mform, $userid) {
    $custominfo = new custominfo_form_extension('user', $userid);
    $custominfo->definition_after_data($mform);
}

function profile_validation($usernew, $files) {
    global $DB;

    $err = array();
    $fields = $DB->get_records('custom_info_field', array('objectname' => 'user'));
    if ($fields) {
        foreach ($fields as $field) {
            $formfield = custominfo_field_factory('user', $field->datatype, $field->id, $usernew->id);
            $err += $formfield->edit_validate_field($usernew, $files);
        }
    }
    return $err;
}

function profile_save_data($usernew) {
    return custominfo_data::type('user')->save_data($usernew);
}

/**
 * Display profile fields.
 * @param int $userid
 */
function profile_display_fields($userid) {
    return custominfo_data::type('user')->display_fields($userid);
}

/**
 * Adds code snippet to a moodle form object for custom profile fields that
 * should appear on the signup page
 * @param moodleform $mform moodle form object
 */
function profile_signup_fields($mform) {
    global $CFG, $DB;

    // Only retrieve required custom fields (with category information)
    // results are sort by categories, then by fields.
    $sql = "SELECT f.id as fieldid, c.id as categoryid, c.name as categoryname, f.datatype
                FROM {custom_info_field} f
                JOIN {custom_info_category} c
                ON f.categoryid = c.id and c.objectname = 'user' AND f.signup = 1 AND uf.visible <> 0
                ORDER BY c.sortorder ASC, f.sortorder ASC";
    $fields = $DB->get_records_sql($sql);
    if ($fields) {
        $currentcat = null;
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            $formfield = custominfo_field_factory("user", $field->datatype, $field->fieldid);
            $formfield->edit_field($mform);
        }
    }
}

/**
 * Returns an object with the custom profile fields set for the given user
 * @param integer $userid
 * @param bool $onlyinuserobject True if you only want the ones in $USER.
 * @return stdClass
 */
function profile_user_record($userid, $onlyinuserobject = true) {
    $record = custominfo_data::type('user')->get_record($userid, $onlyinuserobject);
}

/**
 * Obtains a list of all available custom profile fields, indexed by id.
 *
 * Some profile fields are not included in the user object data (see
 * profile_user_record function above). Optionally, you can obtain only those
 * fields that are included in the user object.
 *
 * To be clear, this function returns the available fields, and does not
 * return the field values for a particular user.
 *
 * @param bool $onlyinuserobject True if you only want the ones in $USER
 * @return array Array of field objects from database (indexed by id)
 * @since Moodle 2.7.1
 */
function profile_get_custom_fields($onlyinuserobject = false) {
    global $DB, $CFG;

    // Get all the fields.
    $fields = $DB->get_records('custom_info_field', array('objectname' => 'user'), 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            require_once($CFG->dirroot . '/lib/custominfo/field/' .
                    $field->datatype . '/field.class.php');
            $newfield = 'profile_field_' . $field->datatype;
            $formfield = new $newfield('user');
            if (!$formfield->is_object_data()) {
                unset($fields[$id]);
            }
        }
    }

    return $fields;
}

/**
 * Load custom profile fields into user object
 *
 * Please note originally in 1.9 we were using the custom field names directly,
 * but it was causing unexpected collisions when adding new fields to user table,
 * so instead we now use 'profile_' prefix.
 *
 * @param stdClass $user user object
 */
function profile_load_custom_fields($user) {
    $user->profile = (array)custominfo_data::type('user')->get_record($user->id);
}

/**
 * Trigger a user profile viewed event.
 *
 * @param stdClass  $user user  object
 * @param stdClass  $context  context object (course or user)
 * @param stdClass  $course course  object
 * @since Moodle 2.9
 */
function profile_view($user, $context, $course = null) {

    $eventdata = array(
        'objectid' => $user->id,
        'relateduserid' => $user->id,
        'context' => $context
    );

    if (!empty($course)) {
        $eventdata['courseid'] = $course->id;
        $eventdata['other'] = array(
            'courseid' => $course->id,
            'courseshortname' => $course->shortname,
            'coursefullname' => $course->fullname
        );
    }

    $event = \core\event\user_profile_viewed::create($eventdata);
    $event->add_record_snapshot('user', $user);
    $event->trigger();
}

/**
 * Does the user have all required custom fields set?
 *
 * Internal, to be exclusively used by {@link user_not_fully_set_up()} only.
 *
 * Note that if users have no way to fill a required field via editing their
 * profiles (e.g. the field is not visible or it is locked), we still return true.
 * So this is actually checking if we should redirect the user to edit their
 * profile, rather than whether there is a value in the database.
 *
 * @param int $userid
 * @return bool
 */
function profile_has_required_custom_fields_set($userid) {
    global $DB;

    $sql = "SELECT f.id
              FROM {custom_info_field} f
         LEFT JOIN {custom_info_data} d ON (d.fieldid = f.id AND d.objectid = ?)
             WHERE f.required = 1 AND f.visible > 0 AND f.locked = 0 AND d.id IS NULL AND f.objectname = 'user'";

    if ($DB->record_exists_sql($sql, [$userid])) {
        return false;
    }

    return true;
}
