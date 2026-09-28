<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\local\allocation;
use tool_muprog\local\course_reset;

/**
 * Reset user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_reset extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new info('userfullname', get_string('user')));

        $options = [
            '' => get_string('choosedots'),
            course_reset::RESETTYPE_STANDARD => get_string('resettype_standard', 'tool_muprog'),
            course_reset::RESETTYPE_FULL => get_string('resettype_full', 'tool_muprog'),
        ];
        $resettype = new select('resettype', get_string('resettype', 'tool_muprog'), $options);
        $resettype->set_required(true);
        $this->add($resettype);

        if ($this->is_edit_possible()) {
            $this->add(new checkbox('updateallocation', get_string('allocation_reset_updateallocation', 'tool_muprog')));

            $timestart = new datetime('timestart', get_string('programstart_date', 'tool_muprog'));
            $timestart->set_required_marker(true);
            $this->add($timestart);

            $this->add(new datetime('timedue', get_string('programdue_date', 'tool_muprog')));
            $this->add(new datetime('timeend', get_string('programend_date', 'tool_muprog')));

            $dm = $this->get_display_manager();
            $dm->disable_if('timestart', 'updateallocation', 'notchecked');
            $dm->disable_if('timedue', 'updateallocation', 'notchecked');
            $dm->disable_if('timeend', 'updateallocation', 'notchecked');
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('allocation_reset', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        if ($this->is_edit_possible() && $data['updateallocation']) {
            $errors = allocation::validate_allocation_dates((int)$data['timestart'], $data['timedue'], $data['timeend']);
            foreach ($errors as $name => $error) {
                $allerrors[$name][] = $error;
            }
        }
    }

    /**
     * Can the allocation dates be updated during reset?
     *
     * @return bool
     */
    private function is_edit_possible(): bool {
        $extra = $this->get_extra_data();
        $sourceclass = allocation::get_source_classname($extra['source']->type);
        return $sourceclass && $sourceclass::is_allocation_update_possible($extra['program'], $extra['source'], $extra['allocation']);
    }
}
