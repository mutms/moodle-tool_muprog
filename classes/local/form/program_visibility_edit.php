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

use tool_mulib\muform\element\autocompletemany;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\yesno;
use tool_mulib\muform\form;
use tool_muprog\muform\autocompletemany\program_visibility_edit_cohortids;

/**
 * Edit program visibility.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_visibility_edit extends form {
    #[\Override]
    protected function definition(): void {
        $programid = (int)$this->get_current_data()['id'];

        $publicaccess = new yesno('publicaccess', get_string('publicaccess', 'tool_muprog'));
        $publicaccess->add_help_button('publicaccess', 'tool_muprog');
        $this->add($publicaccess);

        $source = new program_visibility_edit_cohortids($programid);
        $this->add(new autocompletemany('cohortids', get_string('cohorts', 'tool_muprog'), $source));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('program_update', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
