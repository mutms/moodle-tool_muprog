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
// phpcs:disable moodle.Files.RequireLogin.Missing
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

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use tool_muprog\local\management;

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$report = \tool_muprog\mureport\system_report\program_requests::make_instance($id);
$report->require_access();
$program = $report->get_constraint_record('tool_muprog_program', 'programid');
$context = $report->get_context();

$currenturl = new core\url('/admin/tool/muprog/management/source_approval_requests.php', ['id' => $program->id]);

management::setup_program_page($currenturl, $context, $program, 'program_approval_requests');
$report->init_page($currenturl);

echo $OUTPUT->header();

echo $OUTPUT->render(new \tool_mureport\output\report_full($report));

echo $OUTPUT->footer();
