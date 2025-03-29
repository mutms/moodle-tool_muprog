<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Import program allocation - program selection step.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocation_import extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $arguments = ['programid' => $customdata['id']];
        \tool_muprog\external\form_program_allocation_import_fromprogram::add_form_element(
            $mform, $arguments, 'fromprogram', get_string('importselectprogram', 'tool_muprog'));
        $mform->addRule('fromprogram', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $customdata['id']);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);

        // Check if the user has capability to copy the selected program.
        $programid = $data['fromprogram'];
        $programcontextid = $DB->get_field('tool_muprog_program', 'contextid', ['id' => $programid]);
        $context = \context::instance_by_id($programcontextid);
        if (!has_capability('tool/muprog:clone', $context )) {
            $errors['fromprogram'] = get_string('error');
        }
        return $errors;
    }
}
