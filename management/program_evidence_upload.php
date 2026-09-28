<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
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
// phpcs:disable moodle.Files.LineLength.TooLong

/**
 * Uploads program evidence.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\form\program_evidence_upload_file;
use tool_muprog\local\form\program_evidence_upload_options;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$programid = required_param('programid', PARAM_INT);
$csvfile = optional_param('csvfile', 0, PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:manageevidence', $context);

$currenturl = new core\url('/admin/tool/muprog/management/program_evidence_upload.php', ['programid' => $program->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('evidenceupload', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$returnurl = new core\url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

if ($program->archived) {
    redirect($returnurl);
}

$handler = handler::from_request();

// Rows parsed in the first step are stored in the user's upload area.
$filedata = \tool_muprog\local\util::get_uploaded_data($csvfile);

if (!$filedata) {
    $form = new program_evidence_upload_file($currenturl, []);
    if ($form->is_cancelled()) {
        $handler->cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $nexturl = new core\url($currenturl, ['csvfile' => $data->csvfile]);
        if ($handler->is_dialog()) {
            // The dialog continues with the second step, the page gets it from the next URL.
            $filedata = \tool_muprog\local\util::get_uploaded_data((int)$data->csvfile);
            $handler->render(new program_evidence_upload_options($nexturl, [], ['filedata' => $filedata]));
        }
        redirect($nexturl);
    }
    $handler->render($form);
}

$PAGE->set_url(new core\url($currenturl, ['csvfile' => $csvfile]));
$form = new program_evidence_upload_options($PAGE->url, [], ['filedata' => $filedata]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->programid = $program->id;
    // The uploaded file and parsed rows are deleted after processing.
    $data->csvfile = $csvfile;
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

    $handler->submitted($returnurl);
}

$handler->render($form);
