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
use tool_muprog\local\content\course;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$item = $DB->get_record('tool_muprog_item', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $item->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_course_edit.php', ['id' => $item->id]);
management::setup_program_page($currenturl, $context, $program, 'program_content');

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

if ($program->archived) {
    redirect($returnurl);
}

$top = program::load_content($program->id);
$course = $top->find_item($item->id);
if (!$course || !($course instanceof course)) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_course_edit(null, ['course' => $course, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $top->update_course($course, (array)$data);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('updatecourse', 'tool_muprog'), 3);

echo $form->render();

echo $OUTPUT->footer();
