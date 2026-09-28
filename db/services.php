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

/**
 * Program external functions.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_muprog_get_programs' => [
        'classname' => tool_muprog\external\get_programs::class,
        'description' => 'Return list of programs that match the search parameters.',
        'type' => 'read',
        'capabilities' => 'tool/muprog:view',
    ],
    'tool_muprog_get_program_allocations' => [
        'classname' => tool_muprog\external\get_program_allocations::class,
        'description' => 'Return list of program allocations for given programid and optional userids.',
        'type' => 'read',
        'capabilities' => 'tool/muprog:view',
    ],
    'tool_muprog_source_manual_allocate_users' => [
        'classname' => tool_muprog\external\source_manual_allocate_users::class,
        'description' => 'Allocates users or cohorts to the program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:allocate,moodle/cohort:view',
    ],
    'tool_muprog_delete_program_allocations' => [
        'classname' => tool_muprog\external\delete_program_allocations::class,
        'description' => 'Deallocates users from the program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:deallocate',
    ],
    'tool_muprog_update_program_allocation' => [
        'classname' => tool_muprog\external\update_program_allocation::class,
        'description' => 'Updates the allocation for the user and the program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:admin',
    ],
    'tool_muprog_archive_program_allocation' => [
        'classname' => tool_muprog\external\archive_program_allocation::class,
        'description' => 'Archives the allocation for the user and the program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:deallocate',
    ],
    'tool_muprog_restore_program_allocation' => [
        'classname' => tool_muprog\external\restore_program_allocation::class,
        'description' => 'Restores the allocation for the user and the program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:allocate',
    ],
    'tool_muprog_source_cohort_get_cohorts' => [
        'classname' => tool_muprog\external\source_cohort_get_cohorts::class,
        'description' => 'Gets list of cohort that are synced with the program cohort allocation.',
        'type' => 'read',
        'capabilities' => 'tool/muprog:view',
    ],
    'tool_muprog_source_cohort_add_cohort' => [
        'classname' => tool_muprog\external\source_cohort_add_cohort::class,
        'description' => 'Add cohort to the list of synchronised cohorts of one program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:edit,moodle/cohort:view',
    ],
    'tool_muprog_source_cohort_delete_cohort' => [
        'classname' => tool_muprog\external\source_cohort_delete_cohort::class,
        'description' => 'Removes a cohort from the list of synchronised cohorts of one program.',
        'type' => 'write',
        'capabilities' => 'tool/muprog:edit',
    ],
];
