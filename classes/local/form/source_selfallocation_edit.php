<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_muprog\local\program;
use tool_muprog\local\allocation;

/**
 * Edit program self allocation settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_selfallocation_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $mform->addElement('select', 'enable', get_string('active'), ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('enable', $source->enable);
        if ($source->hasallocations) {
            $mform->hardFreeze('enable');
        }

        $mform->addElement('select', 'selfallocation_allowsignup', get_string('source_selfallocation_allowsignup', 'tool_muprog'),
            ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('selfallocation_allowsignup', 1);
        $mform->hideIf('selfallocation_allowsignup', 'enable', 'eq', '0');

        $mform->addElement('passwordunmask', 'selfallocation_key', get_string('source_selfallocation_key', 'tool_muprog'));
        $mform->setDefault('selfallocation_key', $source->selfallocation_key);
        $mform->hideIf('selfallocation_key', 'enable', 'eq', '0');

        $mform->addElement('text', 'selfallocation_maxusers', get_string('source_selfallocation_maxusers', 'tool_muprog'), 'size="8"');
        $mform->setType('selfallocation_maxusers', PARAM_RAW);
        $mform->setDefault('selfallocation_maxusers', $source->selfallocation_maxusers);
        $mform->hideIf('selfallocation_maxusers', 'enable', 'eq', '0');

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

        if ($data['selfallocation_maxusers'] !== '') {
            if (!is_number($data['selfallocation_maxusers'])) {
                $errors['selfallocation_maxusers'] = get_string('error');
            } else if ($data['selfallocation_maxusers'] < 0) {
                $errors['selfallocation_maxusers'] = get_string('error');
            }
        }

        return $errors;
    }
}
