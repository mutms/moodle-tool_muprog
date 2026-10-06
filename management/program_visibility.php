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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new core\url('/admin/tool/muprog/management/program_visibility.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_visibility');
$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_muprog/wiki/Program-visibility');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$actions = new \tool_mulib\output\header_actions(get_string('management_actions', 'tool_mucatalog'));
$sectionsurl = \tool_mucatalog\local\management::get_sections_management_url($context);
if ($sectionsurl) {
    $actions->get_dropdown()->add_item(get_string('management_sections', 'tool_mucatalog'), $sectionsurl, new \core\output\pix_icon('i/menubars', ''));
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $managementoutput->render_program_visibility($program);

if ($program->draft) {
    echo $OUTPUT->notification(get_string('draft_cataloguenotice', 'tool_muprog'), \core\output\notification::NOTIFY_INFO, false);
}

$addbutton = \tool_mucatalog\local\management::get_reference_add_button('program', $program->id, $currenturl);
if ($addbutton) {
    echo $OUTPUT->box($OUTPUT->render($addbutton), 'buttons');
}

echo $OUTPUT->footer();
