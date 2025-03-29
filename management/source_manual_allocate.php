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
use tool_muprog\local\source\manual;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

require_login();

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'manual'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/source_manual_allocate.php', ['sourceid' => $source->id]);
$returnurl = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

if (!manual::is_allocation_possible($program, $source)) {
    redirect($returnurl);
}

management::setup_program_page($currenturl, $context, $program, 'program_users');

$form = new \tool_muprog\local\form\source_manual_allocate(null, ['program' => $program, 'source' => $source, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $userids = [];
    if ($data->cohortid) {
        $userids = $DB->get_fieldset_select('cohort_members', 'userid', "cohortid = ?", [$data->cohortid]);
    }
    if ($data->users) {
        $userids = array_merge($userids, $data->users);
        $userids = array_unique($userids);
    }
    if ($userids) {
        manual::allocate_users($program->id, $source->id, $userids);
    }
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('source_manual_allocateusers', 'tool_muprog'), 3);

echo $form->render();

echo $OUTPUT->footer();
