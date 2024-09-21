<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace enrol_programs\hook;

/**
 * Program courses completions purged for user.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('openlms')]
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
