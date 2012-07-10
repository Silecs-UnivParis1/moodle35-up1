<?php
global $CFG;

require_once($CFG->libdir . '/custominfo/lib.php');

/**
 * Base class for the customisable profile fields.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class profile_field_base extends custominfo_field_base {

    protected $objectname = 'user';
    protected $capability = 'moodle/user:update';

    /**
     * for compatibility with PHP4 code in sub-classes
     */
    public function profile_field_base($fieldid=0, $objectid=0) {
        parent::__construct($fieldid, $objectid);
    }

    /**
     * Check if the field data is visible to the current user
     * @internal This method should not generally be overwritten by child classes.
     * @return bool
     */
    public function is_visible() {
        global $USER;

        switch ($this->field->visible) {
            case CUSTOMINFO_VISIBLE_ALL:
                return true;
            case CUSTOMINFO_VISIBLE_PRIVATE:
                if ($this->objectid == $USER->id) {
                    return true;
                } else {
                    return has_capability('moodle/user:viewalldetails',
                            context_user::instance($this->objectid));
                }
            case CUSTOMINFO_VISIBLE_NONE:
            default:
                return has_capability($this->capability, context_user::instance($this->objectid));
        }
    }
} /// End of class definition


/***** General purpose functions for customisable user profiles *****/

function profile_load_data($user) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('custom_info_field', array('objectname' => 'user'))) {
        foreach ($fields as $field) {
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $user->id);
            $formfield->edit_load_object_data($user);
        }
    }
}

/**
 * Print out the customisable categories and fields for a users profile
 *
 * @param moodleform $mform instance of the moodleform class
 * @param int $userid id of user whose profile is being edited.
 */
function profile_definition($mform, $userid = 0) {
    global $CFG, $DB;

    // If user is "admin" fields are displayed regardless.
    $update = has_capability('moodle/user:update', context_system::instance());

    if ($categories = $DB->get_records('custom_info_category',
            array('objectname' => 'user'), 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {

                // Check first if *any* fields will be displayed.
                $display = false;
                foreach ($fields as $field) {
                    if ($field->visible != CUSTOMINFO_VISIBLE_NONE) {
                        $display = true;
                    }
                }

                // Display the header and the fields.
                if ($display or $update) {
                    $mform->addElement('header', 'category_'.$category->id, format_string($category->name));
                    foreach ($fields as $field) {
                        require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
                        $newfield = 'profile_field_'.$field->datatype;
                        $formfield = new $newfield($field->id, $userid);
                        $formfield->edit_field($mform);
                    }
                }
            }
        }
    }
}

/**
 * Adds profile fields to user edit forms.
 * @param moodleform $mform
 * @param int $userid
 */
function profile_definition_after_data($mform, $userid) {
    global $CFG, $DB;

    $userid = ($userid < 0) ? 0 : (int)$userid;

    if ($fields = $DB->get_records('custom_info_field', array('objectname' => 'user'))) {
        foreach ($fields as $field) {
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            $formfield->edit_after_data($mform);
        }
    }
}

/**
 * Validates profile data.
 * @param stdClass $usernew
 * @param array $files
 * @return array
 */
function profile_validation($usernew, $files) {
    global $CFG, $DB;

    $err = array();
    if ($fields = $DB->get_records('custom_info_field', array('objectname' => 'user'))) {
        foreach ($fields as $field) {
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $err += $formfield->edit_validate_field($usernew, $files);
        }
    }
    return $err;
}

/**
 * Saves profile data for a user.
 * @param stdClass $usernew
 */
function profile_save_data($usernew) {
    global $CFG, $DB;

    if ($fields = $DB->get_records('custom_info_field', array('objectname' => 'user'))) {
        foreach ($fields as $field) {
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $usernew->id);
            $formfield->edit_save_data($usernew);
        }
    }
}

/**
 * Display profile fields.
 * @param int $userid
 */
function profile_display_fields($userid) {
    global $CFG, $USER, $DB;

    if ($categories = $DB->get_records('custom_info_category', array('objectname' => 'user'), 'sortorder ASC')) {
        foreach ($categories as $category) {
            if ($fields = $DB->get_records('custom_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
                foreach ($fields as $field) {
                    require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id, $userid);
                    if ($formfield->is_visible() and !$formfield->is_empty()) {
                        echo html_writer::tag('dt', format_string($formfield->field->name));
                        echo html_writer::tag('dd', $formfield->display_data());
                    }
                }
            }
        }
    }
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

    if ( $fields = $DB->get_records_sql($sql)) {
        foreach ($fields as $field) {
            // Check if we change the categories.
            if (!isset($currentcat) || $currentcat != $field->categoryid) {
                 $currentcat = $field->categoryid;
                 $mform->addElement('header', 'category_'.$field->categoryid, format_string($field->categoryname));
            }
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->fieldid);
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
    global $CFG, $DB;

    $usercustomfields = new stdClass();

    if ($fields = $DB->get_records('custom_info_field', array('objectname' => 'user'))) {
        foreach ($fields as $field) {
            require_once($CFG->libdir.'/custominfo/field/'.$field->datatype.'/field.class.php');
            $newfield = 'profile_field_'.$field->datatype;
            $formfield = new $newfield($field->id, $userid);
            if (!$onlyinuserobject || $formfield->is_object_data()) {
                $usercustomfields->{$field->shortname} = $formfield->data;
            }
        }
    }

    return $usercustomfields;
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
    $fields = $DB->get_records('user_info_field', null, 'id ASC');

    // If only doing the user object ones, unset the rest.
    if ($onlyinuserobject) {
        foreach ($fields as $id => $field) {
            require_once($CFG->dirroot . '/user/profile/field/' .
                    $field->datatype . '/field.class.php');
            $newfield = 'profile_field_' . $field->datatype;
            $formfield = new $newfield();
            if (!$formfield->is_user_object_data()) {
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
    $user->profile = (array)profile_user_record($user->id);
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

