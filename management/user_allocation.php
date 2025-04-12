<?php
// This file is part of Programs for Moodle™.
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
 * Programs management interface.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\management;
use tool_muprog\local\allocation;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
$source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);

$currenturl = new moodle_url('/admin/tool/muprog/management/user_allocation.php', ['id' => $allocation->id]);

management::setup_program_page($currenturl, $context, $program, 'program_users');

$sourceclasses = allocation::get_source_classes();
/** @var \tool_muprog\local\source\base $sourceclass */
$sourceclass = $sourceclasses[$source->type];

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

// Refresh allocation data just in case.
allocation::fix_user_enrolments($program->id, $allocation->userid);
$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);

$dropdown = new \tool_mulib\output\action_menu\dropdown(
    get_string('extra_menu_management_program_allocation', 'tool_muprog'));

if (has_capability('tool/muprog:admin', $context)) {
    $dropdown->add_dialog_form(new \tool_mulib\output\dialog_form\link(
        new moodle_url('/admin/tool/muprog/management/program_completion_override.php', ['id' => $allocation->id]),
        get_string('programcompletionoverride', 'tool_muprog')
    ));
}
if (has_capability('tool/muprog:manageallocation', $context)) {
    if ($sourceclass::allocation_edit_supported($program, $source, $allocation)
        && !$program->archived && !$allocation->archived) {
        $dropdown->add_dialog_form(new \tool_mulib\output\dialog_form\link(
            new \moodle_url('/admin/tool/muprog/management/user_allocation_edit.php', ['id' => $allocation->id]),
            get_string('updateallocation', 'tool_muprog')
        ));
    }
}
if (has_capability('tool/muprog:archive', $context)) {
    if ($sourceclass::allocation_archiving_supported($program, $source, $allocation)) {
        if ($allocation->archived) {
            $dropdown->add_dialog_form(new \tool_mulib\output\dialog_form\link(
                new \moodle_url('/admin/tool/muprog/management/user_allocation_unarchive.php', ['id' => $allocation->id]),
                get_string('unarchive', 'tool_muprog')
            ));
        } else {
            $dropdown->add_dialog_form(new \tool_mulib\output\dialog_form\link(
                new \moodle_url('/admin/tool/muprog/management/user_allocation_archive.php', ['id' => $allocation->id]),
                get_string('archive', 'tool_muprog')
            ));
        }
    }
}
if (has_capability('tool/muprog:manageallocation', $context)) {
    if ($sourceclass::allocation_delete_supported($program, $source, $allocation)) {
        $link = new \tool_mulib\output\dialog_form\link(
            new \moodle_url('/admin/tool/muprog/management/user_allocation_delete.php', ['id' => $allocation->id]),
            get_string('deleteallocation', 'tool_muprog')
        );
        $link->set_after_submit($link::AFTER_SUBMIT_REDIRECT);
        $dropdown->add_dialog_form($link);
    }
}
if (has_capability('tool/muprog:reset', $context) && !$program->archived && !$allocation->archived) {
    $dropdown->add_dialog_form(new \tool_mulib\output\dialog_form\link(
        new \moodle_url('/admin/tool/muprog/management/user_allocation_reset.php', ['id' => $allocation->id]),
        get_string('resetallocation', 'tool_muprog')
    ));
}
if ($dropdown->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($dropdown));
}

echo $OUTPUT->header();

echo $OUTPUT->heading($OUTPUT->user_picture($user) . fullname($user), 2, ['h3']);

echo $managementoutput->render_user_allocation($program, $source, $allocation);
echo $managementoutput->render_user_progress($program, $allocation);
echo $managementoutput->render_user_notifications($program, $allocation);

echo $OUTPUT->footer();
