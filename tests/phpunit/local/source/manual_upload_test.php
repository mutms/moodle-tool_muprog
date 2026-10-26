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
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_muprog\phpunit\local\source;

use tool_muprog\local\program;
use tool_muprog\local\source\manual_upload;

/**
 * Allocation upload tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\manual_upload
 */
final class manual_upload_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Options with everything skipped or not.
     *
     * @param bool $skip
     * @return \stdClass
     */
    private function get_options(bool $skip): \stdClass {
        $options = [];
        foreach (manual_upload::get_option_names() as $name) {
            $options[$name] = (int)$skip;
        }
        return (object)$options;
    }

    /**
     * Data that passed the columns stage.
     *
     * @param array $rows
     * @param array $map
     * @param bool $headers
     * @return \stdClass
     */
    private function csvdata(array $rows, array $map, bool $headers = false): \stdClass {
        $csvdata = manual_upload::validate(['rows' => $rows, 'columns' => ['headers' => $headers, 'map' => $map]]);
        $this->assertNotEmpty($csvdata->columns);
        return $csvdata;
    }

    /**
     * Program with manual allocation.
     *
     * @return array program and source records
     */
    private function create_program(): array {
        global $DB;
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program(['sources' => ['manual' => []]]);
        $source = $DB->get_record('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual'], '*', MUST_EXIST);
        return [$program, $source];
    }

    public function test_wizard_state_and_stages(): void {
        $this->setAdminUser();
        $rows = [['username', 'startdate'], ['user1', '']];
        $columns = ['headers' => true, 'map' => [0 => 'username', 1 => 'timestart']];

        // Nothing stored yet, the wizard asks for the data.
        foreach ([[], ['rows' => 'x'], ['rows' => [['a'], ['b', 'c']]]] as $stored) {
            $csvdata = manual_upload::validate($stored);
            $this->assertSame([], $csvdata->rows);
            $this->assertTrue(manual_upload::is_source_stage($csvdata));
        }

        $delimiter = \tool_mulib\local\import_util::DELIMITER_AUTO;
        $csvdata = manual_upload::save_source("username,startdate\nuser1,", 'UTF-8', $delimiter);
        $this->assertSame($rows, $csvdata->rows);
        $this->assertFalse(manual_upload::is_source_stage($csvdata));
        $this->assertTrue(manual_upload::is_columns_stage($csvdata));
        $this->assertEquals($csvdata, manual_upload::validate(json_decode(json_encode($csvdata), true)));

        $csvdata = manual_upload::save_columns($csvdata, (object)['headers' => 1, 'column_0' => 'username', 'column_1' => 'timestart']);
        $this->assertSame($columns, $csvdata->columns);
        $this->assertFalse(manual_upload::is_columns_stage($csvdata));
        $this->assertTrue(manual_upload::is_options_stage($csvdata));

        // Confirmed options have to be confirmed again after the columns changed.
        $csvdata = manual_upload::save_options($csvdata, (object)['skipallocated' => 1]);
        $this->assertFalse(manual_upload::is_options_stage($csvdata));
        $this->assertEquals($csvdata, manual_upload::validate(json_decode(json_encode($csvdata), true)));
        $csvdata = manual_upload::save_columns($csvdata, (object)['headers' => 1, 'column_0' => 'username', 'column_1' => 'timestart']);
        $this->assertTrue(manual_upload::is_options_stage($csvdata));
        $this->assertSame(1, $csvdata->options['skipallocated']);
        $this->assertSame(0, $csvdata->options['skipmissing']);
    }

    public function test_check_columns(): void {
        $rows = [['a', 'b'], ['c', 'd']];
        $this->assertSame([], manual_upload::check_columns($rows, ['headers' => true, 'map' => ['email', 'timedue']]));
        $this->assertSame(
            ['column_0' => 'source_manual_upload_error_nouser'],
            manual_upload::check_columns($rows, ['headers' => false, 'map' => ['timedue', '']])
        );
        $this->assertSame(
            ['column_1' => 'source_manual_upload_error_columntwice'],
            manual_upload::check_columns($rows, ['headers' => false, 'map' => ['email', 'email']])
        );
        $this->assertSame(
            ['column_1' => 'source_manual_upload_error_columnunknown'],
            manual_upload::check_columns($rows, ['headers' => false, 'map' => ['email', 'xx']])
        );
        $this->assertSame(
            ['headers' => 'source_manual_upload_error_nodatarows'],
            manual_upload::check_columns([['email']], ['headers' => true, 'map' => ['email']])
        );
        $this->assertSame(
            ['headers' => 'source_manual_upload_error_headers'],
            manual_upload::check_columns($rows, ['map' => ['email', '']])
        );
    }

    public function test_guesses(): void {
        $first = ['username', 'email', 'idnumber', 'firstname'];
        $this->assertTrue(manual_upload::guess_headers($first));
        // Only the first user column is used.
        $this->assertSame(['username', '', '', ''], manual_upload::guess_columns([$first], true));
        $this->assertSame(
            ['', 'idnumber', '', 'timestart'],
            manual_upload::guess_columns([['Name', 'ID number', 'x', 'Start date']], true)
        );
        $this->assertSame(
            ['username', 'timestart', 'timedue', 'timeend'],
            manual_upload::guess_columns([['username', 'startdate', 'duedate', 'enddate']], true)
        );

        $this->assertFalse(manual_upload::guess_headers(['student1@example.com']));
        $this->assertSame(['', 'email'], manual_upload::guess_columns([['1', 'a@example.com'], ['2', 'b@example.com']], false));
        $this->assertSame(['username', ''], manual_upload::guess_columns([['student1', 'x'], ['student2', 'y']], false));
    }

    public function test_check_reports_every_problem(): void {
        global $DB;

        [$program, $source] = $this->create_program();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'email' => 'user1@example.com', 'idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'email' => 'same@example.com', 'idnumber' => 'u2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3', 'email' => 'same@example.com', 'idnumber' => 'u3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4', 'email' => 'user4@example.com', 'idnumber' => 'u4']);
        $user5 = $this->getDataGenerator()->create_user(['username' => 'user5', 'email' => 'user5@example.com', 'idnumber' => 'u5']);
        \tool_muprog\local\source\manual::allocate_users($program->id, $source->id, [$user4->id]);

        $rows = [
            ['USER1', '', 'User1@Example.com'],
            ['nobody', '', ''],
            ['', '', 'same@example.com'],
            ['user1', '', ''],
            ['user4', '', ''],
            ['user5', 'abc', ''],
            ['', '', ''],
            ['user2', '', 'user1@example.com'],
        ];
        $csvdata = $this->csvdata($rows, ['username', 'timestart', 'email']);

        $results = fn(array $outcomes) => array_map(fn($o) => $o->result, $outcomes);
        $outcomes = manual_upload::check($program, $csvdata, $this->get_options(false));
        $this->assertSame([
            manual_upload::RESULT_ALLOCATED,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
            manual_upload::RESULT_ERROR,
        ], $results($outcomes));
        $this->assertSame($user1->id, (string)$outcomes[0]->userid);
        $this->assertSame(get_string('source_manual_upload_error_missing', 'tool_muprog', 'nobody'), $outcomes[1]->message);
        $this->assertSame(get_string('source_manual_upload_error_ambiguous', 'tool_muprog', 'same@example.com'), $outcomes[2]->message);
        $this->assertSame(get_string('source_manual_upload_error_duplicate', 'tool_muprog'), $outcomes[3]->message);
        $this->assertSame(get_string('source_manual_upload_error_allocated', 'tool_muprog'), $outcomes[4]->message);
        $this->assertSame(get_string('source_manual_upload_error_date', 'tool_muprog', 'abc'), $outcomes[5]->message);
        $this->assertSame(get_string('source_manual_upload_error_nouservalue', 'tool_muprog'), $outcomes[6]->message);
        $this->assertSame(get_string('source_manual_upload_error_ambiguous', 'tool_muprog', 'user1@example.com'), $outcomes[7]->message);

        $outcomes = manual_upload::check($program, $csvdata, $this->get_options(true));
        $this->assertSame([
            manual_upload::RESULT_ALLOCATED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
            manual_upload::RESULT_SKIPPED,
        ], $results($outcomes));

        // Nothing was written by the checks.
        $this->assertSame(1, $DB->count_records('tool_muprog_allocation', ['programid' => $program->id]));
    }

    public function test_import_writes_nothing_when_a_row_fails(): void {
        global $DB;

        [$program, $source] = $this->create_program();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $rows = [['username'], ['user1'], ['nobody']];

        $counts = manual_upload::import($program, $source, $this->csvdata($rows, ['username'], true), $this->get_options(false));
        $this->assertSame([manual_upload::RESULT_ALLOCATED => 1, manual_upload::RESULT_SKIPPED => 0, manual_upload::RESULT_ERROR => 1], $counts);
        $this->assertSame(0, $DB->count_records('tool_muprog_allocation', ['programid' => $program->id]));

        $counts = manual_upload::import($program, $source, $this->csvdata($rows, ['username'], true), $this->get_options(true));
        $this->assertSame([manual_upload::RESULT_ALLOCATED => 1, manual_upload::RESULT_SKIPPED => 1, manual_upload::RESULT_ERROR => 0], $counts);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id]));
    }

    public function test_import_with_dates(): void {
        global $DB;

        $this->setAdminUser();
        [$program, $source] = $this->create_program();
        $timestart = time();
        $pdata = (object)[
            'id' => $program->id,
            'programstart_type' => 'date',
            'programstart_date' => $timestart,
            'programdue_type' => 'date',
            'programdue_date' => $timestart + (20 * DAYSECS),
            'programend_type' => 'date',
            'programend_date' => $timestart + (30 * DAYSECS),
        ];
        $program = program::update_scheduling($pdata);

        $user1 = $this->getDataGenerator()->create_user(['idnumber' => 'u1']);
        $user2 = $this->getDataGenerator()->create_user(['idnumber' => 'u2']);
        $user3 = $this->getDataGenerator()->create_user(['idnumber' => 'u3']);
        $user4 = $this->getDataGenerator()->create_user(['idnumber' => 'u4']);
        $user5 = $this->getDataGenerator()->create_user(['idnumber' => 'u5']);

        $timestart2 = new \DateTime('@' . ($timestart - (10 * DAYSECS)));
        $timedue3 = new \DateTime('@' . ($timestart + (1 * DAYSECS)));
        $timeend3 = new \DateTime('@' . ($timestart + (2 * DAYSECS)));
        $rows = [
            ['u1', '', '', ''],
            ['u2', $timestart2->format(\DateTime::ATOM), '', ''],
            ['u3', '', $timedue3->format(\DateTime::ATOM), $timeend3->format(\DateTime::COOKIE)],
            ['u4', 'abc', '', ''],
            ['u5', '', '', '01/01/2001'],
        ];
        $csvdata = $this->csvdata($rows, ['idnumber', 'timestart', 'timedue', 'timeend']);

        $outcomes = manual_upload::check($program, $csvdata, $this->get_options(false));
        $this->assertSame(get_string('source_manual_upload_error_date', 'tool_muprog', 'abc'), $outcomes[3]->message);
        $this->assertSame(get_string('source_manual_upload_error_dates', 'tool_muprog'), $outcomes[4]->message);

        $counts = manual_upload::import($program, $source, $csvdata, $this->get_options(true));
        $this->assertSame([manual_upload::RESULT_ALLOCATED => 3, manual_upload::RESULT_SKIPPED => 2, manual_upload::RESULT_ERROR => 0], $counts);

        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id]);
        $this->assertEquals($pdata->programstart_date, $allocation1->timestart);
        $this->assertEquals($pdata->programdue_date, $allocation1->timedue);
        $this->assertEquals($pdata->programend_date, $allocation1->timeend);

        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user2->id]);
        $this->assertEquals($timestart2->getTimestamp(), $allocation2->timestart);
        $this->assertEquals($pdata->programdue_date, $allocation2->timedue);
        $this->assertEquals($pdata->programend_date, $allocation2->timeend);

        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user3->id]);
        $this->assertEquals($pdata->programstart_date, $allocation3->timestart);
        $this->assertEquals($timedue3->getTimestamp(), $allocation3->timedue);
        $this->assertEquals($timeend3->getTimestamp(), $allocation3->timeend);

        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user4->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user5->id]));
    }
}
