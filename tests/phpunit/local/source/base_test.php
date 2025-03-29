<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_muprog\phpunit\local\source;

use tool_muprog\local\program;

/**
 * Allocation source base test.
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\base
 */
final class base_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_is_valid_dateoverrides(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::add_program($data);
        $now = time();

        $data = (object)[
            'id' => $program->id,
            'programstart_type' => 'date',
            'programstart_date' => $now,
            'programdue_type' => 'date',
            'programdue_date' => $now + 100,
            'programend_type' => 'date',
            'programend_date' => $now + 200,
        ];
        $program = program::update_program_scheduling($data);

        $this->assertTrue(\tool_muprog\local\source\base::is_valid_dateoverrides($program, []));
        $this->assertTrue(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timestart' => $now + 1]));
        $this->assertTrue(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timestart' => $now + 1, 'timedue' => $now + 2]));
        $this->assertTrue(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timestart' => $now + 1, 'timedue' => $now + 2, 'timeend' => $now + 2]));
        $this->assertTrue(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timestart' => 0, 'timedue' => 0, 'timeend' => 0]));

        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timestart' => $now + 100]));
        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timedue' => $now]));
        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timedue' => $now - 1]));
        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timeend' => $now]));
        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timeend' => $now - 1]));
        $this->assertFalse(\tool_muprog\local\source\base::is_valid_dateoverrides($program, ['timedue' => $now + 5, 'timeend' => $now + 4]));
    }
}
