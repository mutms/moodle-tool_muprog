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

use tool_muprog\local\content\set;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\duration;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\text;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Edit program set item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_update_set extends form {
    #[\Override]
    protected function definition(): void {
        $istop = $this->get_extra_data()['istop'];

        $this->add(new info('typename', get_string('item_type', 'tool_muprog')));

        if ($istop) {
            $this->add(new info('fullname', get_string('fullname')));
        } else {
            $fullname = new text('fullname', get_string('fullname'), ['maxlength' => 254]);
            $fullname->set_required(true);
            $this->add($fullname);
        }

        $sequencetypes = set::get_sequencetype_types();
        $sequencetype = new select('sequencetype', get_string('sequencetype', 'tool_muprog'), $sequencetypes);
        $sequencetype->set_required(true);
        $this->add($sequencetype);

        $minprerequisites = new number('minprerequisites', $sequencetypes[set::SEQUENCE_TYPE_ATLEAST], ['min' => 1]);
        $minprerequisites->set_required_marker(true);
        $minprerequisites->add_validator(new required_if_visible());
        $this->add($minprerequisites);
        $this->get_display_manager()->hide_if('minprerequisites', 'sequencetype', 'neq', set::SEQUENCE_TYPE_ATLEAST);

        $minpoints = new number('minpoints', $sequencetypes[set::SEQUENCE_TYPE_MINPOINTS], ['min' => 1]);
        $minpoints->set_required_marker(true);
        $minpoints->add_validator(new required_if_visible());
        $this->add($minpoints);
        $this->get_display_manager()->hide_if('minpoints', 'sequencetype', 'neq', set::SEQUENCE_TYPE_MINPOINTS);

        $this->add(new duration('completiondelay', get_string('completiondelay', 'tool_muprog')));

        if (!$istop) {
            $this->add(new number('points', get_string('itempoints', 'tool_muprog'), ['min' => 0]));
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('updateset', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
