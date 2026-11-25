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
// phpcs:disable moodle.Files.RequireLogin.Missing

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

$contextid = optional_param('contextid', 0, PARAM_INT);

$report = \tool_muprog\mureport\system_report\programs::make_instance($contextid ?: context_system::instance()->id);
$report->require_access();
$context = $report->get_context();

$currenturl = new core\url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);

management::setup_index_page($currenturl, $context);
$report->init_page($currenturl);
$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_muprog/wiki/Program-management');

$actions = new header_actions(get_string('management_index_actions', 'tool_muprog'));

if (has_capability('tool/muprog:edit', $context)) {
    $url = new core\url('/admin/tool/muprog/management/program_create.php', ['contextid' => $context->id]);
    $button = new tool_mulib\output\muform\dialog\button($url, get_string('program_create', 'tool_muprog'));
    $button->set_submitted_action(\tool_mulib\muform\handler\dialog::ACTION_REDIRECT);
    $actions->add_button($button);
}
if (has_capability('tool/muprog:export', $context)) {
    $url = new core\url('/admin/tool/muprog/management/export.php', ['contextid' => $contextid]);
    $actions->get_dropdown()->add_item(get_string('export', 'tool_muprog'), $url, new \core\output\pix_icon('i/export', ''));
}
if (has_capability('tool/muprog:upload', $context)) {
    $url = new core\url('/admin/tool/muprog/management/upload.php', ['contextid' => $contextid]);
    $actions->get_dropdown()->add_item(get_string('upload', 'tool_muprog'), $url, new \core\output\pix_icon('i/upload', ''));
}

if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $OUTPUT->render(new \tool_mureport\output\report_full($report));

echo $OUTPUT->footer();
