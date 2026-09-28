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

use tool_mulib\muform\handler;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

require('../../../../config.php');

$programid = required_param('programid', PARAM_INT);
$type = required_param('type', PARAM_ALPHANUMEXT);

require_login();

$program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
$source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => $type]);
$context = context::instance_by_id($program->contextid);
require_capability('tool/muprog:edit', $context);

$currenturl = new core\url('/admin/tool/muprog/management/program_source_edit.php', ['programid' => $program->id, 'type' => $type]);
$PAGE->set_context($context);
$PAGE->set_url($currenturl);

$returnurl = new core\url('/admin/tool/muprog/management/program_allocation.php', ['id' => $program->id]);

/** @var \tool_muprog\local\source\base[] $sourceclasses */
$sourceclasses = \tool_muprog\local\allocation::get_source_classes();
if (!isset($sourceclasses[$type])) {
    throw new coding_exception('Invalid source type');
}
$sourceclass = $sourceclasses[$type];

$title = get_string('updatesource', 'tool_muprog', $sourceclass::get_name());
$PAGE->set_title($title);
$PAGE->set_heading($title);

if ($source) {
    if (!$sourceclass::is_update_allowed($program)) {
        redirect($returnurl);
    }
    $source->enable = 1;
    $source->hasallocations = $DB->record_exists('tool_muprog_allocation', ['sourceid' => $source->id]);
} else {
    if (!$sourceclass::is_new_allowed($program)) {
        redirect($returnurl);
    }
    $source = new stdClass();
    $source->id = null;
    $source->type = $type;
    $source->programid = $program->id;
    $source->enable = 0;
    $source->hasallocations = false;
}
$source = $sourceclass::decode_datajson($source);

$handler = handler::from_request();

/** @var \tool_mulib\muform\form $formclass */
$formclass = $sourceclass::get_edit_form_class();
$form = new $formclass($currenturl, $source, ['source' => $source, 'program' => $program]);

if ($form->is_cancelled()) {
    $handler->cancelled($returnurl);
}

if ($data = $form->get_data()) {
    $data->programid = $program->id;
    $data->type = $type;
    if (property_exists($data, 'selfallocation_key') && $data->selfallocation_key === null) {
        // Shared keys are not submitted unless changed.
        $data->selfallocation_key = $source->selfallocation_key;
    }
    tool_muprog\local\source\base::update_source($data);
    $handler->submitted($returnurl);
}

$handler->render($form);
