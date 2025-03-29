<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\notification;

use stdClass;

/**
 * Program reset notification.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class reset extends base {
    /**
     * Send notifications.
     *
     * @param stdClass|null $program
     * @param stdClass|null $user
     * @return void
     */
    public static function notify_users(?stdClass $program, ?stdClass $user): void {
        // Nothing to do, we notify during allocation.
    }

    /**
     * Send notifications related to allocation.
     *
     * @param stdClass $user
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return void
     */
    public static function notify_now(stdClass $user, stdClass $program, stdClass $source, stdClass $allocation): void {
        self::notify_allocated_user($program, $source, $allocation, $user);
    }
}
