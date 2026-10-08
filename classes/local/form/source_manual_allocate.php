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
use tool_mulib\muform\element\autocompletemany;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\customfields;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\customfield\allocation_handler;
use tool_muprog\muform\autocomplete\source_manual_allocate_cohortid;
use tool_muprog\muform\autocompletemany\source_manual_allocate_users;

/**
 * Allocate users and cohorts manually.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_allocate extends form {
    #[\Override]
    protected function definition(): void {
        $programid = (int)$this->get_extra_data()['program']->id;

        $users = new autocompletemany('users', get_string('users'), new source_manual_allocate_users($programid));
        $users->set_required_marker(true);
        $this->add($users);

        $cohortid = new autocomplete('cohortid', get_string('cohort', 'cohort'), new source_manual_allocate_cohortid($programid));
        $cohortid->set_required_marker(true);
        $this->add($cohortid);

        // Either users or cohort is required, the other one is hidden when not needed.
        $this->get_display_manager()->hide_if('cohortid', 'users', 'notempty');
        $this->get_display_manager()->hide_if('users', 'cohortid', 'notempty');

        $this->add(new customfields('customfields', allocation_handler::create(), null));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('source_manual_allocateusers', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        if (!$data['users'] && !$data['cohortid']) {
            $allerrors['users'][] = get_string('required');
            $allerrors['cohortid'][] = get_string('required');
        }
        if ($data['users'] && $data['cohortid']) {
            // Hiding of fields is cosmetic only, never allocate a cohort that the user cannot see in the form.
            $allerrors['cohortid'][] = get_string('error');
        }
    }
}
