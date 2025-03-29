<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\navigation\views;

use tool_muprog\local\allocation;
use stdClass;
use moodle_url;

/**
 * Program page secondary menu.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_secondary extends \core\navigation\views\secondary {
    /** @var stdClass */
    protected $program;

    /**
     * navigation constructor.
     * @param \moodle_page $page
     * @param stdClass $program
     */
    public function __construct(\moodle_page $page, stdClass $program) {
        parent::__construct($page);
        $this->program = $program;
    }

    /**
     * Init secondary menu.
     */
    public function initialise(): void {
        $this->id = 'secondary_navigation';
        $this->headertitle = get_string('menu');

        $program = $this->program;

        $url = new moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
        $this->add(get_string('tabgeneral', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_general');

        $url = new moodle_url('/admin/tool/muprog/management/program_content.php', ['id' => $program->id]);
        $this->add(get_string('tabcontent', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_content');

        $url = new moodle_url('/admin/tool/muprog/management/program_visibility.php', ['id' => $program->id]);
        $this->add(get_string('tabvisibility', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_visibility');

        $url = new moodle_url('/admin/tool/muprog/management/program_allocation.php', ['id' => $program->id]);
        $this->add(get_string('taballocation', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_allocation');

        $url = new moodle_url('/admin/tool/muprog/management/program_notifications.php', ['id' => $program->id]);
        $this->add(get_string('notifications', 'tool_mulib'), $url, \navigation_node::TYPE_SETTING, null, 'program_notifications');

        /** @var \tool_muprog\local\source\base[] $sourceclasses */ // Class name hack.
        $sourceclasses = allocation::get_source_classes();
        foreach ($sourceclasses as $sourceclass) {
            $sourceclass::add_program_secondary_tabs($this, $program);
        }

        if (\tool_muprog\local\certificate::is_available()) {
            $url = new moodle_url('/admin/tool/muprog/management/program_certificate.php', ['id' => $program->id]);
            $this->add(get_string('certificate', 'tool_certificate'), $url, \navigation_node::TYPE_SETTING, null, 'program_certificate');
        }

        $url = new moodle_url('/admin/tool/muprog/management/program_users.php', ['id' => $program->id]);
        $this->add(get_string('tabusers', 'tool_muprog'), $url, \navigation_node::TYPE_SETTING, null, 'program_users');

        $this->scan_for_active_node($this);
        $this->initialised = true;
    }
}
