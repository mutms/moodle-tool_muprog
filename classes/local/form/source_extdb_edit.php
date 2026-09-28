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

use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\muform\autocomplete\source_extdb_edit_queryid;

/**
 * Edit external database sync settings.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_edit extends form {
    #[\Override]
    protected function definition(): void {
        $source = $this->get_extra_data()['source'];
        $yesno = ['1' => get_string('yes'), '0' => get_string('no')];
        $dm = $this->get_display_manager();

        $enable = new select('enable', get_string('active'), $yesno);
        $enable->set_frozen($source->hasallocations);
        $this->add($enable);

        $programid = (int)$this->get_extra_data()['program']->id;
        $auxint1 = new autocomplete(
            'auxint1',
            get_string('extdb_query', 'tool_mulib'),
            new source_extdb_edit_queryid($programid)
        );
        $this->add($auxint1);
        $dm->hide_if('auxint1', 'enable', 'eq', '0');

        $auxint2 = new checkbox('auxint2', get_string('source_extdb_archiveremoved', 'tool_muprog'));
        // Archive removed allocations by default in new sources.
        $auxint2->set_default(1);
        $this->add($auxint2);
        $dm->hide_if('auxint2', 'enable', 'eq', '0');

        // TODO: add more aux settings.

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('update')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
