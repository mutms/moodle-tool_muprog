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

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_visibility.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_visibility');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

echo $OUTPUT->header();

$buttons = [];
if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_visibility_edit.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\dialog_form\button($editurl, get_string('edit'));
    $buttons[] = $OUTPUT->render($editbutton);
}

echo $managementoutput->render_program_visibility($program);

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
