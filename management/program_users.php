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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$report = \tool_muprog\mureport\system_report\program_allocations::make_instance($id);
$report->require_access();
$program = $report->get_constraint_record('tool_muprog_program', 'programid');
$context = $report->get_context();

$currenturl = new core\url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

management::setup_program_page($currenturl, $context, $program, 'program_users');
$report->init_page($currenturl);
$PAGE->set_docs_path('https://github.com/mutms/moodle-tool_muprog/wiki/Program-users');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id]);
/** @var \tool_muprog\local\source\base[] $sourceclasses */ // Type hack.
$sourceclasses = \tool_muprog\local\allocation::get_source_classes();

$actions = new \tool_mulib\output\header_actions(get_string('management_program_users_actions', 'tool_muprog'));
foreach ($sourceclasses as $sourceclass) {
    $sourcetype = $sourceclass::get_type();
    $sourcerecord = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => $sourcetype]);
    if (!$sourcerecord) {
        continue;
    }
    $sourceclass::add_management_program_users_actions($actions, $program, $sourcerecord);
}
$canmanageevidence = has_capability('tool/muprog:manageevidence', $context);
$totalcount = $DB->count_records('tool_muprog_allocation', ['programid' => $program->id]);
if ($totalcount && !$program->archived && $canmanageevidence) {
    $url = new \core\url('/admin/tool/muprog/management/program_evidence_upload.php', ['programid' => $id]);
    $actions->get_dropdown()->add_item(
        get_string('evidenceupload', 'tool_muprog'),
        $url,
        new \core\output\pix_icon('i/upload', '')
    );
}
if ($actions->has_items()) {
    $PAGE->add_header_action($OUTPUT->render($actions));
}

echo $OUTPUT->header();

echo $OUTPUT->render(new \tool_mureport\output\report_full($report));

echo $OUTPUT->footer();
