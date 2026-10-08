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
 * Program management interface.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\management;
use tool_mulib\output\header_actions;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new core\url('/admin/tool/muprog/management/program.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_general');
$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_muprog/wiki/Program-settings');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$actions = new header_actions(get_string('management_program_general_actions', 'tool_muprog'));
if (has_capability('tool/muprog:export', $context)) {
    $url = new core\url('/admin/tool/muprog/management/export.php', ['id' => $program->id]);
    $actions->get_dropdown()->add_item(get_string('export', 'tool_muprog'), $url, new \core\output\pix_icon('i/export', ''));
}
$frozen = management::is_program_frozen($program);
if ($program->draft && !$program->archived && !$frozen && has_capability('tool/muprog:edit', $context)) {
    $url = new core\url('/admin/tool/muprog/management/program_release.php', ['id' => $program->id]);
    $button = new tool_mulib\output\muform\dialog\button($url, get_string('program_release', 'tool_muprog'), true);
    $button->set_form_size('sm');
    $actions->add_button($button);
}
if (\tool_muprog\local\operation\base::did_operation_fail($program->id) && has_capability('tool/muprog:admin', $context)) {
    $url = new core\url('/admin/tool/muprog/management/program_operation_dismiss.php', ['id' => $program->id]);
    $button = new tool_mulib\output\muform\dialog\button($url, get_string('program_operation_dismiss', 'tool_muprog'));
    $actions->add_button($button);
}
$candelete = (($program->archived || $program->draft) && !management::is_program_frozen($program, true));
if ($candelete && has_capability('tool/muprog:delete', $context)) {
    $url = new core\url('/admin/tool/muprog/management/program_delete.php', ['id' => $program->id]);
    $link = new tool_mulib\output\muform\dialog\link($url, get_string('program_delete', 'tool_muprog'), 'i/delete');
    $link->add_class('text-danger');
    $link->set_form_size('sm');
    $link->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_REDIRECT);
    $actions->get_dropdown()->add_dialog($link);
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

$buttons = [];
if (!$frozen && has_capability('tool/muprog:edit', $context)) {
    $url = new core\url('/admin/tool/muprog/management/program_update.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\muform\dialog\button($url, get_string('edit'));
    $buttons[] = $OUTPUT->render($editbutton);
}

echo $managementoutput->render_program_general($program);

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
