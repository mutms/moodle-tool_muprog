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
 * Uploads program evidence.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
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
require_once($CFG->dirroot . '/lib/formslib.php');

$programid = required_param('programid', PARAM_INT);
$draftitemid = optional_param('csvfile', null, PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:manageevidence', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_evidence_upload.php', ['programid' => $programid]);
$returnurl = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $programid]);

if ($program->archived) {
    redirect($returnurl);
}

management::setup_program_page($currenturl, $context, $program, 'program_users');

$filedata = null;
if ($draftitemid && confirm_sesskey()) {
    $filedata = \tool_muprog\local\util::get_uploaded_data($draftitemid);
}

if (!$filedata) {
    $form = new \tool_muprog\local\form\program_evidence_upload_file(null, ['program' => $program, 'context' => $context]);
} else {
    $form = new \tool_muprog\local\form\program_evidence_upload_options(null, ['program' => $program,
        'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if ($filedata && $form instanceof \tool_muprog\local\form\program_evidence_upload_options) {
        $result = \tool_muprog\local\allocation::process_evidence_uploaded_data($data, $filedata);

        if ($result['updated']) {
            $message = get_string('evidenceupload_updated', 'tool_muprog', $result['updated']);
            \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($result['skipped']) {
            $message = get_string('evidenceupload_skipped', 'tool_muprog', $result['skipped']);
            \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        }
        if ($result['errors']) {
            $message = get_string('evidenceupload_errors', 'tool_muprog', $result['errors']);
            \core\notification::add($message, \core\output\notification::NOTIFY_WARNING);
        }

        $form->redirect_submitted($returnurl);
    }
    if (!$filedata && $form instanceof \tool_muprog\local\form\program_evidence_upload_file) {
        $filedata = \tool_muprog\local\util::get_uploaded_data($draftitemid);
        if ($filedata) {
            $form = new \tool_muprog\local\form\program_evidence_upload_options(null, ['program' => $program,
                'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
        }
    }
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('evidenceupload', 'tool_muprog'), 3);

echo $form->render();

echo $OUTPUT->footer();
