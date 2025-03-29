<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\phpunit\event;

use tool_muprog\local\program;

/**
 * Program created event test.
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\event\program_created
 */
final class program_created_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_add_program(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $admin = get_admin();

        $this->setAdminUser();
        $sink = $this->redirectEvents();
        $program = program::add_program($data);
        $events = $sink->get_events();
        $sink->close();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('tool_muprog\event\program_created', $event);
        $this->assertEquals($syscontext->id, $event->contextid);
        $this->assertSame($program->id, $event->objectid);
        $this->assertSame('c', $event->crud);
        $this->assertSame($event::LEVEL_OTHER, $event->edulevel);
        $this->assertSame('tool_muprog_program', $event->objecttable);
        $this->assertSame('Program created', $event::get_name());
        $description = $event->get_description();
        $programurl = new \moodle_url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
        $this->assertSame($programurl->out(false), $event->get_url()->out(false));
    }
}
