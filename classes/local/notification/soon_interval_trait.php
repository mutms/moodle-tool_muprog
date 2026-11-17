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
 * Configurable interval of notifications sent ahead of a date.
 *
 * The interval is stored in auxint1 as a number of seconds, null means default self::TIME_SOON.
 * The label is the lang string notification_TYPE_interval.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait soon_interval_trait {
    /**
     * Add interval element to notification update form.
     *
     * @param \tool_mulib\muform\form $form
     * @param stdClass $notification current notification data
     * @return void
     */
    #[\Override]
    public static function add_update_form_elements(\tool_mulib\muform\form $form, stdClass $notification): void {
        $element = (new \tool_mulib\muform\element\duration(
            'auxint1',
            get_string('notification_' . static::get_notificationtype() . '_interval', 'tool_muprog'),
            ['d']
        ))->set_required(true);
        $form->add($element);
    }

    /**
     * Show default interval in notification update form when not set.
     *
     * @param array $current notification record with subject and body
     * @return array
     */
    #[\Override]
    public static function prepare_update_form_data(array $current): array {
        if (empty($current['auxint1'])) {
            $current['auxint1'] = self::TIME_SOON;
        }
        return $current;
    }

    /**
     * Validate and normalise the interval before notification is created or updated.
     *
     * @param stdClass $data data to be stored
     * @param stdClass|null $oldrecord current notification record, null when creating
     * @return void
     */
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
            throw new \core\exception\invalid_parameter_exception('Notification interval must be at least one day');
        }
    }

    /**
     * Returns the interval to be displayed on notification details page.
     *
     * @param stdClass $notification
     * @return string[] HTML values indexed with plain text labels
     */
    #[\Override]
    public static function get_aux_details(stdClass $notification): array {
        return [
            get_string('notification_' . static::get_notificationtype() . '_interval', 'tool_muprog') => format_time($notification->auxint1 ?: self::TIME_SOON),
        ];
    }
}
