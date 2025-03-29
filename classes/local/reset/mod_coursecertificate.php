<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\reset;

use stdClass;

/**
 * mod_coursecertificate user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_coursecertificate extends base {
    /**
     * Custom course module reset method.
     *
     * @param stdClass $user
     * @param array $courseids
     * @param array $options
     * @return void
     */
    public static function purge_data(stdClass $user, array $courseids, array $options = []): void {
        global $DB;

        if (!$courseids) {
            return;
        }

        if (!get_config('tool_certificate', 'version')) {
            return;
        }

        list($courses, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $user->id;

        // Archive existing certificates.
        $sql = "UPDATE {tool_certificate_issues}
                   SET archived = 1
                 WHERE component = 'mod_coursecertificate' AND archived = 0
                       AND userid = :userid AND courseid $courses";
        $DB->execute($sql, $params);
    }
}
