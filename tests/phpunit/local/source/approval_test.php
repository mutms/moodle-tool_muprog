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

/**
 * Approval allocation source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\source\approval
 */
final class approval_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_type(): void {
        $this->assertSame('approval', \tool_muprog\local\source\approval::get_type());
    }

    public function test_is_new_alloved(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->assertTrue(\tool_muprog\local\source\approval::is_new_allowed($program));
        \set_config('source_approval_allownew', 0, 'tool_muprog');
        $this->assertFalse(\tool_muprog\local\source\approval::is_new_allowed($program));
    }

    public function test_is_new_allowed_in_new(): void {
        $this->assertFalse(\tool_muprog\local\source\approval::is_new_allowed_in_new());
    }

    public function test_can_user_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $program3 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []], 'archived' => 1]);
        $source3m = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source3a = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'approval'], '*', MUST_EXIST);

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();

        \cohort_add_member($cohort1->id, $user1->id);

        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        // Must not be archived.

        $program1 = program::archive($program1->id);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));
        $program1 = program::restore($program1->id);

        // Real user required.

        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $guest->id));

        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, 0));

        // Allocation start-end observed.

        $this->setUser($user1);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => time() + 100, 'timeallocationend' => null]);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => null, 'timeallocationend' => time() - 100]);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => time() - 100, 'timeallocationend' => time() + 100]);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $program1 = program::update_allocation((object)['id' => $program1->id,
            'timeallocationstart' => null, 'timeallocationend' => null]);

        // Must be visible in catalogue.

        $section1 = \tool_mucatalog\local\section::update((object)['id' => $section1->id,
            'uservisible' => 0, 'cohortvisible' => [$cohort1->id]]);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $section1 = \tool_mucatalog\local\section::update((object)['id' => $section1->id,
            'uservisible' => 0, 'cohortvisible' => []]);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $section1 = \tool_mucatalog\local\section::update((object)['id' => $section1->id,
            'uservisible' => 1]);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $item1 = \tool_mucatalog\local\item\program::archive($item1->id);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        $item1 = \tool_mucatalog\local\item\program::restore($item1->id);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        // Allocated already.

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1m->id, [$user1->id]);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));

        // Not rejected or pending.

        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user2->id));
        $this->setUser($user2);

        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user2->id));

        \tool_muprog\local\source\approval::reject_request($request->id, 'oh well');
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user2->id));

        \tool_muprog\local\source\approval::delete_request($request->id);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user2->id));

        // Disabled requests.

        $source1a = \tool_muprog\local\source\approval::update_source((object)[
            'programid' => $program1->id,
            'type' => 'approval',
            'enable' => 1,
            'approval_allowrequest' => 0,
        ]);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user2->id));
    }

    public function test_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);
        $this->assertSame($source1a->id, $request->sourceid);
        $this->assertSame($user1->id, $request->userid);

        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);
        $this->assertNull($request);
    }

    public function test_approve_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);

        $this->setUser($user2);
        $allocation = \tool_muprog\local\source\approval::approve_request($request->id);
        $this->assertSame($program1->id, $allocation->programid);
        $this->assertSame($source1a->id, $allocation->sourceid);
        $this->assertSame($user1->id, $allocation->userid);
        $this->assertFalse($DB->record_exists('tool_muprog_request', ['sourceid' => $source1a->id, 'userid' => $user1->id]));
    }

    public function test_reject_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);

        $this->setUser($user2);
        $this->setCurrentTimeStart();
        \tool_muprog\local\source\approval::reject_request($request->id, 'sorry mate');
        $request = $DB->get_record('tool_muprog_request', ['sourceid' => $source1a->id, 'userid' => $user1->id]);
        $this->assertSame($source1a->id, $request->sourceid);
        $this->assertSame($user1->id, $request->userid);
        $this->assertTimeCurrent($request->timerejected);
        $this->assertFalse(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));
    }

    public function test_delete_request(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source1m = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source1a = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');
        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $program2 = $generator->create_program(['sources' => ['manual' => [], 'approval' => []]]);
        $source2m = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $source2a = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $request = \tool_muprog\local\source\approval::request($program1->id, $source1a->id);

        $this->setUser($user2);
        \tool_muprog\local\source\approval::delete_request($request->id);
        $this->assertTrue(\tool_muprog\local\source\approval::can_user_request($program1, $source1a, $user1->id));
    }

    public function test_is_import_allowed(): void {

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['approval' => []]]);
        $program2 = $generator->create_program(['sources' => []]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => ['approval' => []]]);

        \set_config('source_approval_allownew', '1', 'tool_muprog');

        $this->assertTrue(\tool_muprog\local\source\approval::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\approval::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\approval::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\approval::is_import_allowed($program2, $program4));

        \set_config('source_approval_allownew', '0', 'tool_muprog');

        $this->assertFalse(\tool_muprog\local\source\approval::is_import_allowed($program1, $program3));
        $this->assertFalse(\tool_muprog\local\source\approval::is_import_allowed($program2, $program3));
        $this->assertTrue(\tool_muprog\local\source\approval::is_import_allowed($program1, $program4));
        $this->assertFalse(\tool_muprog\local\source\approval::is_import_allowed($program2, $program4));
    }

    public function test_import_source_data(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['approval' => []]]);
        $program2 = $generator->create_program(['sources' => ['approval' => []]]);
        $program3 = $generator->create_program(['sources' => []]);
        $program4 = $generator->create_program(['sources' => []]);

        $source1 = \tool_muprog\local\source\approval::update_source((object)[
            'programid' => $program1->id,
            'type' => 'approval',
            'enable' => 1,
            'approval_allowrequest' => 0,
        ]);
        $source2 = \tool_muprog\local\source\approval::update_source((object)[
            'programid' => $program2->id,
            'type' => 'approval',
            'enable' => 1,
            'approval_allowrequest' => 1,
        ]);
        $this->assertNotSame($source1->datajson, $source2->datajson);
        // Auxiliary fields are not used by this source, they must never be copied.
        $junk = ['auxint1' => 11, 'auxint2' => 22, 'auxint3' => 33, 'auxint4' => 44, 'auxint5' => 55];
        $DB->update_record('tool_muprog_source', (object)(['id' => $source1->id] + $junk));
        $DB->update_record('tool_muprog_source', (object)(['id' => $source2->id] + $junk));

        // New source in target gets the settings only.
        $source3 = \tool_muprog\local\source\approval::import_source_data($program1->id, $program3->id);
        $this->assertSame($program3->id, $source3->programid);
        $this->assertSame('approval', $source3->type);
        $this->assertSame($source1->datajson, $source3->datajson);
        $this->assertNull($source3->auxint1);
        $this->assertNull($source3->auxint2);
        $this->assertNull($source3->auxint3);
        $this->assertNull($source3->auxint4);
        $this->assertNull($source3->auxint5);
        $this->assertSame(1, $DB->count_records('tool_muprog_source', ['programid' => $program3->id]));

        // Settings of existing source in target are replaced, nothing else is touched.
        $DB->update_record('tool_muprog_source', (object)['id' => $source3->id, 'auxint1' => 1, 'auxint2' => 2, 'auxint3' => 3, 'auxint4' => 4, 'auxint5' => 5]);
        $source3x = \tool_muprog\local\source\approval::import_source_data($program2->id, $program3->id);
        $this->assertSame($source3->id, $source3x->id);
        $this->assertSame($program3->id, $source3x->programid);
        $this->assertSame('approval', $source3x->type);
        $this->assertSame($source2->datajson, $source3x->datajson);
        $this->assertSame('1', $source3x->auxint1);
        $this->assertSame('2', $source3x->auxint2);
        $this->assertSame('3', $source3x->auxint3);
        $this->assertSame('4', $source3x->auxint4);
        $this->assertSame('5', $source3x->auxint5);
        $this->assertSame(1, $DB->count_records('tool_muprog_source', ['programid' => $program3->id]));

        // Originals are not changed.
        $this->assertSame($source1->datajson, $DB->get_field('tool_muprog_source', 'datajson', ['id' => $source1->id]));
        $this->assertSame($source2->datajson, $DB->get_field('tool_muprog_source', 'datajson', ['id' => $source2->id]));
        $this->assertSame('11', $DB->get_field('tool_muprog_source', 'auxint1', ['id' => $source1->id]));

        // Source must be enabled in the original.
        try {
            \tool_muprog\local\source\approval::import_source_data($program4->id, $program3->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_missing_record_exception::class, $ex);
        }
        $this->assertSame(0, $DB->count_records('tool_muprog_source', ['programid' => $program4->id]));

        // Requests are not copied.
        $user = $this->getDataGenerator()->create_user();
        $DB->insert_record('tool_muprog_request', (object)[
            'sourceid' => $source1->id, 'userid' => $user->id, 'timerequested' => time(), 'datajson' => '[]',
        ]);
        \tool_muprog\local\source\approval::import_source_data($program1->id, $program3->id);
        $this->assertSame(0, $DB->count_records('tool_muprog_request', ['sourceid' => $source3->id]));
        $this->assertSame(1, $DB->count_records('tool_muprog_request', ['sourceid' => $source1->id]));
    }
}
