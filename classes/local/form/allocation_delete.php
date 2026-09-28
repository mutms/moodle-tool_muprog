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
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Delete user allocation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class allocation_delete extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new info('userfullname', get_string('user')));

        $dates = [
            'timeallocated' => get_string('allocationdate', 'tool_muprog'),
            'timestart' => get_string('programstart_date', 'tool_muprog'),
            'timedue' => get_string('programdue_date', 'tool_muprog'),
            'timeend' => get_string('programend_date', 'tool_muprog'),
            'timecompleted' => get_string('completiondate', 'tool_muprog'),
        ];
        foreach ($dates as $name => $label) {
            $date = new datetime($name, $label);
            $date->set_frozen(true);
            $this->add($date);
        }

        $archived = new select('archived', get_string('archived', 'tool_muprog'), ['0' => get_string('no'), '1' => get_string('yes')]);
        $archived->set_frozen(true);
        $this->add($archived);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('deleteallocation', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
