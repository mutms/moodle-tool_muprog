<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

use tool_muprog\local\content\training;

/**
 * Edit program training item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_training_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var training $training */
        $training = $this->_customdata['training'];

        $mform->addElement('static', 'staticfullname', get_string('fullname'), format_string($training->get_fullname()));

        $mform->addElement('text', 'points', get_string('itempoints', 'tool_muprog'));
        $mform->setType('points', PARAM_INT);
        $mform->setDefault('points', $training->get_points());

        $mform->addElement('duration', 'completiondelay', get_string('completiondelay', 'tool_muprog'),
            ['optional' => true, 'defaultunit' => DAYSECS]);
        $mform->setDefault('completiondelay', $training->get_completiondelay());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $training->get_id());

        $this->add_action_buttons(true, get_string('updatetraining', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        return $errors;
    }
}
