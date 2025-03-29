<?php
// This file is part of Programs for Moodle™.
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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_muprog\local\management;
use tool_muprog\local\program;
use tool_muprog\local\util;

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_allocation');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

echo $OUTPUT->header();

if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_allocations_edit.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\dialog_form\icon($editurl, get_string('updateallocations', 'tool_muprog'), 'i/settings');
    $editbutton->set_dialog_name(get_string('allocations', 'tool_muprog'));
    $editbutton = ' ' . $OUTPUT->render($editbutton);
} else {
    $editbutton = '';
}
echo $OUTPUT->heading(get_string('allocations', 'tool_muprog') . $editbutton, 2, ['h3']);
echo $managementoutput->render_program_allocation($program);

if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_scheduling_edit.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\dialog_form\icon($editurl, get_string('updatescheduling', 'tool_muprog'), 'i/settings');
    $editbutton->set_dialog_name(get_string('scheduling', 'tool_muprog'));
    $editbutton = ' ' . $OUTPUT->render($editbutton);
} else {
    $editbutton = '';
}
echo $OUTPUT->heading(get_string('scheduling', 'tool_muprog') . $editbutton, 2, ['h3']);
echo $managementoutput->render_program_scheduling($program);

echo $OUTPUT->heading(get_string('allocationsources', 'tool_muprog'), 2, ['h3']);
echo $managementoutput->render_program_sources($program);

if (has_capability('tool/muprog:edit', $context) && !$program->archived) {
    $importurl = new moodle_url('/admin/tool/muprog/management/program_allocation_import.php', ['id' => $program->id]);
    $importaction = new \tool_mulib\output\dialog_form\button($importurl, get_string('importprogramallocation',
        'tool_muprog'));
    echo $OUTPUT->render($importaction);
}

echo $OUTPUT->footer();
