<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace enrol_programs\local\form;

use enrol_programs\local\allocation;
use enrol_programs\local\course_reset;

/**
 * Reset user allocation.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_allocation_reset extends \local_openlms\dialog_form {
    /** @var bool */
    private $editsupported;

    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $allocation = $this->_customdata['allocation'];
        $user = $this->_customdata['user'];
        $context = $this->_customdata['context'];

        $mform->addElement('static', 'userfullname', get_string('user'), fullname($user));

        $options = [
            course_reset::RESETTYPE_STANDARD => new \lang_string('resettype_standard', 'enrol_programs'),
            course_reset::RESETTYPE_FULL => new \lang_string('resettype_full', 'enrol_programs'),
        ];
        $mform->addElement('select', 'resettype', get_string('resettype', 'enrol_programs'), $options);

        /** @var \enrol_programs\local\source\base $coursceclass */
        $coursceclass = allocation::get_source_classes()[$source->type];
        if ($coursceclass::allocation_edit_supported($program, $source, $allocation)) {
            $this->editsupported = true;
            $mform->addElement('advcheckbox', 'updateallocation', get_string('updateallocation', 'enrol_programs'));
            $mform->addElement('date_time_selector', 'timestart', get_string('programstart_date', 'enrol_programs'), ['optional' => false]);
            $mform->disabledIf('timestart', 'updateallocation', 'eq', 0);
            $mform->addElement('date_time_selector', 'timedue', get_string('programdue_date', 'enrol_programs'), ['optional' => true]);
            $mform->disabledIf('timedue', 'updateallocation', 'eq', 0);
            $mform->addElement('date_time_selector', 'timeend', get_string('programend_date', 'enrol_programs'), ['optional' => true]);
            $mform->disabledIf('timeend', 'updateallocation', 'eq', 0);
        } else {
            $this->editsupported = false;
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $allocation->id);

        $this->add_action_buttons(true, get_string('resetallocation', 'enrol_programs'));

        $this->set_data($allocation);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($this->editsupported && $data['updateallocation']) {
            $errors = array_merge($errors, \enrol_programs\local\allocation::validate_allocation_dates(
                $data['timestart'], $data['timedue'], $data['timeend']));
        }

        return $errors;
    }
}
