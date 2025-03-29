<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\event;

/**
 * Program viewed event.
 *
 * NOTE: this is learner view in catalogue only, management UI does not trigger this.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_viewed extends \core\event\base {
    /**
     * Helper for event creation.
     *
     * @param \stdClass $program
     *
     * @return program_viewed|static
     */
    public static function create_from_program(\stdClass $program) {
        $context = \context::instance_by_id($program->contextid);
        $data = [
            'context' => $context,
            'objectid' => $program->id,
        ];
        /** @var static $event */
        $event = self::create($data);
        $event->add_record_snapshot('tool_muprog_program', $program);
        return $event;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed program with id '$this->objectid'";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_program_viewed', 'tool_muprog');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $this->objectid]);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'tool_muprog_program';
    }
}
