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

namespace tool_muprog\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\local\content\attendance;
use tool_muprog\local\content\course;
use tool_muprog\local\content\credits;

/**
 * Delete program content item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_delete extends form {
    #[\Override]
    protected function definition(): void {
        $item = $this->get_extra_data()['item'];

        $this->add(new info('typename', get_string('item_type', 'tool_muprog')));

        $this->add(new info('fullname', get_string('fullname')));

        if ($item instanceof course) {
            $deletestr = get_string('deletecourse', 'tool_muprog');
        } else if ($item instanceof attendance) {
            $deletestr = get_string('deleteattendance', 'tool_muprog');
        } else if ($item instanceof credits) {
            $deletestr = get_string('deletecredits', 'tool_muprog');
        } else {
            $deletestr = get_string('deleteset', 'tool_muprog');
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', $deletestr), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
