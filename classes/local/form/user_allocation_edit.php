<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

/**
 * Edit user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_allocation_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $allocation = $this->_customdata['allocation'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $mform->addElement('date_time_selector', 'timeallocated', get_string('allocationdate', 'tool_muprog'), ['optional' => false]);
        $mform->freeze('timeallocated');

        $mform->addElement('date_time_selector', 'timestart', get_string('programstart_date', 'tool_muprog'), ['optional' => false]);

        $mform->addElement('date_time_selector', 'timedue', get_string('programdue_date', 'tool_muprog'), ['optional' => true]);

        $mform->addElement('date_time_selector', 'timeend', get_string('programend_date', 'tool_muprog'), ['optional' => true]);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        $this->add_action_buttons(true, get_string('updateallocation', 'tool_muprog'));

        $this->set_data($allocation);
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $errors = array_merge($errors, \tool_muprog\local\allocation::validate_allocation_dates(
            $data['timestart'], $data['timedue'], $data['timeend']));

        return $errors;
    }
}
