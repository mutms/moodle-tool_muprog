<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

/**
 * Edit program completion.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_completion_override extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {

        $mform = $this->_form;
        $allocation = $this->_customdata['allocation'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $mform->addElement('date_time_selector', 'timecompleted', get_string('programcompletion', 'tool_muprog'), ['optional' => true]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        $this->add_action_buttons(true, get_string('update'));

        $this->set_data($allocation);
    }
}
