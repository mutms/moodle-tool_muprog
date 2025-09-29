<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
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

use tool_muprog\local\source\externaldb;

/**
 * External database allocation source editor.
 *
 * @package    tool_muprog
 * @copyright  2025 e-Learning Team, Universiti Malaysia Terengganu
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_externaldb_edit extends \tool_mulib\local\ajax_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $mform->addElement('select', 'enable', get_string('active'), ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('enable', $source->enable);
        if (!empty($source->hasallocations)) {
            $mform->hardFreeze('enable');
        }

        $mform->addElement('text', 'remotetable', get_string('source_externaldb_remotetable', 'tool_muprog'));
        $mform->setType('remotetable', PARAM_RAW_TRIMMED);
        $mform->setDefault('remotetable', $source->remotetable);
        $mform->hideIf('remotetable', 'enable', 'eq', 0);

        $mform->addElement('text', 'programfield', get_string('source_externaldb_programfield', 'tool_muprog'));
        $mform->setType('programfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('programfield', $source->programfield);
        $mform->hideIf('programfield', 'enable', 'eq', 0);

        $mform->addElement('text', 'programvalue', get_string('source_externaldb_programvalue', 'tool_muprog'));
        $mform->setType('programvalue', PARAM_RAW_TRIMMED);
        $mform->setDefault('programvalue', $source->programvalue);
        $mform->addHelpButton('programvalue', 'source_externaldb_programvalue', 'tool_muprog');
        $mform->hideIf('programvalue', 'enable', 'eq', 0);

        $mform->addElement('text', 'userfield', get_string('source_externaldb_userfield', 'tool_muprog'));
        $mform->setType('userfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('userfield', $source->userfield);
        $mform->hideIf('userfield', 'enable', 'eq', 0);

        $mappingoptions = [
            'idnumber' => get_string('idnumber'),
            'username' => get_string('username'),
            'email' => get_string('email'),
        ];
        $mform->addElement('select', 'usermapping', get_string('source_externaldb_usermapping', 'tool_muprog'), $mappingoptions);
        $mform->setDefault('usermapping', $source->usermapping ?? 'idnumber');
        $mform->hideIf('usermapping', 'enable', 'eq', 0);

        $mform->addElement('text', 'timeallocatedfield', get_string('source_externaldb_timeallocatedfield', 'tool_muprog'));
        $mform->setType('timeallocatedfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('timeallocatedfield', $source->timeallocatedfield);
        $mform->hideIf('timeallocatedfield', 'enable', 'eq', 0);

        $mform->addElement('text', 'timestartfield', get_string('source_externaldb_timestartfield', 'tool_muprog'));
        $mform->setType('timestartfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('timestartfield', $source->timestartfield);
        $mform->hideIf('timestartfield', 'enable', 'eq', 0);

        $mform->addElement('text', 'timeduefield', get_string('source_externaldb_timeduefield', 'tool_muprog'));
        $mform->setType('timeduefield', PARAM_RAW_TRIMMED);
        $mform->setDefault('timeduefield', $source->timeduefield);
        $mform->hideIf('timeduefield', 'enable', 'eq', 0);

        $mform->addElement('text', 'timeendfield', get_string('source_externaldb_timeendfield', 'tool_muprog'));
        $mform->setType('timeendfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('timeendfield', $source->timeendfield);
        $mform->hideIf('timeendfield', 'enable', 'eq', 0);

        $mform->addElement('text', 'sourceinstancefield', get_string('source_externaldb_sourceinstancefield', 'tool_muprog'));
        $mform->setType('sourceinstancefield', PARAM_RAW_TRIMMED);
        $mform->setDefault('sourceinstancefield', $source->sourceinstancefield);
        $mform->hideIf('sourceinstancefield', 'enable', 'eq', 0);

        $mform->addElement('text', 'statusfield', get_string('source_externaldb_statusfield', 'tool_muprog'));
        $mform->setType('statusfield', PARAM_RAW_TRIMMED);
        $mform->setDefault('statusfield', $source->statusfield);
        $mform->hideIf('statusfield', 'enable', 'eq', 0);

        $mform->addElement('text', 'statusvalue', get_string('source_externaldb_statusvalue', 'tool_muprog'));
        $mform->setType('statusvalue', PARAM_RAW_TRIMMED);
        $mform->setDefault('statusvalue', $source->statusvalue);
        $mform->addHelpButton('statusvalue', 'source_externaldb_statusvalue', 'tool_muprog');
        $mform->hideIf('statusvalue', 'enable', 'eq', 0);

        $mform->addElement(
            'advcheckbox',
            'syncdeletions',
            get_string('source_externaldb_syncdeletions', 'tool_muprog'),
            get_string('source_externaldb_syncdeletions_desc', 'tool_muprog'),
            null,
            [0, 1]
        );
        $mform->setDefault('syncdeletions', $source->syncdeletions);
        $mform->hideIf('syncdeletions', 'enable', 'eq', 0);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $program->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);
        $mform->setDefault('type', $source->type);

        $this->add_action_buttons(true, get_string('update'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['enable'])) {
            if (!externaldb::is_configured()) {
                $errors['remotetable'] = get_string('source_externaldb_settingsmissing', 'tool_muprog');
            }
            foreach (['remotetable', 'programfield', 'userfield'] as $field) {
                if (empty($data[$field])) {
                    $errors[$field] = get_string('required');
                }
            }
            $allowedmapping = ['idnumber', 'username', 'email'];
            if (empty($data['usermapping']) || !in_array($data['usermapping'], $allowedmapping, true)) {
                $errors['usermapping'] = get_string('invaliduser', 'error');
            }
        }

        return $errors;
    }
}
