<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Programs management interface.
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

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/source_approval_requests.php', ['id' => $program->id]);

management::setup_program_page($currenturl, $context, $program, 'program_approval_requests');

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \tool_muprog\reportbuilder\local\systemreports\requests::class,
    $context, parameters:['programid' => $program->id]);
echo $report->output();

echo $OUTPUT->footer();
