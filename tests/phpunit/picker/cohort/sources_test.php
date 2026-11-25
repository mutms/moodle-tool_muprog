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

namespace tool_muprog\phpunit\picker\cohort;

use tool_muprog\picker\cohort\source_cohort_edit_cohortids;
use tool_muprog\picker\cohort\source_manual_allocate_cohortid;
use tool_mulib\local\mulib;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests of cohort picker sources of program allocation forms.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(source_cohort_edit_cohortids::class)]
#[CoversClass(source_manual_allocate_cohortid::class)]
#[Group('MuTMS')]
final class sources_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Ids of cohorts offered by a source.
     *
     * @param \tool_mulib\picker\cohort\base $source
     * @param string $query
     * @param string[] $exclude
     * @return int[]
     */
    private function search(\tool_mulib\picker\cohort\base $source, string $query, array $exclude = []): array {
        return array_column($source->search($query, $exclude)['cohorts'], 'id');
    }

    public function test_source_cohort_edit_cohortids(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $syscontext = \core\context\system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \core\context\coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $incategory = ['contextid' => $catcontext1->id];
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0] + $incategory);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1] + $incategory);
        $ids = [(int)$cohort1->id, (int)$cohort2->id, (int)$cohort3->id];
        $all = array_map('strval', $ids);

        $program1 = $generator->create_program(['sources' => ['cohort' => []]]);
        $program2 = $generator->create_program(['sources' => ['cohort' => ['cohortids' => [$cohort1->id]]]] + $incategory);

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
        $source = new source_cohort_edit_cohortids((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $this->assertSame($ids, $this->search($source, ''));
        $this->assertSame([(int)$cohort1->id], $this->search($source, 'ta 1'));
        $this->assertSame([(int)$cohort1->id, (int)$cohort3->id], $this->search($source, '', [(string)$cohort2->id]));
        $this->assertSame($all, array_map('strval', array_keys($source->labels($all))));
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($cohort3->id + 100)]));

        // Manager of the category may use its cohorts, the cohort already allocated stays valid.
        $this->setUser($user2);
        $source = new source_cohort_edit_cohortids((int)$program2->id);
        $this->assertSame([(int)$cohort2->id, (int)$cohort3->id], $this->search($source, ''));
        $this->assertSame($all, array_map('strval', array_keys($source->labels($all))));

        // Without permission to view cohorts only visible cohorts and the allocated cohort.
        $this->setUser($user3);
        $source = new source_cohort_edit_cohortids((int)$program2->id);
        $this->assertSame([(int)$cohort3->id], $this->search($source, ''));
        $labels = $source->labels($all);
        $this->assertSame([(string)$cohort1->id, (string)$cohort3->id], array_map('strval', array_keys($labels)));
        $this->assertSame('Kohorta 1', $labels[$cohort1->id]);

        try {
            new source_cohort_edit_cohortids((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }

    public function test_source_manual_allocate_cohortid(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $syscontext = \core\context\system::instance();
        $category1 = $this->getDataGenerator()->create_category();
        $catcontext1 = \core\context\coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 1', 'visible' => 0]);
        $incategory = ['contextid' => $catcontext1->id];
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 2', 'visible' => 0] + $incategory);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Kohorta 3', 'visible' => 1] + $incategory);
        $all = [(string)$cohort1->id, (string)$cohort2->id, (string)$cohort3->id];

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['sources' => ['manual' => []], 'contextid' => $catcontext1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user1->id, $catcontext1->id);

        $this->setAdminUser();
        $source = new source_manual_allocate_cohortid((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $this->assertSame([(int)$cohort1->id, (int)$cohort2->id, (int)$cohort3->id], $this->search($source, ''));
        $this->assertSame($all, array_map('strval', array_keys($source->labels($all))));

        $this->setUser($user1);
        $source = new source_manual_allocate_cohortid((int)$program2->id);
        $this->assertSame([(int)$cohort3->id], $this->search($source, ''));
        $this->assertSame([(string)$cohort3->id], array_map('strval', array_keys($source->labels($all))));

        $viewroleid = $this->getDataGenerator()->create_role();
        assign_capability('moodle/cohort:view', CAP_ALLOW, $viewroleid, $catcontext1);
        role_assign($viewroleid, $user1->id, $catcontext1->id);
        $this->assertSame([(int)$cohort2->id, (int)$cohort3->id], $this->search($source, ''));

        try {
            new source_manual_allocate_cohortid((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }
    }

    public function test_tenants(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }
        \tool_mutenancy\local\tenancy::activate();
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $catcontext1 = \core\context\coursecat::instance($tenant1->categoryid);
        $catcontext2 = \core\context\coursecat::instance($tenant2->categoryid);
        $cohort0 = $this->getDataGenerator()->create_cohort(['name' => 'Test 0']);
        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Test 1', 'contextid' => $catcontext1->id]);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Test 2', 'contextid' => $catcontext2->id]);
        $all = [(string)$cohort0->id, (string)$cohort1->id, (string)$cohort2->id];
        $program0 = $generator->create_program(['sources' => ['cohort' => [], 'manual' => []]]);
        $program1 = $generator->create_program(['sources' => ['cohort' => [], 'manual' => []], 'contextid' => $catcontext1->id]);

        $this->setAdminUser();
        foreach ([source_cohort_edit_cohortids::class, source_manual_allocate_cohortid::class] as $class) {
            $source = new $class((int)$program0->id);
            $this->assertSame([(int)$cohort0->id, (int)$cohort1->id, (int)$cohort2->id], $this->search($source, 'Test'));
            $source = new $class((int)$program1->id);
            $this->assertSame([(int)$cohort0->id, (int)$cohort1->id], $this->search($source, 'Test'));
            $this->assertSame([(string)$cohort0->id, (string)$cohort1->id], array_map('strval', array_keys($source->labels($all))));
        }
    }
}
