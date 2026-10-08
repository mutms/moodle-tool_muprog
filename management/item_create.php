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
 * Create a new program item.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */

use tool_muprog\local\program;
use tool_mulib\muform\form;
use tool_mulib\muform\handler;
use core\url;

require('../../../../config.php');

require_login();

$parentid = required_param('parentid', PARAM_INT);
$type = optional_param('type', '', PARAM_ALPHANUM);

$parentrecord = $DB->get_record('tool_muprog_item', ['id' => $parentid], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $parentrecord->programid], '*', MUST_EXIST);

$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);
if ($program->archived) {
    require_capability('tool/muprog:admin', $context);
}
\tool_muprog\local\management::require_program_not_frozen($program);

$PAGE->set_context($context);

$returnurl = new url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

$top = program::load_content($program->id);
$parent = $top->find_item($parentrecord->id);

if ($parent::get_type() !== 'set' && $parent::get_type() !== 'top') {
    throw new \core\exception\invalid_parameter_exception('parent must be a set');
}

$types = $top::get_types();
unset($types['top']);
if (!\tool_mulib\local\mulib::is_mutrain_active()) {
    unset($types['credits']);
}
if (!isset($types[$type])) {
    $type = '';
}

$pageurl = new url('/admin/tool/muprog/management/item_create.php', ['parentid' => $parentrecord->id]);
if ($type) {
    $currenturl = new url($pageurl, ['type' => $type]);
    $title = get_string('item_create_' . $type, 'tool_muprog');
} else {
    $currenturl = $pageurl;
    $title = get_string('appenditem', 'tool_muprog');
}
$PAGE->set_url($currenturl);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$handler = handler::from_request();

$createform = function (string $type, url $formurl) use ($types, $program): form {
    $current = [
        'typename' => (string)$types[$type],
        'points' => 1,
    ];
    $extra = ['programid' => (int)$program->id];
    if ($type === 'set') {
        $current['sequencetype'] = \tool_muprog\local\content\set::SEQUENCE_TYPE_ALLINANYORDER;
        $current['minprerequisites'] = 1;
        $current['minpoints'] = 1;
        return new \tool_muprog\local\form\item_create_set($formurl, $current, $extra);
    } else if ($type === 'course') {
        return new \tool_muprog\local\form\item_create_course($formurl, $current, $extra);
    } else if ($type === 'attendance') {
        return new \tool_muprog\local\form\item_create_attendance($formurl, $current, $extra);
    } else if ($type === 'credits') {
        return new \tool_muprog\local\form\item_create_credits($formurl, $current, $extra);
    }
    throw new \core\exception\coding_exception('Unknown item type');
};

if (!$type) {
    $form = new \tool_muprog\local\form\item_create($currenturl, [], ['types' => $types]);
    if ($form->is_cancelled()) {
        $handler->cancelled($returnurl);
    }
    if ($data = $form->get_data()) {
        $nexturl = new url($pageurl, ['type' => $data->type]);
        if ($handler->is_dialog()) {
            // The dialog continues with the second step, the page gets it from the next URL.
            $handler->render(
                $createform($data->type, $nexturl),
                get_string('item_create_' . $data->type, 'tool_muprog')
            );
        }
        redirect($nexturl);
    }
    $handler->render($form);
}

$form = $createform($type, $currenturl);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    if ($type === 'set') {
        $set = $top->append_set($parent, (array)$data);
    } else if ($type === 'course') {
        $courseids = $data->courseids;
        unset($data->courseids);
        foreach ($courseids as $courseid) {
            $top->append_course($parent, (int)$courseid, (array)$data);
        }
    } else if ($type === 'attendance') {
        $top->append_attendance($parent, (array)$data);
    } else if ($type === 'credits') {
        $creditframeworkid = (int)$data->creditframeworkid;
        unset($data->creditframeworkid);
        $top->append_credits($parent, $creditframeworkid, (array)$data);
    }
    $handler->submitted($returnurl);
}

$handler->render($form);
