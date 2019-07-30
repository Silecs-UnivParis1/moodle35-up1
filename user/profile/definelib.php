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

/**
 * This file contains the profile_define_base class.
 *
 * @package core_user
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Class profile_define_base
 *
 * @copyright  2007 onwards Shane Elliot {@link http://pukunui.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . '/custominfo/lib.php');

/**
 * Reorder the profile fields within a given category starting
 * at the field at the given startorder
 */
function profile_reorder_fields() {
    return custominfo_field::type('user')->reorder();
}

/**
 * Reorder the profile categoriess starting at the category
 * at the given startorder
 */
function profile_reorder_categories() {
    return custominfo_category::type('user')->reorder();
}

/**
 * Delete a profile category
 * @param   integer   $id id of the category to be deleted
 * @return  boolean   success of operation
 */
function profile_delete_category($id) {
    return custominfo_category::findById($id)->delete();
}

    // Retrieve the category.
    if (!$category = $DB->get_record('user_info_category', array('id' => $id))) {
        print_error('invalidcategoryid');
    }

    if (!$categories = $DB->get_records('user_info_category', null, 'sortorder ASC')) {
        print_error('nocate', 'debug');
    }

    unset($categories[$category->id]);

    if (!count($categories)) {
        return false; // We can not delete the last category.
    }

    // Does the category contain any fields.
    if ($DB->count_records('user_info_field', array('categoryid' => $category->id))) {
        if (array_key_exists($category->sortorder - 1, $categories)) {
            $newcategory = $categories[$category->sortorder - 1];
        } else if (array_key_exists($category->sortorder + 1, $categories)) {
            $newcategory = $categories[$category->sortorder + 1];
        } else {
            $newcategory = reset($categories); // Get first category if sortorder broken.
        }

        $sortorder = $DB->count_records('user_info_field', array('categoryid' => $newcategory->id)) + 1;

        if ($fields = $DB->get_records('user_info_field', array('categoryid' => $category->id), 'sortorder ASC')) {
            foreach ($fields as $field) {
                $f = new stdClass();
                $f->id = $field->id;
                $f->sortorder = $sortorder++;
                $f->categoryid = $newcategory->id;
                if ($DB->update_record('user_info_field', $f)) {
                    $field->sortorder = $f->sortorder;
                    $field->categoryid = $f->categoryid;
                    \core\event\user_info_field_updated::create_from_field($field)->trigger();
                }
            }
        }
    }

    // Finally we get to delete the category.
    $DB->delete_records('user_info_category', array('id' => $category->id));
    profile_reorder_categories();

    \core\event\user_info_category_deleted::create_from_category($category)->trigger();

    return true;
}

/**
 * Deletes a profile field.
 * @param int $id
 */
function profile_delete_field($id) {
    return custominfo_field::findById($id)->delete();
}

/**
 * Change the sortorder of a field
 * @param   integer   id of the field
 * @param   string    direction of move
 * @return  boolean   success of operation
 */
function profile_move_field($id, $move) {
    return custominfo_field::findById($id)->move($move);
}

/**
 * Change the sortorder of a category
 * @param   integer   id of the category
 * @param   string    direction of move
 * @return  boolean   success of operation
 */
function profile_move_category($id, $move) {
    return custominfo_category::findById($id)->move($move);
}

/**
 * Retrieve a list of all the available data types
 * @return   array   a list of the datatypes suitable to use in a select statement
 */
function profile_list_datatypes() {
    return custominfo_field::list_datatypes();
}

/**
 * Retrieve a list of categories and ids suitable for use in a form
 * @return   array
 */
function profile_list_categories() {
    return custominfo_category::type('user')->list_assoc();
}


/**
 * Edit a category
 *
 * @param int $id
 * @param string $redirect
 */
