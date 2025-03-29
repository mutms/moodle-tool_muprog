<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Delete program certificate settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_certificate_delete extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $record = $DB->get_record('tool_certificate_templates', ['id' => $data->templateid]);
        if ($record) {
            $template = format_string($record->name);
            $mform->addElement('static', 'template', get_string('certificatetemplate', 'tool_certificate'), $template);
        }

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons(true, get_string('delete'));
    }
}
