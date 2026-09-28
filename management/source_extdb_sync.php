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
 * External database sync tool.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

require_login();

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'extdb'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid, 'archived' => 0], '*', MUST_EXIST);
$query = $DB->get_record('tool_mulib_extdb_query', ['id' => $source->auxint1]);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$currenturl = new core\url('/admin/tool/muprog/management/source_extdb_sync.php', ['sourceid' => $source->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('source_extdb_sync', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$returnurl = new core\url('/admin/tool/muprog/management/program_allocation.php', ['id' => $program->id]);

$handler = handler::from_request();

$current = [
    'program' => $program->fullname,
    'query' => $query ? $query->name : get_string('error'),
    'lastsync' => $source->auxint5 ? userdate($source->auxint5) : get_string('none'),
];
$form = new \tool_muprog\local\form\source_extdb_sync($currenturl, $current, ['program' => $program, 'source' => $source, 'query' => $query]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $result = tool_muprog\local\source\extdb::sync_asap($source);
    if ($result) {
        \core\notification::add(
            get_string('source_extdb_sync_queued', 'tool_muprog'),
            \core\notification::SUCCESS
        );
    } else {
        \core\notification::add(
            get_string('source_extdb_sync_notqueued', 'tool_muprog'),
            \core\notification::ERROR
        );
    }
    $handler->submitted($returnurl);
}

$handler->render($form);
