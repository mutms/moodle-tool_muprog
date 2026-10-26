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
 * Upload or copy/paste user allocations to program via CSV format.
 *
 * The page walks through three stages: source, column meaning, options with a dry run.
 * The parsed rows and the answers live in the muform wizard state between stages, the
 * current stage is always decided from that state.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\form\source_manual_upload_columns;
use tool_muprog\local\form\source_manual_upload_options;
use tool_muprog\local\form\source_manual_upload_source;
use tool_muprog\local\management;
use tool_muprog\local\source\manual;
use tool_muprog\local\source\manual_upload;
use tool_mulib\muform\wizard;

// phpcs:disable moodle.Commenting.InlineComment.TypeHintingMatch
/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
// phpcs:enable moodle.Commenting.InlineComment.TypeHintingMatch

require('../../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

require_login();

$source = $DB->get_record('tool_muprog_source', ['id' => $sourceid, 'type' => 'manual'], '*', MUST_EXIST);
$program = $DB->get_record('tool_muprog_program', ['id' => $source->programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:allocate', $context);

$pageurl = new core\url('/admin/tool/muprog/management/source_manual_upload.php', ['sourceid' => $source->id]);
management::setup_program_page($pageurl, $context, $program, 'program_users');
$title = get_string('source_manual_uploadusers', 'tool_muprog');
$PAGE->navbar->add($title);

$returnurl = new core\url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);

if (!manual::is_allocation_possible($program, $source)) {
    redirect($returnurl);
}

// The state belongs to this allocation source, it cannot be replayed against another one.
$wizardname = 'tool_muprog_source_manual_upload:' . $source->id;
$wizard = wizard::load($wizardname, optional_param(wizard::PARAM, 0, PARAM_INT));
if (!$wizard) {
    $wizard = wizard::start($wizardname);
    redirect($wizard->get_url($pageurl));
}

// The stored state is validated again on every request, each stage fills one part of it.
$state = $wizard->get_data();
$csvdata = manual_upload::validate($state);
$sourcedata = is_array($state['source'] ?? null) ? $state['source'] : [];

$labels = [
    'source' => get_string('source_manual_upload_source', 'tool_muprog'),
    'columns' => get_string('source_manual_upload_columns', 'tool_muprog'),
    'options' => get_string('source_manual_upload_options', 'tool_muprog'),
];
$isvalid = fn(string $stage): bool => match ($stage) {
    'source' => !manual_upload::is_source_stage($csvdata),
    'columns' => !manual_upload::is_columns_stage($csvdata),
    'options' => !manual_upload::is_options_stage($csvdata),
};
$requested = optional_param(wizard::STAGE_PARAM, null, PARAM_ALPHA);
$stages = wizard::resolve_stages($labels, $isvalid, $requested);
$stage = wizard::current_stage($stages);

if ($requested === null && $isvalid('options')) {
    // Everything was answered, allocate the users.
    $counts = manual_upload::import($program, $source, $csvdata, (object)$csvdata->options);
    $wizard->delete();
    if ($counts[manual_upload::RESULT_ERROR]) {
        \core\notification::error(get_string('source_manual_upload_failed', 'tool_muprog', (object)$counts));
    } else {
        \core\notification::success(get_string('source_manual_upload_done', 'tool_muprog', (object)$counts));
    }
    redirect($returnurl);
}

$formurl = $wizard->get_url($pageurl, $stage);
if ($stage === 'source') {
    $form = new source_manual_upload_source($formurl, $sourcedata);
} else if ($stage === 'columns') {
    $current = [];
    if ($csvdata->columns) {
        $current['headers'] = (int)$csvdata->columns['headers'];
        foreach ($csvdata->columns['map'] as $index => $value) {
            $current['column_' . $index] = $value;
        }
    }
    $form = new source_manual_upload_columns($formurl, $current, ['rows' => $csvdata->rows]);
} else {
    // Options of an earlier confirmation are only defaults of the form.
    $form = new source_manual_upload_options($formurl, $csvdata->options, ['program' => $program, 'csvdata' => $csvdata]);
}

if ($form->is_cancelled()) {
    $wizard->delete();
    redirect($returnurl);
}
if ($stage !== 'source' && $form->is_reloaded() && $form->get_element('back')->get_value()) {
    redirect($wizard->get_url($pageurl, $stage === 'options' ? 'columns' : 'source'));
}
if ($data = $form->get_data()) {
    if ($stage === 'source') {
        $csvdata = manual_upload::save_source($form->get_source_content(), $data->encoding, $data->delimiter_name);
        $sourcedata = [
            'sourcefile' => (int)$data->sourcefile,
            'csvtext' => $data->csvtext,
            'encoding' => $data->encoding,
            'delimiter_name' => $data->delimiter_name,
        ];
    } else if ($stage === 'columns') {
        $csvdata = manual_upload::save_columns($csvdata, $data);
    } else {
        $csvdata = manual_upload::save_options($csvdata, $data);
    }
    $wizard->set_data((array)$csvdata + ['source' => $sourcedata]);
    redirect($wizard->get_url($pageurl));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title, 2, 'mb-3');
echo $wizard->render($OUTPUT, $stages, $pageurl, $form);
echo $OUTPUT->footer();
