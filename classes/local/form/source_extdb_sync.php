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

/**
 * Edit external database manual sync form
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_sync extends form {
    #[\Override]
    protected function definition(): void {
        $source = $this->get_extra_data()['source'];

        $this->add(new info('program', get_string('program', 'tool_muprog')));

        $this->add(new info('query', get_string('extdb_query', 'tool_mulib'), null, info::PLAIN));

        if ($source->auxint4 || $source->auxint3) {
            $this->add(new info('pendingsync', get_string('source_extdb_pendingsync', 'tool_muprog'), get_string('yes')));
        }

        $this->add(new info('lastsync', get_string('source_extdb_lastsync', 'tool_muprog')));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('source_extdb_sync', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $extra = $this->get_extra_data();
        $program = $extra['program'];
        $source = $extra['source'];
        $query = $extra['query'];

        if ($program->archived) {
            $allerrors['program'][] = get_string('error');
        } else if (!$query) {
            $allerrors['query'][] = get_string('error');
        }

        if ($source->auxint4 && $source->auxint4 + HOURSECS > time()) {
            // Task is still running right now, wait at least an hour before adding a new task.
            $allerrors['pendingsync'][] = get_string('error');
        }
    }
}
