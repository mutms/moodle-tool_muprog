<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_muprog\local\program;
use tool_muprog\local\allocation;

/**
 * Edit program allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocations_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('date_time_selector', 'timeallocationstart', get_string('allocationstart', 'tool_muprog'), ['optional' => true]);
        $mform->addHelpButton('timeallocationstart', 'allocationstart', 'tool_muprog');

        $mform->addElement('date_time_selector', 'timeallocationend', get_string('allocationend', 'tool_muprog'), ['optional' => true]);
        $mform->addHelpButton('timeallocationend', 'allocationend', 'tool_muprog');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $data->id);

        $this->add_action_buttons(true, get_string('updateallocations', 'tool_muprog'));

        $this->set_data($data);
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if ($data['timeallocationstart'] && $data['timeallocationend']
            && $data['timeallocationstart'] >= $data['timeallocationend']) {
            $errors['timeallocationend'] = get_string('error');
        }

        return $errors;
    }
}
