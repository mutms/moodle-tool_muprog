<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Program management interface.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use enrol_programs\local\management;
use enrol_programs\local\allocation;

require('../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$allocation = $DB->get_record('enrol_programs_allocations', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('enrol_programs_programs', ['id' => $allocation->programid], '*', MUST_EXIST);
$source = $DB->get_record('enrol_programs_sources', ['id' => $allocation->sourceid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('enrol/programs:view', $context);

$user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);

$currenturl = new moodle_url('/enrol/programs/management/user_allocation.php', ['id' => $allocation->id]);

management::setup_program_page($currenturl, $context, $program);
$PAGE->set_docs_path("$CFG->wwwroot/enrol/programs/documentation.php/program_allocation.md");

$sourceclasses = allocation::get_source_classes();
/** @var \enrol_programs\local\source\base $sourceclass */
$sourceclass = $sourceclasses[$source->type];

/** @var \enrol_programs\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('enrol_programs', 'management');

echo $OUTPUT->header();

echo $managementoutput->render_management_program_tabs($program, 'users');

// Refresh allocation data just in case.
allocation::fix_user_enrolments($program->id, $allocation->userid);
$allocation = $DB->get_record('enrol_programs_allocations', ['id' => $allocation->id], '*', MUST_EXIST);

$dropdown = new \local_openlms\output\extra_menu\dropdown(
    get_string('extra_menu_management_program_allocation', 'enrol_programs'));

if (has_capability('enrol/programs:admin', $context)) {
    $dropdown->add_dialog_form(new \local_openlms\output\dialog_form\link(
        new moodle_url('/enrol/programs/management/program_completion_override.php', ['id' => $allocation->id]),
        get_string('programcompletionoverride', 'enrol_programs')
    ));
}
if (has_capability('enrol/programs:manageallocation', $context)) {
    if ($sourceclass::allocation_edit_supported($program, $source, $allocation)
        && !$program->archived && !$allocation->archived) {
        $dropdown->add_dialog_form(new \local_openlms\output\dialog_form\link(
            new \moodle_url('/enrol/programs/management/user_allocation_edit.php', ['id' => $allocation->id]),
            get_string('updateallocation', 'enrol_programs')
        ));
    }
}
if (has_capability('enrol/programs:archive', $context)) {
    if ($sourceclass::allocation_archiving_supported($program, $source, $allocation)) {
        if ($allocation->archived) {
            $dropdown->add_dialog_form(new \local_openlms\output\dialog_form\link(
                new \moodle_url('/enrol/programs/management/user_allocation_unarchive.php', ['id' => $allocation->id]),
                get_string('unarchive', 'enrol_programs')
            ));
        } else {
            $dropdown->add_dialog_form(new \local_openlms\output\dialog_form\link(
                new \moodle_url('/enrol/programs/management/user_allocation_archive.php', ['id' => $allocation->id]),
                get_string('archive', 'enrol_programs')
            ));
        }
    }
}
if (has_capability('enrol/programs:manageallocation', $context)) {
    if ($sourceclass::allocation_delete_supported($program, $source, $allocation)) {
        $link = new \local_openlms\output\dialog_form\link(
            new \moodle_url('/enrol/programs/management/user_allocation_delete.php', ['id' => $allocation->id]),
            get_string('deleteallocation', 'enrol_programs')
        );
        $link->set_after_submit($link::AFTER_SUBMIT_REDIRECT);
        $dropdown->add_dialog_form($link);
    }
}
if (has_capability('enrol/programs:reset', $context) && !$program->archived && !$allocation->archived) {
    $dropdown->add_dialog_form(new \local_openlms\output\dialog_form\link(
        new \moodle_url('/enrol/programs/management/user_allocation_reset.php', ['id' => $allocation->id]),
        get_string('resetallocation', 'enrol_programs')
    ));
}
if ($dropdown->has_items()) {
    echo '<div class="float-right">';
    echo $OUTPUT->render($dropdown);
    echo '</div>';
}

echo $OUTPUT->heading($OUTPUT->user_picture($user) . fullname($user), 2, ['h3']);

echo $managementoutput->render_user_allocation($program, $source, $allocation);
echo $managementoutput->render_user_progress($program, $allocation);
echo $managementoutput->render_user_notifications($program, $allocation);

echo $OUTPUT->footer();
