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

use tool_muprog\local\program;
use tool_muprog\local\management;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/muprog:edit', $context);

if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new moodle_url('/admin/tool/muprog/management/program_add.php', ['contextid' => $context->id]);
management::setup_index_page($currenturl, $context);

$program = new stdClass();
$program->contextid = $context->id;
$program->fullname = '';
$program->idnumber = '';
$program->creategroups = 0;
$program->description = '';
$program->descriptionformat = FORMAT_HTML;

$editoroptions = program::get_description_editor_options($context->id);

$form = new \tool_muprog\local\form\program_add(null, ['data' => $program, 'editoroptions' => $editoroptions]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]));
}

if ($data = $form->get_data()) {
    $program = program::add_program($data);
    $returlurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $form->redirect_submitted($returlurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('addprogram', 'tool_muprog'));

echo $form->render();

echo $OUTPUT->footer();
