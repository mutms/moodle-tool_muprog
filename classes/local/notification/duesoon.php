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

namespace tool_muprog\local\notification;

use stdClass;

/**
 * Program due soon notification.
 *
 * Meaning of aux fields:
 * - auxint1: number of seconds before the due date when notification is sent, null means default self::TIME_SOON
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class duesoon extends base {
    #[\Override]
    public static function add_update_form_elements(\tool_mulib\muform\form $form, stdClass $notification): void {
        $element = (new \tool_mulib\muform\element\duration(
            'auxint1',
            get_string('notification_duesoon_interval', 'tool_muprog'),
            ['d']
        ))->set_required(true);
        $form->add($element);
    }

    #[\Override]
    public static function prepare_update_form_data(array $current): array {
        if (empty($current['auxint1'])) {
            $current['auxint1'] = self::TIME_SOON;
        }
        return $current;
    }

    #[\Override]
    public static function normalise_aux_data(stdClass $data, ?stdClass $oldrecord): void {
        unset($data->auxjson);
        unset($data->auxint2);

        if (!property_exists($data, 'auxint1')) {
            return;
        }
        if ($data->auxint1 === null || $data->auxint1 === '') {
            // Use default.
            $data->auxint1 = null;
            return;
        }
        $data->auxint1 = (int)$data->auxint1;
        if ($data->auxint1 < DAYSECS) {
            throw new \core\exception\invalid_parameter_exception('Due soon interval must be at least one day');
        }
    }

    #[\Override]
    public static function get_aux_details(stdClass $notification): array {
        return [
            get_string('notification_duesoon_interval', 'tool_muprog') => format_time($notification->auxint1 ?: self::TIME_SOON),
        ];
    }

    /**
     * Send notifications.
     *
     * @param stdClass|null $program
     * @param stdClass|null $user
     * @return void
     */
    public static function notify_users(?stdClass $program, ?stdClass $user): void {
        global $DB;

        $source = null;
        $loadfunction = function (stdClass $allocation) use (&$program, &$source, &$user): void {
            global $DB;
            if (!$source || $source->id != $allocation->sourceid) {
                $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);
            }
            if (!$user || $user->id != $allocation->userid) {
                $user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);
            }
            if (!$program || $program->id != $source->programid) {
                $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid], '*', MUST_EXIST);
            }
        };

        $params = [];
        $programselect = '';
        if ($program) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $program->id;
        }
        $userselect = '';
        if ($user) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $user->id;
        }
        $params['now'] = time();
        $params['now2'] = $params['now'];
        $params['soon'] = self::TIME_SOON;

        $sql = "SELECT pa.*
                  FROM {tool_muprog_allocation} pa
                  JOIN {user} u ON u.id = pa.userid AND u.deleted = 0 AND u.suspended = 0
                  JOIN {tool_muprog_source} s ON s.id = pa.sourceid
                  JOIN {tool_muprog_program} p ON p.id = pa.programid
                  JOIN {tool_mulib_notification} n
                       ON n.component = 'tool_muprog' AND n.notificationtype = 'duesoon' AND n.instanceid = p.id AND n.enabled = 1
             LEFT JOIN {tool_mulib_notification_user} un
                       ON un.notificationid = n.id AND un.userid = pa.userid AND un.otherid1 = pa.id
                 WHERE un.id IS NULL AND p.archived = 0 AND pa.archived = 0
                       $programselect $userselect
                       AND pa.timecompleted IS NULL AND pa.timedue > :now AND pa.timedue < :now2 + COALESCE(n.auxint1, :soon)
              ORDER BY p.id, s.id, pa.userid";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $allocation) {
            $loadfunction($allocation);
            self::notify_allocated_user($program, $source, $allocation, $user);
        }
        $rs->close();
    }
}
