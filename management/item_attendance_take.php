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
 * Program offline attendance taking.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\content\attendance;
use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

require('../../../../config.php');

$itemid = required_param('itemid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);

require_login();

$item = $DB->get_record('tool_muprog_item', ['id' => $itemid, 'type' => 'attendance'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $item->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:takeattendance', $context);

$allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $userid], '*', MUST_EXIST);
$user = $DB->get_record('user', ['id' => $allocation->userid, 'deleted' => 0], '*', MUST_EXIST);

$currenturl = new core\url('/admin/tool/muprog/management/item_attendance_take.php', ['itemid' => $item->id, 'userid' => $user->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);
$title = get_string('attendance_take', 'tool_muprog');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$returnurl = new core\url('/admin/tool/muprog/management/allocation.php', ['id' => $allocation->id]);

if ($program->archived || $allocation->archived) {
    redirect($returnurl);
}

$attendance = $DB->get_record('tool_muprog_attendance', ['itemid' => $item->id, 'userid' => $user->id]);
if ($attendance) {
    if ($attendance->status === null) {
        $attendance->status = attendance::STATUS_NOTSET;
    }
    if (!$attendance->timeeffective) {
        $attendance->timeeffective = time();
    }
} else {
    $attendance = (object)[
        'itemid' => $item->id,
        'userid' => $user->id,
        'status' => attendance::STATUS_NOTSET,
        'timeeffective' => time(),
    ];
}

$handler = handler::from_request();

$current = [
    'userfullname' => fullname($user),
    'itemfullname' => $item->fullname,
    'status' => $attendance->status,
    'timeeffective' => $attendance->timeeffective,
];
$form = new \tool_muprog\local\form\item_attendance_take($currenturl, $current);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->itemid = $item->id;
    $data->userid = $user->id;
    attendance::take_attendance($data);
    $handler->submitted($returnurl);
}

$handler->render($form);
