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
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Restore program.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_restore extends form {
    #[\Override]
    protected function definition(): void {
        $info = '<div class="alert alert-info">' . markdown_to_html(get_string('program_restore_info', 'tool_muprog')) . '</div>';
        $this->add(new inforawhtml('info', '', $info));

        $this->add(new info('fullname', get_string('programname', 'tool_muprog')));

        $this->add(new info('idnumber', get_string('idnumber'), null, info::PLAIN));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('program_restore', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
