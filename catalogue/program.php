<?php
// This file is part of Programs for Moodle™.
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
/** @var stdClass $USER */

require('../../../../config.php');

$id = required_param('id', PARAM_INT);

$syscontext = context_system::instance();

$PAGE->set_url(new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $id]));
$PAGE->set_context(context_system::instance());

require_login();
require_capability('tool/muprog:viewcatalogue', context_system::instance());

if (!\tool_muprog\local\util::is_muprog_active()) {
    redirect(new moodle_url('/'));
}

$program = $DB->get_record('tool_muprog_program', ['id' => $id]);
if (!$program || $program->archived) {
    if ($program) {
        $context = context::instance_by_id($program->contextid);
    } else {
        $context = context_system::instance();
    }
    if (has_capability('tool/muprog:view', $context)) {
        if ($program) {
            redirect(new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]));
        } else {
            redirect(new moodle_url('/admin/tool/muprog/management/index.php'));
        }
    } else {
        redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
    }
}
$programcontext = context::instance_by_id($program->contextid);

$allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $USER->id]);
if ($allocation && !$allocation->archived) {
    redirect(new moodle_url('/admin/tool/muprog/my/program.php', ['id' => $id]));
}

if (!\tool_muprog\local\catalogue::is_program_visible($program)) {
    if (has_capability('tool/muprog:view', $programcontext)) {
        redirect(new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]));
    } else {
        redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
    }
}

if (has_capability('tool/muprog:view', $programcontext)) {
    $manageurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $button = html_writer::link($manageurl, get_string('management', 'tool_muprog'), ['class' => 'btn btn-secondary']);
    $PAGE->set_button($button . $PAGE->button);
}

/** @var \tool_muprog\output\catalogue\renderer $catalogueoutput */
$catalogueoutput = $PAGE->get_renderer('tool_muprog', 'catalogue');

$PAGE->set_heading(get_string('catalogue', 'tool_muprog'));
$PAGE->navigation->override_active_url(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
$PAGE->set_title(get_string('catalogue', 'tool_muprog'));
$PAGE->navbar->add(format_string($program->fullname));

echo $OUTPUT->header();

\tool_muprog\event\catalogue_program_viewed::create_from_program($program)->trigger();

echo $catalogueoutput->render_program($program);

echo $OUTPUT->footer();
