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
 * Program content import interface.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
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

$id = required_param('id', PARAM_INT);
$fromprogram = optional_param('fromprogram', 0, PARAM_INT);

require_login();

$targetprogram = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($targetprogram->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/program_content_import.php', ['id' => $targetprogram->id, 'fromprogram' => $fromprogram]);
management::setup_program_page($currenturl, $context, $targetprogram, 'program_content');

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $targetprogram->id]);

if ($targetprogram->archived) {
    redirect($returnurl);
}

$top = program::load_content($targetprogram->id);

$form = null;
if (!$fromprogram) {
    $form = new \tool_muprog\local\form\program_content_import(null,
        ['id' => $targetprogram->id, 'contextid' => $context->id]);
    if ($form->is_cancelled()) {
        redirect($returnurl);
    } else if ($data = $form->get_data()) {
        $fromprogram = $data->fromprogram;
        unset($data);
        $form = null;
    }
}

if (!$form) {
    $form = new \tool_muprog\local\form\program_content_import_confirmation(null,
        ['id' => $targetprogram->id, 'contextid' => $context->id, 'fromprogram' => $fromprogram]);

    if ($form->is_cancelled()) {
        redirect($returnurl);
    }

    if ($data = $form->get_data()) {
        $from = $DB->get_record('tool_muprog_program', ['id' => $data->fromprogram], '*', MUST_EXIST);
        $top->content_import($data);
        $form->redirect_submitted($returnurl);
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($targetprogram->fullname));

echo $OUTPUT->heading(get_string('importprogramcontent', 'tool_muprog'), 3);

echo $form->render();

echo $OUTPUT->footer();
