<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Delete allocation request.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_approval_delete extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $request = $this->_customdata['request'];
        $program = $this->_customdata['program'];
        $user = $this->_customdata['user'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $request->id);

        $this->add_action_buttons(true, get_string('source_approval_requestdelete', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
