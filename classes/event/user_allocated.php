<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\event;

/**
 * User allocated event.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_allocated extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $allocation
     * @param \stdClass $program
     *
     * @return user_allocated|static
     */
    public static function create_from_allocation(\stdClass $allocation, \stdClass $program) {
        $context = \context::instance_by_id($program->contextid);
        $data = [
            'context' => $context,
            'objectid' => $allocation->id,
            'relateduserid' => $allocation->userid,
            'other' => ['programid' => $program->id],
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_muprog_allocation', $allocation);
        $event->add_record_snapshot('tool_muprog_program', $program);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->relateduserid' was allocated to program with id '$this->objectid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_user_allocated', 'tool_muprog');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/muprog/management/user_allocation.php', ['id' => $this->objectid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_muprog_allocation';
    }
}
