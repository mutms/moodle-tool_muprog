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
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Edit program allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocations_edit extends form {
    #[\Override]
    protected function definition(): void {
        $start = new datetime('timeallocationstart', get_string('allocationstart', 'tool_muprog'));
        $start->add_help_button('allocationstart', 'tool_muprog');
        $this->add($start);

        $end = new datetime('timeallocationend', get_string('allocationend', 'tool_muprog'));
        $end->add_help_button('allocationend', 'tool_muprog');
        $this->add($end);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('program_allocations_edit', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        if (
            $data['timeallocationstart'] && $data['timeallocationend']
            && $data['timeallocationstart'] >= $data['timeallocationend']
        ) {
            $allerrors['timeallocationend'][] = get_string('error');
        }
    }
}
