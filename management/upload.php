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
 * Programs upload.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\form\upload_files;
use tool_muprog\local\form\upload_options;
use tool_muprog\local\management;
use tool_muprog\local\upload;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require('../../../../config.php');

require_login();

$contextid = optional_param('contextid', 0, PARAM_INT);
$draftid = optional_param('files', 0, PARAM_INT);

$returnurl = new core\url('/admin/tool/muprog/management/index.php', ['contextid' => $contextid]);
$currenturl = new core\url('/admin/tool/muprog/management/upload.php', ['contextid' => $contextid]);

if ($contextid) {
    $context = context::instance_by_id($contextid);
} else {
    $context = context_system::instance();
}
require_capability('tool/muprog:upload', $context);

// Programs parsed in the first step are stored in the user's upload area.
$filedata = \tool_muprog\local\util::get_uploaded_data($draftid, false);
if ($filedata) {
    $currenturl->param('files', $draftid);
}

management::setup_index_page($currenturl, $context);
$PAGE->set_heading(get_string('upload', 'tool_muprog'));

$handler = handler::from_request();

if (!$filedata) {
    $form = new upload_files($currenturl, []);
    if ($form->is_cancelled()) {
        $handler->cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        redirect(new core\url($currenturl, ['files' => $data->files]));
    }
    $handler->render($form);
}

$form = new upload_options($currenturl, ['contextid' => $contextid ?: null], ['filedata' => $filedata]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    upload::process($data, $filedata);
    $handler->submitted($returnurl);
}

$handler->render(function (\core\output\core_renderer $output) use ($form, $filedata): string {
    $html = $form->render($output);
    $html .= $output->heading(get_string('upload_preview', 'tool_muprog'), 3);
    $html .= upload::preview($filedata);
    return $html;
});
