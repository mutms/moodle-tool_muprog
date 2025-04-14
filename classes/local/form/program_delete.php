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
 * Delete program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_delete extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];

        $mform->addElement('static', 'fullname', get_string('programname', 'tool_muprog'), format_string($program->fullname));

        $mform->addElement('static', 'idnumber', get_string('idnumber'), format_string($program->idnumber));

        $mform->addElement('select', 'archived', get_string('archived', 'tool_muprog'),
            [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->freeze('archived');
        $mform->setDefault('archived', $program->archived);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $program->id);

        $this->add_action_buttons(true, get_string('program_delete', 'tool_muprog'));
    }
}
