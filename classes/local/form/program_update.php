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
use tool_mulib\muform\element\editor;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\tags;
use tool_mulib\muform\element\text;
use tool_mulib\muform\element\yesno;
use tool_mulib\muform\form;
use tool_muprog\customfield\program_handler;
use tool_muprog\muform\tagarea\program as program_tagarea;

/**
 * Update program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_update extends form {
    #[\Override]
    protected function definition(): void {
        $current = $this->get_current_data();
        $contextid = (int)$current['contextid'];
        $programid = (int)$current['id'];

        $fullname = new text('fullname', get_string('programname', 'tool_muprog'), ['maxlength' => 254]);
        $fullname->set_required(true);
        $this->add($fullname);

        $idnumber = new text('idnumber', get_string('programidnumber', 'tool_muprog'), ['type' => 'rawtext', 'maxlength' => 254]);
        $idnumber->set_required(true);
        $this->add($idnumber);

        $creategroups = new yesno('creategroups', get_string('creategroups', 'tool_muprog'));
        $creategroups->add_help_button('creategroups', 'tool_muprog');
        $this->add($creategroups);

        $this->add(new tags('tags', get_string('tags'), new program_tagarea($programid, $contextid)));

        $this->add(new filemanager('image', get_string('programimage', 'tool_muprog'), 1, ['.jpg', '.jpeg', '.jpe', '.png']));

        $this->add(new editor('description', get_string('description'), -1));

        $archived = new yesno('archived', get_string('archived', 'tool_muprog'));
        $archived->set_frozen(true);
        $this->add($archived);

        $this->add(new customfields('customfields', program_handler::create(), $programid));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('program_update', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        global $DB;
        $id = (int)$this->get_current_data()['id'];
        $select = "LOWER(idnumber) = LOWER(?) AND id <> ?";
        if (trim($data['idnumber']) !== $data['idnumber']) {
            $allerrors['idnumber'][] = get_string('error');
        } else if ($DB->record_exists_select('tool_muprog_program', $select, [$data['idnumber'], $id])) {
            $allerrors['idnumber'][] = get_string('error');
        }
    }
}
