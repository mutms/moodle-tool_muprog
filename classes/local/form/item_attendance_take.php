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

use tool_muprog\local\content\attendance;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\radios;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Take offline attendance data.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_attendance_take extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new info('userfullname', get_string('user')));

        $this->add(new info('itemfullname', get_string('fullname')));

        $status = new radios('status', get_string('attendance_status', 'tool_muprog'), array_map('strval', attendance::get_statuses()));
        $status->set_required(true);
        $this->add($status);

        $timeeffective = new datetime('timeeffective', get_string('attendance_effective', 'tool_muprog'));
        $timeeffective->set_required_marker(true);
        $timeeffective->add_validator(new required_if_visible());
        $this->add($timeeffective);
        $this->get_display_manager()->hide_if('timeeffective', 'status', 'eq', attendance::STATUS_NOTSET);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('attendance_take', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
