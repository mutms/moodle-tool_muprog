<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Request program allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_approval_request extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $confirmation = markdown_to_html(get_string('source_approval_confirm', 'tool_muprog'));
        $mform->addElement('static', 'confirmation', '', clean_text($confirmation));

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $this->add_action_buttons(true, get_string('source_approval_makerequest', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
