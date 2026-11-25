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

use tool_muprog\mureport\system_report\program_requests;
use tool_mureport\mureport\request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Program requests report tests.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(program_requests::class)]
#[CoversClass(\tool_muprog\mureport\entity\request::class)]
#[Group('MuTMS')]
final class program_requests_test extends \advanced_testcase {
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
        $report = program_requests::make_instance($programid);
        $report->require_access();
        return $report->get_query()->get_page($request)->export_rows();
    }

    /**
     * Returns user names and actions of report rows.
     *
     * @param int $programid
     * @param request $request
     * @return array name => action names
     */
    private function get_actions(int $programid, request $request = new request([])): array {
        $result = [];
        foreach ($this->get_rows($programid, $request) as $row) {
            $result[preg_replace('/^[A-Z]+(?=[A-Z])/', '', strip_tags($row['cells']['auser:fullname']))] = array_column($row['actions']['request:actions'] ?? [], 'name');
        }
        return $result;
    }

    public function test_rows(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Beta']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'Cyril', 'lastname' => 'Gamma']);
        $program1 = $generator->create_program(['idnumber' => 'P1', 'sources' => ['approval' => [], 'manual' => []]]);
        $program2 = $generator->create_program(['idnumber' => 'P2', 'sources' => ['approval' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'approval'], '*', MUST_EXIST);
        $now = time();
        $DB->insert_record('tool_muprog_request', ['sourceid' => $source1->id, 'userid' => $user1->id, 'timerequested' => $now - 300]);
        $DB->insert_record('tool_muprog_request', ['sourceid' => $source1->id, 'userid' => $user2->id, 'timerequested' => $now - 200,
            'timerejected' => $now - 100]);
        $DB->insert_record('tool_muprog_request', ['sourceid' => $source1->id, 'userid' => $user3->id, 'timerequested' => $now - 100]);
        $DB->insert_record('tool_muprog_request', ['sourceid' => $source2->id, 'userid' => $user1->id, 'timerequested' => $now]);
        $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user3->id]);

        $this->setAdminUser();
        $this->assertSame([
            'Anna Alpha' => ['approve', 'reject'],
            'Bob Beta' => ['delete'],
            'Cyril Gamma' => ['reject', 'delete'],
        ], $this->get_actions($program1->id));
        $this->assertSame(['Anna Alpha', 'Cyril Gamma'], array_keys($this->get_actions($program1->id, new request(['request:status' => ['value' => 'pending']]))));
        $this->assertSame(['Bob Beta'], array_keys($this->get_actions($program1->id, new request(['request:status' => ['value' => 'rejected']]))));
        $this->assertSame(['Anna Alpha'], array_keys($this->get_actions($program2->id)));

        $DB->set_field('tool_muprog_program', 'archived', 1, ['id' => $program1->id]);
        $this->assertSame(['reject'], $this->get_actions($program1->id)['Anna Alpha']);

        $viewer = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $viewer->id, \context_system::instance()->id);
        $this->setUser($viewer);
        $this->assertSame(['Anna Alpha' => [], 'Bob Beta' => [], 'Cyril Gamma' => []], $this->get_actions($program1->id));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\required_capability_exception::class);
        $this->get_rows($program1->id);
    }
}
