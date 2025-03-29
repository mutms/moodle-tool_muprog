<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\reset;

use stdClass;

/**
 * mod_h5pactivity user data reset
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_h5pactivity extends base {
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

        list($courses, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $params['userid'] = $user->id;

        $sql = "DELETE
                  FROM {h5pactivity_attempts_results}
                 WHERE attemptid IN (
                    SELECT a.id
                      FROM {h5pactivity_attempts} a
                      JOIN {h5pactivity} h ON h.id = a.h5pactivityid AND h.course $courses
                     WHERE a.userid = :userid)";
        $DB->execute($sql, $params);

        $sql = "DELETE
                  FROM {h5pactivity_attempts}
                 WHERE userid = :userid AND h5pactivityid IN (
                    SELECT h.id
                      FROM {h5pactivity} h WHERE h.course $courses)";
        $DB->execute($sql, $params);
    }
}
