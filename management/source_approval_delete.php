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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_muprog\local\management;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$request = $DB->get_record('tool_muprog_request', ['id' => $id], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $request->userid], '*', MUST_EXIST);
$source = $DB->get_record('tool_muprog_source', ['id' => $request->sourceid], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/source_approval_delete.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_approval_requests');

$returnurl = new moodle_url('/admin/tool/muprog/management/source_approval_requests.php', ['id' => $program->id]);

$form = new \tool_muprog\local\form\source_approval_delete(null, ['request' => $request, 'user' => $user, 'program' => $program, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    tool_muprog\local\source\approval::delete_request($request->id);
    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $form->render();

echo $OUTPUT->footer();
