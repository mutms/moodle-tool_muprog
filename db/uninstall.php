<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Program enrolment uninstallation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Uninstall programs.
 *
 * @return true
 */
function xmldb_tool_muprog_uninstall(): bool {
    global $CFG, $DB;

    $trans = $DB->start_delegated_transaction();

    $DB->delete_records('tool_muprog_usr_snapshot', []);
    $DB->delete_records('tool_muprog_prg_snapshot', []);
    $DB->delete_records('tool_muprog_evidence', []);
    $DB->delete_records('tool_muprog_completion', []);
    $DB->delete_records('tool_muprog_allocation', []);
    $DB->delete_records('tool_muprog_request', []);
    $DB->delete_records('tool_muprog_src_cohort', []);
    $DB->delete_records('tool_muprog_source', []);
    $DB->delete_records('tool_muprog_cohort', []);
    $DB->delete_records('tool_muprog_prerequisite', []);
    $DB->delete_records('tool_muprog_item', []);

    $fs = get_file_storage();
    $contextids = $DB->get_fieldset_sql("SELECT DISTINCT contextid FROM {tool_muprog_program}", []);
    foreach ($contextids as $contextid) {
        $context = context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context) {
            continue;
        }
        $fs->delete_area_files($context->id, 'tool_muprog');
    }

    core_tag_tag::delete_instances('tool_muprog');

    $DB->delete_records('tool_muprog_program', []);

    $trans->allow_commit();

    $program = enrol_get_plugin('muprog');
    $rs = $DB->get_recordset('enrol', ['enrol' => 'muprog']);
    foreach ($rs as $instance) {
        $program->delete_instance($instance);
    }
    $rs->close();

    role_unassign_all(['component' => 'enrol_muprog']);

    return true;
}
