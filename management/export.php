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
 * Programs export.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\management;
use tool_muprog\local\export;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_POST['format'])) {
    define('NO_DEBUG_DISPLAY', true);
}

require('../../../../config.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$archived = optional_param('archived', 0, PARAM_BOOL);

if ($id) {
    $program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
    $context = context::instance_by_id($program->contextid);
    $returnurl = new core\url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $contextid = $context->id;
    $archived = $program->archived;
} else {
    $program = null;
    if ($contextid) {
        $context = context::instance_by_id($contextid);
    } else {
        $context = context_system::instance();
        $contextid = $context->id;
    }
    $returnurl = new core\url('/admin/tool/muprog/management/index.php',
        ['contextid' => $contextid, 'archived' => $archived]);
}
$currenturl = new core\url('/admin/tool/muprog/management/export.php',
    ['id' => $id, 'contextid' => $contextid, 'archived' => $archived]);

require_capability('tool/muprog:export', $context);

if ($program) {
    management::setup_program_page($currenturl, $context, $program, 'program_general');
} else {
    management::setup_index_page($currenturl, $context);
}
$PAGE->set_heading(get_string('export', 'tool_muprog'));

$handler = handler::from_request();

$current = [
    'format' => 'json',
    'delimiter_name' => \tool_muprog\local\form\export::get_default_delimiter(),
    'encoding' => 'UTF-8',
];
if ($program) {
    $current['programids'] = [$program->id];
} else {
    $current['contextid'] = $contextid;
    $current['includesubcontexts'] = 0;
    $current['archived'] = $archived;
}
$form = new \tool_muprog\local\form\export($currenturl, $current, ['program' => $program, 'context' => $context]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}
if ($data = $form->get_data()) {
    export::process($data);
    die;
}

$handler->render($form);
