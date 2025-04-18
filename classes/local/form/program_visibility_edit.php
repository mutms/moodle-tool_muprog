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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_muprog\local\management;
use tool_muprog\external\form_program_visibility_edit_cohortids;

/**
 * Edit program visibility.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_visibility_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('select', 'public', get_string('public', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->setDefault('public', $data->public);
        $mform->addHelpButton('public', 'public', 'tool_muprog');

        form_program_visibility_edit_cohortids::add_form_element(
            $mform, ['programid' => $data->id], 'cohortids', get_string('cohorts', 'tool_muprog'));
        $cohorts = management::fetch_current_cohorts_menu($data->id);
        $mform->setDefault('cohortids', array_keys($cohorts));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $data->id);

        $this->add_action_buttons(true, get_string('program_update', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        foreach ($data['cohortids'] as $cohortid) {
            $error = form_program_visibility_edit_cohortids::validate_cohortid($cohortid, $data['id']);
            if ($error !== null) {
                $errors['cohorts'] = $error;
                break;
            }
        }

        return $errors;
    }
}
