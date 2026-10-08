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
 * Update a program item.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

use tool_muprog\local\program;
use core\url;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;
use tool_muprog\local\content\course;
use tool_muprog\local\content\attendance;
use tool_muprog\local\content\credits;
use tool_mulib\muform\handler;

require('../../../../config.php');

require_login();

$id = required_param('id', PARAM_INT);

$record = $DB->get_record('tool_muprog_item', ['id' => $id], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);
if ($program->archived) {
    require_capability('tool/muprog:admin', $context);
}
\tool_muprog\local\management::require_program_not_frozen($program);

$currenturl = new url('/admin/tool/muprog/management/item_update.php', ['id' => $record->id]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

$top = program::load_content($program->id);
/** @var set|course|attendance|credits $item */
$item = $top->find_item($record->id);

$type = $item::get_type();
if ($type === 'set' || $type === 'top') {
    $title = get_string('updateset', 'tool_muprog');
} else if ($type === 'course') {
    $title = get_string('updatecourse', 'tool_muprog');
} else if ($type === 'attendance') {
    $title = get_string('updateattendance', 'tool_muprog');
} else if ($type === 'credits') {
    $title = get_string('updatecredits', 'tool_muprog');
} else {
    throw new \core\exception\coding_exception('Unknown item type');
}
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$current = [
    'typename' => $item::get_type_name(),
    'fullname' => $record->fullname,
    'completiondelay' => $item->get_completiondelay(),
    'points' => $item->get_points(),
];
if ($type === 'set' || $type === 'top') {
    $current['sequencetype'] = $item->get_sequencetype();
    if ($item->get_sequencetype() === set::SEQUENCE_TYPE_ATLEAST) {
        $minprerequisites = $item->get_minprerequisites();
    } else {
        $minprerequisites = count($item->get_children());
    }
    if ($item->get_sequencetype() === set::SEQUENCE_TYPE_MINPOINTS) {
        $minpoints = $item->get_minpoints();
    } else {
        $minpoints = 0;
        foreach ($item->get_children() as $child) {
            $minpoints += $child->get_points();
        }
    }
    // Zero is not a valid minimum, leave the hidden fields empty instead.
    $current['minprerequisites'] = $minprerequisites ?: null;
    $current['minpoints'] = $minpoints ?: null;
    $form = new tool_muprog\local\form\item_update_set($currenturl, $current, ['istop' => ($item instanceof top)]);
} else if ($type === 'course') {
    $form = new tool_muprog\local\form\item_update_course($currenturl, $current);
} else if ($type === 'attendance') {
    $form = new tool_muprog\local\form\item_update_attendance($currenturl, $current);
} else {
    $form = new tool_muprog\local\form\item_update_credits($currenturl, $current);
}

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if ($type === 'set' || $type === 'top') {
        $top->update_set($item, (array)$data);
    } else if ($type === 'course') {
        $top->update_course($item, (array)$data);
    } else if ($type === 'attendance') {
        $top->update_attendance($item, (array)$data);
    } else if ($type === 'credits') {
        $top->update_credits($item, (array)$data);
    }
    $handler->submitted($returnurl);
}

$handler->render($form);
