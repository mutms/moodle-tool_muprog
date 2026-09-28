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

use tool_muprog\muform\autocompletemany\item_create_course_courseids;
use tool_mulib\muform\element\autocompletemany;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\duration;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Add courses to program.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_course extends form {
    #[\Override]
    protected function definition(): void {
        $programid = $this->get_extra_data()['programid'];

        $this->add(new info('typename', get_string('item_type', 'tool_muprog')));

        $courseids = new autocompletemany('courseids', get_string('courses'), new item_create_course_courseids($programid));
        $courseids->set_required(true);
        $this->add($courseids);

        $this->add(new duration('completiondelay', get_string('completiondelay', 'tool_muprog')));

        $this->add(new number('points', get_string('itempoints', 'tool_muprog'), ['min' => 0]));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('item_create_course', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
