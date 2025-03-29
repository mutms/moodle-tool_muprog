<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\task;

/**
 * Program certificate issuing task.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate extends \core\task\scheduled_task {

    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskcertificate', 'tool_muprog');
    }

    /**
     * Run task for all program cron stuff.
     */
    public function execute() {
        if (!enrol_is_enabled('muprog')) {
            return;
        }

        \tool_muprog\local\certificate::cron();
    }
}
