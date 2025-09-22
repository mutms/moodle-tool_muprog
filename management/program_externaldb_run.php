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

/**
 * Manual external database synchronisation.
 *
 * @package    tool_muprog
 * @copyright  2025 Open LMS
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\allocation;
use tool_muprog\local\source\externaldb;

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_sesskey();
require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

if ($program->archived) {
    redirect(new moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $program->id]));
}

externaldb::fix_allocations($program->id, null);
allocation::fix_enrol_instances($program->id);
allocation::fix_user_enrolments($program->id, null);

redirect(
    new moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $program->id]),
    get_string('runexternaldballocation_triggered', 'tool_muprog'),
    0,
    \core\output\notification::NOTIFY_SUCCESS
);
