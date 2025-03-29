<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Configuration for program custom fields.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var stdClass $CFG */
/** @var moodle_page $PAGE */

require('../../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('tool_muprog_customfield');

/** @var \core_customfield\output\renderer $output */
$output = $PAGE->get_renderer('core_customfield');

$handler = \tool_muprog\customfield\fields_handler::create();
$outputpage = new \core_customfield\output\management($handler);

echo $output->header(),
$output->heading(new lang_string('customfields', 'tool_muprog')),
$output->render($outputpage),
$output->footer();
