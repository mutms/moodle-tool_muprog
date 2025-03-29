<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

/**
 * Program self-allocation confirmation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_selfallocation extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $confirmation = markdown_to_html(get_string('source_selfallocation_confirm', 'tool_muprog'));
        $mform->addElement('static', 'confirmation', '', clean_text($confirmation));

        $data = (object)json_decode($source->datajson);
        if (isset($data->key)) {
            $mform->addElement('passwordunmask', 'key', get_string('source_selfallocation_key', 'tool_muprog'));
            $mform->addRule('key', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $this->add_action_buttons(true, get_string('source_selfallocation_allocate', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $source = $this->_customdata['source'];
        $sourcedata = (object)json_decode($source->datajson);
        if (isset($sourcedata->key)) {
            if (trim($data['key']) === '') {
                $errors['key'] = get_string('required');
            } else if ($data['key'] !== $sourcedata->key) {
                $errors['key'] = get_string('error');
            }
        }

        return $errors;
    }
}
