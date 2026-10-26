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

namespace tool_muprog\phpunit\local;

use tool_muprog\local\evidence_upload;
use tool_muprog\local\source\manual;

/**
 * Completion evidence upload tests.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\evidence_upload
 */
final class evidence_upload_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Options with everything skipped or not.
     *
     * @param bool $skip
     * @param string $details
     * @return \stdClass
     */
    private function get_options(bool $skip, string $details = 'Default'): \stdClass {
        $options = [];
        foreach (evidence_upload::get_option_names() as $name) {
            $options[$name] = (int)$skip;
        }
        $options['details'] = $details;
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
        $csvdata = evidence_upload::validate(['rows' => $rows, 'columns' => ['headers' => $headers, 'map' => $map]]);
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
        $rows = [['username', 'completed'], ['user1', '02/12/23']];

        $delimiter = \tool_mulib\local\import_util::DELIMITER_AUTO;
        $csvdata = evidence_upload::save_source("username,completed\nuser1,02/12/23", 'UTF-8', $delimiter);
        $this->assertSame($rows, $csvdata->rows);
        $this->assertTrue(evidence_upload::is_columns_stage($csvdata));

        // The completion date column is required.
        $this->assertSame(
            ['column_0' => 'evidenceupload_error_nocompletion'],
            evidence_upload::check_columns($rows, ['headers' => true, 'map' => ['username', '']])
        );
        $this->assertSame(
            ['column_0' => 'evidenceupload_error_nouser'],
            evidence_upload::check_columns($rows, ['headers' => true, 'map' => ['', 'timecompleted']])
        );

        $csvdata = evidence_upload::save_columns($csvdata, (object)['headers' => 1, 'column_0' => 'username', 'column_1' => 'timecompleted']);
        $this->assertFalse(evidence_upload::is_columns_stage($csvdata));
        $this->assertTrue(evidence_upload::is_options_stage($csvdata));

        // Without details column the default details are required.
        $csvdata = evidence_upload::save_options($csvdata, (object)['skipmissing' => 1, 'details' => ' ']);
        $this->assertTrue(evidence_upload::is_options_stage($csvdata));
        $csvdata = evidence_upload::save_options($csvdata, (object)['skipmissing' => 1, 'details' => ' Some details ']);
        $this->assertFalse(evidence_upload::is_options_stage($csvdata));
        $this->assertSame('Some details', $csvdata->options['details']);
        $this->assertEquals($csvdata, evidence_upload::validate(json_decode(json_encode($csvdata), true)));

        // Details column does not need default details.
        $rows = [['user1', '02/12/23', 'x']];
        $csvdata = $this->csvdata($rows, ['username', 'timecompleted', 'details']);
        $this->assertTrue(evidence_upload::has_details_column($csvdata));
        $csvdata = evidence_upload::save_options($csvdata, (object)[]);
        $this->assertFalse(evidence_upload::is_options_stage($csvdata));
    }

    public function test_guesses(): void {
        $first = ['username', 'email', 'idnumber', 'completed', 'evidence'];
        $this->assertTrue(evidence_upload::guess_headers($first));
        $this->assertSame(['username', '', '', 'timecompleted', 'details'], evidence_upload::guess_columns([$first], true));
        $this->assertSame(
            ['', 'email', 'timecompleted'],
            evidence_upload::guess_columns([['Name', 'Email address', 'Completion date']], true)
        );
    }

    public function test_check_reports_every_problem(): void {
        global $DB;

        [$program, $source] = $this->create_program();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        $user3 = $this->getDataGenerator()->create_user(['username' => 'user3']);
        $user4 = $this->getDataGenerator()->create_user(['username' => 'user4']);
        $user5 = $this->getDataGenerator()->create_user(['username' => 'user5']);
        manual::allocate_users($program->id, $source->id, [$user1->id, $user2->id, $user3->id, $user4->id]);
        $DB->set_field('tool_muprog_allocation', 'archived', 1, ['programid' => $program->id, 'userid' => $user3->id]);

        $rows = [
            ['user1', '02/12/23', 'Evidence 1'],
            ['user2', '03/12/23', ''],
            ['user3', '03/12/23', ''],
            ['user4', '', ''],
            ['user5', '03/12/23', ''],
            ['nobody', '03/12/23', ''],
            ['user1', '04/12/23', ''],
            ['user2', '13/13/23', ''],
        ];
        $csvdata = $this->csvdata($rows, ['username', 'timecompleted', 'details']);

        $outcomes = evidence_upload::check($program, $csvdata, $this->get_options(false));
        $this->assertSame([
            evidence_upload::RESULT_UPDATED,
            evidence_upload::RESULT_UPDATED,
            evidence_upload::RESULT_ERROR,
            evidence_upload::RESULT_ERROR,
            evidence_upload::RESULT_ERROR,
            evidence_upload::RESULT_ERROR,
            evidence_upload::RESULT_ERROR,
            evidence_upload::RESULT_ERROR,
        ], array_map(fn($o) => $o->result, $outcomes));
        $this->assertSame('Evidence 1', $outcomes[0]->details);
        $this->assertSame(strtotime('02/12/23'), $outcomes[0]->timecompleted);
        $this->assertSame('Default', $outcomes[1]->details);
        $this->assertSame(get_string('evidenceupload_error_archived', 'tool_muprog'), $outcomes[2]->message);
        $this->assertSame(get_string('evidenceupload_error_nodate', 'tool_muprog'), $outcomes[3]->message);
        $this->assertSame(get_string('evidenceupload_error_notallocated', 'tool_muprog'), $outcomes[4]->message);
        $this->assertSame(get_string('evidenceupload_error_missing', 'tool_muprog', 'nobody'), $outcomes[5]->message);
        $this->assertSame(get_string('evidenceupload_error_duplicate', 'tool_muprog'), $outcomes[6]->message);
        // The second row of user2 is a duplicate before the date is looked at.
        $this->assertSame(get_string('evidenceupload_error_duplicate', 'tool_muprog'), $outcomes[7]->message);

        $outcomes = evidence_upload::check($program, $csvdata, $this->get_options(true));
        $this->assertSame([
            evidence_upload::RESULT_UPDATED,
            evidence_upload::RESULT_UPDATED,
            evidence_upload::RESULT_SKIPPED,
            evidence_upload::RESULT_SKIPPED,
            evidence_upload::RESULT_SKIPPED,
            evidence_upload::RESULT_SKIPPED,
            evidence_upload::RESULT_SKIPPED,
            evidence_upload::RESULT_SKIPPED,
        ], array_map(fn($o) => $o->result, $outcomes));

        $outcomes = evidence_upload::check($program, $this->csvdata([['user1', '13/13/23']], ['username', 'timecompleted']), $this->get_options(false));
        $this->assertSame(get_string('evidenceupload_error_date', 'tool_muprog', '13/13/23'), $outcomes[0]->message);
    }

    public function test_import(): void {
        global $DB;

        [$program, $source] = $this->create_program();
        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2']);
        manual::allocate_users($program->id, $source->id, [$user1->id, $user2->id]);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user1->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user2->id]);
        $item = $DB->get_record('tool_muprog_item', ['topitem' => 1, 'programid' => $program->id], '*', MUST_EXIST);

        $rows = [['user1', '02/12/23', 'Evidence 1'], ['user2', '03/12/23', ''], ['nobody', '03/12/23', '']];
        $csvdata = $this->csvdata($rows, ['username', 'timecompleted', 'details']);

        $counts = evidence_upload::import($program, $csvdata, $this->get_options(false));
        $this->assertSame([evidence_upload::RESULT_UPDATED => 2, evidence_upload::RESULT_SKIPPED => 0, evidence_upload::RESULT_ERROR => 1], $counts);
        $this->assertSame(0, $DB->count_records('tool_muprog_evidence', ['itemid' => $item->id]));

        $counts = evidence_upload::import($program, $csvdata, $this->get_options(true));
        $this->assertSame([evidence_upload::RESULT_UPDATED => 2, evidence_upload::RESULT_SKIPPED => 1, evidence_upload::RESULT_ERROR => 0], $counts);
        $evidence1 = $DB->get_record('tool_muprog_evidence', ['itemid' => $item->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertSame((string)strtotime('02/12/23'), $evidence1->timecompleted);
        $this->assertStringContainsString('Evidence 1', $evidence1->evidencejson);
        $evidence2 = $DB->get_record('tool_muprog_evidence', ['itemid' => $item->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $this->assertStringContainsString('Default', $evidence2->evidencejson);

        $allocation1 = $DB->get_record('tool_muprog_allocation', ['id' => $allocation1->id]);
        $this->assertSame((string)strtotime('02/12/23'), $allocation1->timecompleted);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['id' => $allocation2->id]);
        $this->assertSame((string)strtotime('03/12/23'), $allocation2->timecompleted);
    }
}
