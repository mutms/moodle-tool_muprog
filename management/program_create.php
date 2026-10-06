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
 * Add program.
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
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/muprog:edit', $context);

if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new core\url('/admin/tool/muprog/management/program_create.php', ['contextid' => $context->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('program_create', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = [
    'contextid' => $context->id,
    'creategroups' => 0,
    'draft' => (int)(bool)get_config('tool_muprog', 'program_draftdefault'),
    'descriptionformat' => FORMAT_HTML,
    'descriptionfilearea' => new file_area(context_system::instance(), 'tool_muprog', 'description', null),
];
$form = new \tool_muprog\local\form\program_create($currenturl, $current);

if ($form->is_cancelled()) {
    $handler->cancelled(new core\url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]));
}

if ($data = $form->get_data()) {
    // Custom fields and description files are saved by the form elements.
    $record = (object)array_filter((array)$data, fn($key) => !str_starts_with($key, 'customfield_'), ARRAY_FILTER_USE_KEY);
    $record->addsources = array_fill_keys($data->addsources ?? [], 1);
    $program = program::create($record);
    $description = $form->get_element('description');
    $description->get_file_area()->set_itemid($program->id);
    $description->save_area();
    $form->get_element('customfields')->save($program->id);
    $returnurl = new core\url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $handler->submitted($returnurl);
}

$handler->render($form);
