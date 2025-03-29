<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\notification;

use stdClass;

/**
 * Program de-allocation notification.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class deallocation extends base {
    /**
     * Send notifications.
     *
     * @param stdClass|null $program
     * @param stdClass|null $user
     * @return void
     */
    public static function notify_users(?stdClass $program, ?stdClass $user): void {
        // We notify during de-allocation and then delete all notifications,
        // this cannot be triggered from cron later.
    }

    /**
     * Returns program de-allocation placeholders.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @param stdClass $user
     * @return array
     */
    public static function get_allocation_placeholders(stdClass $program, stdClass $source, stdClass $allocation,
                                                       stdClass $user): array {
        $a = parent::get_allocation_placeholders($program, $source, $allocation, $user);
        $context = \context::instance_by_id($program->contextid);
        if (has_capability('tool/muprog:view', $context)) {
            $a['program_url'] = (new \moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]))->out(false);
        } else {
            $a['program_url'] = (new \moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]))->out(false);
        }
        return $a;
    }

    /**
     * Notify users about de-allocation.
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
