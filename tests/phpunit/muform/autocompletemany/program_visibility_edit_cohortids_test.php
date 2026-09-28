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

namespace tool_muprog\phpunit\muform\autocompletemany;

use tool_muprog\muform\autocompletemany\program_visibility_edit_cohortids;
use tool_mulib\local\mulib;

/**
 * Program visibility cohorts autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocompletemany\program_visibility_edit_cohortids
 */
final class program_visibility_edit_cohortids_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]
        );
        $cohort3 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);
        role_assign($managerrole->id, $user2->id, $catcontext1);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $this->setUser($user1);

        $source = new program_visibility_edit_cohortids((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $expected = [
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        $expected = [
            (int)$cohort1->id => $cohort1->name,
        ];
        $this->assertSame($expected, $source->search('ta 1', 50, []));

        $expected = [
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort3->id => $cohort3->name,
        ];
        $this->assertSame($expected, $source->search('', 50, [(string)$cohort2->id]));
        $this->assertNull($source->search('', 2, []));

        $this->setUser($user2);

        $source = new program_visibility_edit_cohortids((int)$program2->id);
        $expected = [
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        $this->setUser($user3);

        $source = new program_visibility_edit_cohortids((int)$program2->id);
        $expected = [
            (int)$cohort3->id => $cohort3->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        try {
            new program_visibility_edit_cohortids((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }

    public function test_search_tenant(): void {
        global $DB;

        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $tenantcohort1 = $DB->get_record('cohort', ['id' => $tenant1->cohortid]);
        $tenantcohort2 = $DB->get_record('cohort', ['id' => $tenant2->cohortid]);

        $program0 = $generator->create_program([]);
        $program1 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program2 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $cohort0 = $this->getDataGenerator()->create_cohort(['visible' => 1]);
        $cohort1 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant1catcontext->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['visible' => 1, 'contextid' => $tenant2catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);

        $this->setUser($user1);

        // NOTE: tenant cohorts are created in system context - they should be visible here.

        $source = new program_visibility_edit_cohortids((int)$program0->id);
        $expected = [
            (int)$cohort0->id => $cohort0->name,
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$tenantcohort1->id => $tenantcohort1->name,
            (int)$tenantcohort2->id => $tenantcohort2->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        $source = new program_visibility_edit_cohortids((int)$program1->id);
        $expected = [
            (int)$cohort0->id => $cohort0->name,
            (int)$cohort1->id => $cohort1->name,
            (int)$tenantcohort1->id => $tenantcohort1->name,
            (int)$tenantcohort2->id => $tenantcohort2->name,
        ];
        $this->assertSame($expected, $source->search('', 50, []));

        // Cohorts of other tenants are not allowed.
        $this->assertSame([], $source->labels([(string)$cohort2->id]));
        $this->assertSame([(string)$cohort2->id => 'Error'], $source->validate([(string)$cohort2->id]));
        $this->assertSame([(int)$cohort1->id => $cohort1->name], $source->labels([(string)$cohort1->id]));
        $this->assertSame([], $source->validate([(string)$cohort1->id]));
    }

    public function test_labels_validate(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program(['contextid' => $catcontext1->id]);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $cohort2 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 2', 'visible' => 0, 'contextid' => $catcontext1->id]
        );
        $cohort3 = $this->getDataGenerator()->create_cohort(
            ['name' => 'Kohorta 3', 'visible' => 1, 'contextid' => $catcontext1->id]
        );

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user1->id, $syscontext);
        role_assign($managerrole->id, $user2->id, $catcontext1);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $all = [(string)$cohort1->id, (string)$cohort2->id, (string)$cohort3->id];
        $alllabels = [
            (int)$cohort1->id => $cohort1->name,
            (int)$cohort2->id => $cohort2->name,
            (int)$cohort3->id => $cohort3->name,
        ];

        $this->setUser($user1);

        $source = new program_visibility_edit_cohortids((int)$program1->id);
        $this->assertSame($alllabels, $source->labels($all));
        $this->assertSame([], $source->validate($all));
        $source = new program_visibility_edit_cohortids((int)$program2->id);
        $this->assertSame($alllabels, $source->labels($all));
        $this->assertSame([], $source->validate($all));

        // Unknown and invalid values.
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($cohort3->id + 100)]));
        $this->assertSame(
            [(string)($cohort3->id + 100) => 'Error'],
            $source->validate([(string)($cohort3->id + 100)])
        );

        $this->setUser($user2);

        try {
            new program_visibility_edit_cohortids((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
        $source = new program_visibility_edit_cohortids((int)$program2->id);
        $this->assertSame(
            [(int)$cohort2->id => $cohort2->name, (int)$cohort3->id => $cohort3->name],
            $source->labels($all)
        );
        $this->assertSame([(string)$cohort1->id => 'Error'], $source->validate($all));

        $this->setUser($user3);

        $source = new program_visibility_edit_cohortids((int)$program2->id);
        $this->assertSame([(int)$cohort3->id => $cohort3->name], $source->labels($all));
        $this->assertSame(
            [(string)$cohort1->id => 'Error', (string)$cohort2->id => 'Error'],
            $source->validate($all)
        );

        // Cohorts already used by the program are always allowed.
        \tool_muprog\local\program::update_visibility(
            (object)['id' => $program2->id, 'publicaccess' => 0, 'cohortids' => [$cohort1->id]]
        );
        $this->assertSame(
            [(int)$cohort1->id => $cohort1->name, (int)$cohort3->id => $cohort3->name],
            $source->labels($all)
        );
        $this->assertSame([(string)$cohort2->id => 'Error'], $source->validate($all));
    }
}
