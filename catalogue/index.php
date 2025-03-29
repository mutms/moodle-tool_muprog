<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Program browsing for learners.
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

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$catalogue = new \tool_muprog\local\catalogue($_REQUEST);
$syscontext = context_system::instance();

$PAGE->set_url($catalogue->get_current_url());
$PAGE->set_context($syscontext);

require_login();
require_capability('tool/muprog:viewcatalogue', $syscontext);

if (!enrol_is_enabled('muprog')) {
    redirect(new moodle_url('/'));
}

$buttons = [];
$manageurl = \tool_muprog\local\management::get_management_url();
if ($manageurl) {
    $buttons[] = html_writer::link($manageurl, get_string('management', 'tool_muprog'), ['class' => 'btn btn-secondary']);
}
if (!isguestuser()) {
    $myprogramsurl = new moodle_url('/admin/tool/muprog/my/index.php');
    $buttons[] = html_writer::link($myprogramsurl, get_string('myprograms', 'tool_muprog'), ['class' => 'btn btn-secondary']);
}
$buttons = implode('&nbsp;', $buttons);
$PAGE->set_button($buttons . $PAGE->button);

$PAGE->set_heading(get_string('catalogue', 'tool_muprog'));
$PAGE->set_title(get_string('catalogue', 'tool_muprog'));
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

echo $catalogue->render_programs();

echo $OUTPUT->footer();
