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
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$itemrecord = $DB->get_record('tool_muprog_item', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $itemrecord->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_delete.php', ['id' => $itemrecord->id]);
management::setup_program_page($currenturl, $context, $program, 'program_content');

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

if ($program->archived) {
    redirect($returnurl);
}

$top = program::load_content($program->id);
$item = $top->find_item($itemrecord->id);
if (!$item) {
    $item = $top->find_orphaned_item($itemrecord->id);
}
if (!$item || !$item->is_deletable()) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_delete(null, ['item' => $item, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $top->delete_item($item->get_id());
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

if ($item instanceof set) {
    echo $OUTPUT->heading(get_string('deleteset', 'tool_muprog'), 3);
} else {
    echo $OUTPUT->heading(get_string('deletecourse', 'tool_muprog'), 3);
}

echo $form->render();

echo $OUTPUT->footer();
