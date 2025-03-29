<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Programs management interface - certificate awarding.
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

require('../../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$id = required_param('id', PARAM_INT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:view', $context);

if (!\tool_muprog\local\certificate::is_available()) {
    redirect(new moodle_url('/admin/tool/muprog/program.php', ['id' => $program->id]));
}

$currenturl = new moodle_url('/admin/tool/muprog/management/program_certificate.php', ['id' => $id]);

management::setup_program_page($currenturl, $context, $program, 'program_certificate');

/** @var \tool_muprog\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('tool_muprog', 'management');

$cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);

echo $OUTPUT->header();

$buttons = [];
if (has_capability('tool/muprog:edit', $context)) {
    $editurl = new moodle_url('/admin/tool/muprog/management/program_certificate_edit.php', ['id' => $program->id]);
    $editbutton = new tool_mulib\output\dialog_form\button($editurl, get_string('edit'));
    $editbutton->set_dialog_name(get_string('certificate', 'tool_certificate'));
    $buttons[] = $OUTPUT->render($editbutton);

    if ($cert) {
        $deleteurl = new moodle_url('/admin/tool/muprog/management/program_certificate_delete.php', ['id' => $program->id]);
        $deletebutton = new tool_mulib\output\dialog_form\button($deleteurl, get_string('delete'));
        $deletebutton->set_dialog_name(get_string('certificate', 'tool_certificate'));
        $buttons[] = $OUTPUT->render($deletebutton);
    }
}

$cert = $DB->get_record('tool_muprog_cert', ['programid' => $program->id]);

if ($cert) {
    echo '<dl class="row">';
    echo '<dt class="col-3">' . get_string('certificatetemplate', 'tool_certificate') . '</dt><dd class="col-9">';
    $template = $DB->get_record('tool_certificate_templates', ['id' => $cert->templateid]);
    if (!$template) {
        echo get_string('error');
        echo '</dd>';
        echo '</dl>';
    } else {
        echo format_string($template->name);
        echo '</dd>';

        echo '<dt class="col-3">' . get_string('expirydate', 'tool_certificate') . '</dt><dd class="col-9">';
        if ($cert->expirydatetype == 1) {
            echo userdate($cert->expirydateoffset);
        } else if ($cert->expirydatetype == 2) {
            echo format_time($cert->expirydateoffset);
        } else {
            echo get_string('never', 'tool_certificate');
        }
        echo '</dd>';

        echo '</dl>';
    }
} else {
    echo '<dl class="row">';
    echo '<dt class="col-3">' . get_string('certificatetemplate', 'tool_certificate') . '</dt><dd class="col-9">';
    echo get_string('notset', 'tool_muprog');
    echo '</dd>';
    echo '</dl>';
}

if ($buttons) {
    $buttons = implode(' ', $buttons);
    echo $OUTPUT->box($buttons, 'buttons');
}

echo $OUTPUT->footer();
