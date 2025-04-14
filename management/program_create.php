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
 * Add program.
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

use tool_muprog\local\program;
use tool_muprog\local\management;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$contextid = required_param('contextid', PARAM_INT);
$context = context::instance_by_id($contextid);

require_login();
require_capability('tool/muprog:edit', $context);

if ($context->contextlevel != CONTEXT_SYSTEM && $context->contextlevel != CONTEXT_COURSECAT) {
    throw new moodle_exception('invalidcontext');
}

$currenturl = new moodle_url('/admin/tool/muprog/management/program_create.php', ['contextid' => $context->id]);
management::setup_index_page($currenturl, $context);

$program = new stdClass();
$program->contextid = $context->id;
$program->fullname = '';
$program->idnumber = '';
$program->creategroups = 0;
$program->description = '';
$program->descriptionformat = FORMAT_HTML;

$editoroptions = program::get_description_editor_options($context->id);

$form = new \tool_muprog\local\form\program_create(null, ['data' => $program, 'editoroptions' => $editoroptions]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]));
}

if ($data = $form->get_data()) {
    $program = program::create($data);
    $returlurl = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
    $form->redirect_submitted($returlurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('program_create', 'tool_muprog'));

echo $form->render();

echo $OUTPUT->footer();
