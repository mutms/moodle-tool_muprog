<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_muprog\local\content\course;

/**
 * Edit program course item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_course_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        /** @var course $course */
        $course = $this->_customdata['course'];

        $mform->addElement('static', 'staticfullname', get_string('fullname'), format_string($course->get_fullname()));

        $mform->addElement('text', 'points', get_string('itempoints', 'tool_muprog'));
        $mform->setType('points', PARAM_INT);
        $mform->setDefault('points', $course->get_points());

        $mform->addElement('duration', 'completiondelay', get_string('completiondelay', 'tool_muprog'),
            ['optional' => true, 'defaultunit' => DAYSECS]);
        $mform->setDefault('completiondelay', $course->get_completiondelay());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $course->get_id());

        $this->add_action_buttons(true, get_string('updatecourse', 'tool_muprog'));
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