function profile_edit_category($id, $redirect) {
    global $DB, $OUTPUT, $CFG;

    require_once($CFG->dirroot.'/user/profile/index_category_form.php');
    $categoryform = new category_form();

    if ($category = $DB->get_record('user_info_category', array('id' => $id))) {
        $categoryform->set_data($category);
    }

    if ($categoryform->is_cancelled()) {
        redirect($redirect);
    } else {
        if ($data = $categoryform->get_data()) {
            if (empty($data->id)) {
                unset($data->id);
                $data->sortorder = $DB->count_records('user_info_category') + 1;
                $data->id = $DB->insert_record('user_info_category', $data, true);

                $createdcategory = $DB->get_record('user_info_category', array('id' => $data->id));
                \core\event\user_info_category_created::create_from_category($createdcategory)->trigger();
            } else {
                $DB->update_record('user_info_category', $data);

                $updatedcateogry = $DB->get_record('user_info_category', array('id' => $data->id));
                \core\event\user_info_category_updated::create_from_category($updatedcateogry)->trigger();
            }
            profile_reorder_categories();
            redirect($redirect);

        }

        if (empty($id)) {
            $strheading = get_string('profilecreatenewcategory', 'admin');
        } else {
            $strheading = get_string('profileeditcategory', 'admin', format_string($category->name));
        }

        // Print the page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $categoryform->display();
        echo $OUTPUT->footer();
        die;
    }

}

/**
 * Edit a profile field.
 *
 * @param int $id
 * @param string $datatype
 * @param string $redirect
 */
function profile_edit_field($id, $datatype, $redirect) {
    global $CFG, $DB, $OUTPUT, $PAGE;

    if (!$field = $DB->get_record('user_info_field', array('id' => $id))) {
        $field = new stdClass();
        $field->datatype = $datatype;
        $field->description = '';
        $field->descriptionformat = FORMAT_HTML;
        $field->defaultdata = '';
        $field->defaultdataformat = FORMAT_HTML;
    }

    // Clean and prepare description for the editor.
    $field->description = clean_text($field->description, $field->descriptionformat);
    $field->description = array('text' => $field->description, 'format' => $field->descriptionformat, 'itemid' => 0);

    require_once($CFG->dirroot.'/user/profile/index_field_form.php');
    $fieldform = new field_form(null, $field->datatype);

    // Convert the data format for.
    if (is_array($fieldform->editors())) {
        foreach ($fieldform->editors() as $editor) {
            if (isset($field->$editor)) {
                $field->$editor = clean_text($field->$editor, $field->{$editor.'format'});
                $field->$editor = array('text' => $field->$editor, 'format' => $field->{$editor.'format'}, 'itemid' => 0);
            }
        }
    }

    $fieldform->set_data($field);

    if ($fieldform->is_cancelled()) {
        redirect($redirect);

    } else {
        if ($data = $fieldform->get_data()) {
            require_once($CFG->dirroot.'/user/profile/field/'.$datatype.'/define.class.php');
            $newfield = 'profile_define_'.$datatype;
            $formfield = new $newfield();

            // Collect the description and format back into the proper data structure from the editor.
            // Note: This field will ALWAYS be an editor.
            $data->descriptionformat = $data->description['format'];
            $data->description = $data->description['text'];

            // Check whether the default data is an editor, this is (currently) only the textarea field type.
            if (is_array($data->defaultdata) && array_key_exists('text', $data->defaultdata)) {
                // Collect the default data and format back into the proper data structure from the editor.
                $data->defaultdataformat = $data->defaultdata['format'];
                $data->defaultdata = $data->defaultdata['text'];
            }

            // Convert the data format for.
            if (is_array($fieldform->editors())) {
                foreach ($fieldform->editors() as $editor) {
                    if (isset($field->$editor)) {
                        $field->{$editor.'format'} = $field->{$editor}['format'];
                        $field->$editor = $field->{$editor}['text'];
                    }
                }
            }

            $formfield->define_save($data);
            profile_reorder_fields();
            profile_reorder_categories();
            redirect($redirect);
        }

        $datatypes = profile_list_datatypes();

        if (empty($id)) {
            $strheading = get_string('profilecreatenewfield', 'admin', $datatypes[$datatype]);
        } else {
            $strheading = get_string('profileeditfield', 'admin', format_string($field->name));
        }

        // Print the page.
        $PAGE->navbar->add($strheading);
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strheading);
        $fieldform->display();
        echo $OUTPUT->footer();
        die;
    }
}


