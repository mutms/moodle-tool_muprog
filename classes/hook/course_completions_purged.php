<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\hook;

/**
 * Program courses completions purged for user.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\label('Notification that completion of courses in programs was purged for given user')]
final class course_completions_purged {
    /** @var int user id */
    public $userid;
    /** @var int program id */
    public $programid;

    /**
     * Create hook for course completion purge.
     *
     * @param int $userid
     * @param int $programid
     */
    public function __construct(int $userid, int $programid) {
        $this->userid = $userid;
        $this->programid = $programid;
    }
}
