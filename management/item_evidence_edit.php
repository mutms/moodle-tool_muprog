<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Programs management interface.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
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
use tool_muprog\local\allocation;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$allocationid = required_param('allocationid', PARAM_INT);
$itemid = required_param('itemid', PARAM_INT);

require_login();

$allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocationid], '*', MUST_EXIST);
$item = $DB->get_record('tool_muprog_item', ['id' => $itemid, 'programid' => $allocation->programid], '*', MUST_EXIST);
$completion = $DB->get_record('tool_muprog_completion', ['allocationid' => $allocation->id, 'itemid' => $item->id]);
$evidence = $DB->get_record('tool_muprog_evidence', ['userid' => $allocation->userid, 'itemid' => $item->id]);

$user = $DB->get_record('user', ['id' => $allocation->userid, 'deleted' => 0], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:manageevidence', $context);

$returnurl = new moodle_url('/admin/tool/muprog/management/user_allocation.php', ['id' => $allocation->id]);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_evidence_edit.php', ['allocationid' => $allocation->id, 'itemid' => $item->id]);

management::setup_program_page($currenturl, $context, $program, 'program_users');

if ($program->archived || $allocation->archived) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_evidence_edit(null, [
    'allocation' => $allocation, 'item' => $item, 'user' => $user,
    'completion' => $completion, 'evidence' => $evidence, 'context' => $context,
]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    allocation::update_item_evidence($data);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(fullname($user), 3);
echo $OUTPUT->heading(get_string('itemcompletion', 'tool_muprog'), 4);

echo $form->render();

echo $OUTPUT->footer();
