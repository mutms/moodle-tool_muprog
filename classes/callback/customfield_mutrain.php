<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\callback;

/**
 * Hook callbacks from customfield_mutrain related code.
 *
 * @package    tool_muprog
 * @copyright  2025 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class customfield_mutrain {
    /**
     * Callback to discover training framework usage.
     *
     * @param \customfield_mutrain\hook\framework_usage $hook
     * @return void
     */
    public static function framework_usage(\customfield_mutrain\hook\framework_usage $hook): void {
        global $DB;

        $count = $DB->count_records('tool_muprog_item', ['trainingid' => $hook->get_frameworkid()]);
        if ($count) {
            $hook->add_usage($count);
        }
    }

    /**
     * Callback to announce new completions relevant to framework and user.
     *
     * @param \customfield_mutrain\hook\completion_updated $hook
     * @return void
     */
    public static function completion_updated(\customfield_mutrain\hook\completion_updated $hook): void {
        global $DB;

        list($fselect, $params) = $DB->get_in_or_equal($hook->get_frameworkids(), SQL_PARAMS_NAMED);
        $fselect = "pi.trainingid $fselect";
        $params['userid'] = $hook->get_userid();

        $sql = "SELECT DISTINCT pi.programid
                  FROM {tool_muprog_item} pi
                  JOIN {tool_muprog_allocation} pa ON pa.programid = pi.programid
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                 WHERE pa.userid = :userid AND $fselect
                       AND p.archived = 0 AND pa.archived = 0
              ORDER BY pi.programid";
        $programids = $DB->get_fieldset_sql($sql, $params);

        if (!$programids) {
            return;
        }
        if (count($programids) > 1) {
            \tool_muprog\local\allocation::fix_user_enrolments(null, $hook->get_userid());
        } else {
            $programid = reset($programids);
            \tool_muprog\local\allocation::fix_user_enrolments($programid, $hook->get_userid());
        }
    }
}
