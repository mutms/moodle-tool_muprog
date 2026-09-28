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
use tool_mulib\muform\element\customfields;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\customfield\allocation_handler;
use tool_muprog\local\allocation;

/**
 * Edit user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_update extends form {
    #[\Override]
    protected function definition(): void {
        $allocation = $this->get_extra_data()['allocation'];

        $this->add(new info('userfullname', get_string('user')));

        $timeallocated = new datetime('timeallocated', get_string('allocationdate', 'tool_muprog'));
        $timeallocated->set_frozen(true);
        $this->add($timeallocated);

        $timestart = new datetime('timestart', get_string('programstart_date', 'tool_muprog'));
        $timestart->set_required(true);
        $this->add($timestart);

        $this->add(new datetime('timedue', get_string('programdue_date', 'tool_muprog')));

        $this->add(new datetime('timeend', get_string('programend_date', 'tool_muprog')));

        $this->add(new customfields('customfields', allocation_handler::create(), (int)$allocation->id));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('allocation_update', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $errors = allocation::validate_allocation_dates((int)$data['timestart'], $data['timedue'], $data['timeend']);
        foreach ($errors as $name => $error) {
            $allerrors[$name][] = $error;
        }
    }
}
