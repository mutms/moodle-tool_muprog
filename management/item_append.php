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
use tool_muprog\local\program;
use tool_muprog\local\content\set;

// phpcs:ignoreFile moodle.Files.MoodleInternal.MoodleInternalGlobalState
if (!empty($_SERVER['HTTP_X_MULIB_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}
require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$parentitemid = required_param('parentitemid', PARAM_INT);

require_login();

$parentitem = $DB->get_record('tool_muprog_item', ['id' => $parentitemid], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $parentitem->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new moodle_url('/admin/tool/muprog/management/item_append.php', ['parentitemid' => $parentitem->id]);
management::setup_program_page($currenturl, $context, $program, 'program_content');

$returnurl = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);

if ($program->archived) {
    redirect($returnurl);
}

$top = program::load_content($program->id);
$set = $top->find_item($parentitem->id);
if (!$set || !($set instanceof \tool_muprog\local\content\set)) {
    redirect($returnurl);
}

$form = new \tool_muprog\local\form\item_append(null, ['parentset' => $set, 'context' => $context]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if ($data->addset) {
        $set = $top->append_set($set, (array)$data);
    }
    foreach ($data->courses as $cid) {
        $coursecontext = context_course::instance($cid);
        require_capability('tool/muprog:addcourse', $coursecontext);
        $idata = ['points' => $data->points];
        if (!empty($data->completiondelay)) {
            $idata['completiondelay'] = $data->completiondelay;
        }
        $top->append_course($set, $cid, $idata);
    }
    if (!empty($data->trainingid)) {
        $idata = ['points' => $data->points];
        if (!empty($data->completiondelay)) {
            $idata['completiondelay'] = $data->completiondelay;
        }
        $top->append_training($set, $data->trainingid, $idata);
    }

    $form->redirect_submitted($returnurl);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('appenditem', 'tool_muprog'), 3);

echo $form->render();

echo $OUTPUT->footer();
