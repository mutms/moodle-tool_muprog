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

/**
 * Confirm self-allocation to program.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */
/** @var stdClass $USER */

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new core\url('/admin/tool/muprog/my/source_selfallocation.php', ['sourceid' => $sourceid]));

require_login();

if (!\tool_mulib\local\mulib::is_muprog_active()) {
    redirect(new core\url('/'));
}

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'selfallocation'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$programcontext = context::instance_by_id($program->contextid);

if (!\tool_muprog\local\source\selfallocation::can_user_request($program, $source, $USER->id)) {
    redirect(new core\url('/admin/tool/muprog/my/program.php', ['id' => $program->id]));
}

$title = get_string('source_selfallocation_allocate', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

// Program page redirects back to catalogue if user is not allocated.
$returnurl = new core\url('/admin/tool/muprog/my/program.php', ['id' => $program->id]);

$handler = handler::from_request();

$form = new tool_muprog\local\form\source_selfallocation($PAGE->url, [], ['source' => $source]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    tool_muprog\local\source\selfallocation::signup($program->id, $source->id);
    $handler->submitted($returnurl);
}

$handler->render($form);
