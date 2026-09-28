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
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Edit item completion evidence data.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_evidence_edit extends form {
    #[\Override]
    protected function definition(): void {
        $this->add(new info('itemfullname', get_string('item', 'tool_muprog')));

        $this->add(new info('completiondate', get_string('completiondate', 'tool_muprog')));

        $this->add(new datetime('evidencetimecompleted', get_string('evidencedate', 'tool_muprog')));

        $evidencedetails = new textarea('evidencedetails', get_string('evidence_details', 'tool_muprog'));
        $evidencedetails->set_required_marker(true);
        $evidencedetails->add_validator(new required_if_visible());
        $this->add($evidencedetails);
        $this->get_display_manager()->hide_if('evidencedetails', 'evidencetimecompleted', 'empty');

        $this->add(new checkbox('itemrecalculate', get_string('itemrecalculate', 'tool_muprog')));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('update')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
