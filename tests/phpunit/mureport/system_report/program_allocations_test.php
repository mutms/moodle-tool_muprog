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

namespace tool_muprog\phpunit\mureport\system_report;

use tool_muprog\mureport\system_report\program_allocations;
use tool_mureport\mureport\request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Program allocations report tests.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(program_allocations::class)]
#[CoversClass(\tool_muprog\mureport\entity\allocation::class)]
#[Group('MuTMS')]
final class program_allocations_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Returns exported rows.
     *
     * @param int $programid
     * @param request $request
     * @return array
     */
    private function get_rows(int $programid, request $request = new request([])): array {
        $report = program_allocations::make_instance($programid);
        $report->require_access();
        return $report->get_query()->get_page($request)->export_rows();
    }

    /**
     * Returns user names of report rows.
     *
     * @param int $programid
     * @param request $request
     * @return string[]
     */
    private function get_names(int $programid, request $request = new request([])): array {
        return array_map(fn(array $row): string => strip_tags($row['cells']['auser:fullname']), $this->get_rows($programid, $request));
    }

    /**
     * Returns action names of a row.
     *
     * @param array $row
     * @return string[]
     */
    private function get_actions(array $row): array {
        return array_column($row['actions']['allocation:actions'] ?? [], 'name');
    }

    public function test_rows(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Beta']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Cyril', 'lastname' => 'Gamma']);
        $program1 = $generator->create_program(['idnumber' => 'P1', 'fullname' => 'Alpha', 'sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['idnumber' => 'P2', 'fullname' => 'Beta', 'sources' => ['manual' => []]]);
        $allocation1 = $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user1->id]);
        $allocation2 = $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user2->id]);
        $generator->create_program_allocation(['programid' => $program2->id, 'userid' => $user3->id]);
        $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $allocation2->id]);

        $this->setAdminUser();
        $this->assertSame(['Anna Alpha', 'Bob Beta'], $this->get_names($program1->id));
        $this->assertSame(['Anna Alpha'], $this->get_names($program1->id, new request(['allocation:archived' => ['value' => 0]])));
        $this->assertSame(['Bob Beta'], $this->get_names($program1->id, new request([], null, 0, null, 'bob')));
        $this->assertSame(['Cyril Gamma'], $this->get_names($program2->id));

        $rows = $this->get_rows($program1->id);
        $this->assertStringContainsString('/admin/tool/muprog/management/allocation.php?id=' . $allocation1->id, $rows[0]['cells']['auser:fullname']);
        $this->assertSame(['update', 'archive', 'delete'], $this->get_actions($rows[0]));
        $this->assertSame(['restore', 'delete'], $this->get_actions($rows[1]));
        $this->assertSame([false, true], array_column($rows, 'muted'));

        $DB->set_field('tool_muprog_program', 'archived', 1, ['id' => $program1->id]);
        $rows = $this->get_rows($program1->id);
        $this->assertSame(['delete'], $this->get_actions($rows[0]));

        $viewer = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $viewer->id, \context_system::instance()->id);
        $this->setUser($viewer);
        $rows = $this->get_rows($program1->id);
        $this->assertCount(2, $rows);
        $this->assertSame([], $this->get_actions($rows[0]));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\required_capability_exception::class);
        $this->get_rows($program1->id);
    }

    public function test_fullname_formatters(): void {
        $column = program_allocations::make_inspector()->get_selector_columns('auser:fullname')['auser:fullname'];
        $this->assertSame('allocationlink', $column->get_default_formatter_id());
        $this->assertSame(
            ['fullname', 'link', 'picturelink', 'picturelinkstatus', 'allocationlink'],
            array_keys($column->get_formatter_choices())
        );
    }

    public function test_invalid_program(): void {
        $this->setAdminUser();
        $this->expectException(\core\exception\invalid_parameter_exception::class);
        program_allocations::make_instance(-1)->require_access();
    }
}
