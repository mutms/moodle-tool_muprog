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
 * Program content import interface.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\form\program_content_import;
use tool_muprog\local\form\program_content_import_confirmation;
use tool_muprog\local\program;
use tool_muprog\muform\autocomplete\program_content_import_fromprogram;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);
$fromprogramid = optional_param('fromprogram', 0, PARAM_INT);

require_login();

$targetprogram = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($targetprogram->contextid);
require_capability('tool/muprog:edit', $context);
\tool_muprog\local\management::require_program_not_frozen($targetprogram);

$currenturl = new core\url('/admin/tool/muprog/management/program_content_import.php', ['id' => $targetprogram->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('importprogramcontent', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$returnurl = new core\url('/admin/tool/muprog/management/program_content.php', ['id' => $targetprogram->id]);

if ($targetprogram->archived) {
    redirect($returnurl);
}

$handler = handler::from_request();

// The program selected in the first step must be one the user may import from.
$fromprogram = null;
if ($fromprogramid && (new program_content_import_fromprogram($targetprogram->id))->label((string)$fromprogramid) !== null) {
    $fromprogram = $DB->get_record('tool_muprog_program', ['id' => $fromprogramid], '*', MUST_EXIST);
}

if (!$fromprogram) {
    $form = new program_content_import($currenturl, [], ['targetprogram' => $targetprogram]);
    if ($form->is_cancelled()) {
        $handler->cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $nexturl = new core\url($currenturl, ['fromprogram' => $data->fromprogram]);
        if ($handler->is_dialog()) {
            // The dialog continues with the second step, the page gets it from the next URL.
            $fromprogram = $DB->get_record('tool_muprog_program', ['id' => $data->fromprogram], '*', MUST_EXIST);
            $extra = ['targetprogram' => $targetprogram, 'fromprogram' => $fromprogram];
            $handler->render(new program_content_import_confirmation($nexturl, [], $extra));
        }
        redirect($nexturl);
    }
    $handler->render($form);
}

$PAGE->set_url(new core\url($currenturl, ['fromprogram' => $fromprogram->id]));
$form = new program_content_import_confirmation($PAGE->url, [], ['targetprogram' => $targetprogram, 'fromprogram' => $fromprogram]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->id = $targetprogram->id;
    $data->fromprogram = $fromprogram->id;
    program::load_content($targetprogram->id)->content_import($data);
    $handler->submitted($returnurl);
}

$handler->render($form);
