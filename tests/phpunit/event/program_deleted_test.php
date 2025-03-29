<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\phpunit\event;

use tool_muprog\local\program;

/**
 * Program deleted event test.
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\event\program_deleted
 */
final class program_deleted_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_delete_program(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $this->setAdminUser();
        $program = program::add_program($data);

        $sink = $this->redirectEvents();
        program::delete_program($program->id);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('tool_muprog\event\program_deleted', $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($program->id, $event->objectid);
        $this->assertSame('d', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_muprog_program', $event->objecttable);
        $this->assertSame('Program deleted', $event::get_name());
        $description = $event->get_description();
        $programurl = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $program->contextid]);
        $this->assertSame($programurl->out(false), $event->get_url()->out(false));
    }
}
