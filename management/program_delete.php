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

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:delete', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_delete.php', ['id' => $program->id]);
management::setup_program_page($currenturl, $context, $program, 'program_general');

$form = new \tool_muprog\local\form\program_delete(null, ['program' => $program]);
$returnurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);

if (!$program->archived) {
    redirect($returnurl);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    program::delete_program($program->id);
    $returnurl = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $program->contextid]);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteprogram', 'tool_muprog'));

echo $form->render();

echo $OUTPUT->footer();
