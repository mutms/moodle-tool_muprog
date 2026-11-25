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

/**
 * My programs.
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
/** @var stdClass $USER */

require('../../../../config.php');

$userid = optional_param('userid', 0, PARAM_INT);

if (!\tool_mulib\local\mulib::is_muprog_active()) {
    redirect(new core\url('/'));
}

$currenturl = new core\url('/admin/tool/muprog/my/index.php');
if ($userid) {
    $currenturl->param('userid', $userid);
} else {
    require_login();
    if (isguestuser()) {
        redirect(new core\url('/'));
    }
    $userid = (int)$USER->id;
}

$report = \tool_muprog\mureport\system_report\user_programs::make_instance($userid);
$report->require_access();
$report->init_page($currenturl);
$user = $report->get_constraint_record('user', 'userid');

if ($userid != $USER->id) {
    $title = get_string('programs', 'tool_muprog');
} else {
    $title = get_string('myprograms', 'tool_muprog');
}

$PAGE->navigation->extend_for_user($user);
$PAGE->set_title($title);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new core\url('/user/profile.php', ['id' => $user->id]));
$PAGE->navbar->add($title);

$actions = new \tool_mulib\output\header_actions(get_string('program_actions', 'tool_muprog'));

$manageurl = \tool_muprog\local\management::get_management_url();
if ($manageurl) {
    $actions->get_dropdown()->add_item(get_string('management', 'tool_muprog'), $manageurl);
}
$catalogueurl = \tool_mucatalog\local\catalogue::get_catalogue_url();
if ($catalogueurl) {
    $button = html_writer::link($catalogueurl, get_string('catalogue', 'tool_mucatalog'), ['class' => 'btn btn-secondary']);
    $actions->add_button($button);
}

if ($actions->has_items()) {
    $PAGE->set_button($PAGE->button . $OUTPUT->render($actions));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $OUTPUT->render(new \tool_mureport\output\report_full($report));

echo $OUTPUT->footer();
