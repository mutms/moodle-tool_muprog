<?php
// This file is part of Programs for Moodle™.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Add program content items.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_content_import extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $arguments = ['programid' => $customdata['id']];
        \tool_muprog\external\form_program_content_import_fromprogram::add_form_element(
            $mform, $arguments, 'fromprogram', get_string('importselectprogram', 'tool_muprog'));
        $mform->addRule('fromprogram', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $customdata['id']);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Check if the user has capability to copy the selected program.
        $programid = $data['fromprogram'];
        $programcontextid = $DB->get_field('tool_muprog_program', 'contextid', ['id' => $programid]);
        $context = \context::instance_by_id($programcontextid);
        if (!has_capability('tool/muprog:clone', $context )) {
            $errors['fromprogram'] = get_string('error');
        }
        return $errors;
    }
}
