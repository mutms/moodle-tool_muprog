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
 * Update program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\program;
use tool_mulib\muform\handler;
use tool_mulib\muform\util\file_area;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);
$syscontext = \context_system::instance();

$currenturl = new core\url('/admin/tool/muprog/management/program_update.php', ['id' => $program->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('program_update', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = (array)$program;
$current['image'] = new file_area($syscontext, 'tool_muprog', 'image', $program->id);
$current['descriptionfilearea'] = new file_area($syscontext, 'tool_muprog', 'description', $program->id);
$form = new \tool_muprog\local\form\program_update($currenturl, $current);

$returnurl = new core\url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    // Custom fields and description files are saved by the form elements.
    $record = (object)array_filter((array)$data, fn($key) => !str_starts_with($key, 'customfield_'), ARRAY_FILTER_USE_KEY);
    $record->id = $program->id;
    unset($record->archived);
    $program = program::update_general($record);
    $form->get_element('description')->save_area();
    $form->get_element('customfields')->save($program->id);
    $handler->submitted($returnurl);
}

$handler->render($form);
