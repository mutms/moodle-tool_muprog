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

use tool_muprog\mureport\system_report\user_programs;
use tool_mureport\mureport\request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * User programs report tests.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(user_programs::class)]
#[CoversClass(\tool_muprog\mureport\entity\allocation::class)]
#[Group('MuTMS')]
final class user_programs_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Returns exported rows.
     *
     * @param int $userid
     * @param request $request
     * @return array
     */
    private function get_rows(int $userid, request $request = new request([])): array {
        $report = user_programs::make_instance($userid);
        $report->require_access();
        return $report->get_query()->get_page($request)->export_rows();
    }

    /**
     * Returns program idnumbers of report rows.
     *
     * @param int $userid
     * @param request $request
     * @return string[]
     */
    private function get_idnumbers(int $userid, request $request = new request([])): array {
        return array_map(fn(array $row): string => $row['cells']['program:idnumber'], $this->get_rows($userid, $request));
    }

    public function test_rows(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['idnumber' => 'P1', 'fullname' => 'Alpha']);
        $program2 = $generator->create_program(['idnumber' => 'P2', 'fullname' => 'Beta']);
        $program3 = $generator->create_program(['idnumber' => 'P3', 'fullname' => 'Gamma']);
        $program4 = $generator->create_program(['idnumber' => 'P4', 'fullname' => 'Delta']);
        $generator->create_program(['idnumber' => 'P5', 'fullname' => 'Other']);
        $allocation1 = $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user1->id]);
        $allocation2 = $generator->create_program_allocation(['programid' => $program2->id, 'userid' => $user1->id]);
        $allocation3 = $generator->create_program_allocation(['programid' => $program3->id, 'userid' => $user1->id]);
        $generator->create_program_allocation(['programid' => $program4->id, 'userid' => $user1->id]);
        $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user2->id]);
        $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $allocation3->id]);
        $DB->set_field('tool_muprog_program', 'archived', 1, ['id' => $program4->id]);
        $DB->set_field('tool_muprog_allocation', 'timecompleted', time(), ['id' => $allocation2->id]);
        $DB->set_field('tool_muprog_allocation', 'timedue', time() - 100, ['id' => $allocation1->id]);

        $this->setUser($user1);
        $this->assertSame(['P1', 'P2'], $this->get_idnumbers($user1->id));
        $this->assertSame(['P2'], $this->get_idnumbers($user1->id, new request(['allocation:status' => ['values' => ['completed']]])));
        $this->assertSame(['P1'], $this->get_idnumbers($user1->id, new request(['allocation:status' => ['values' => ['overdue']]])));
        $this->assertSame(['P2'], $this->get_idnumbers($user1->id, new request([], null, 0, null, 'beta')));

        $rows = $this->get_rows($user1->id);
        $url = '/admin/tool/muprog/my/program.php?id=' . $program1->id . '&amp;userid=' . $user1->id;
        $this->assertStringContainsString($url, $rows[0]['cells']['program:fullname']);
        $this->assertStringContainsString('Overdue', $rows[0]['cells']['allocation:status']);
        $this->assertStringContainsString('Manual allocation', $rows[0]['cells']['allocation:source']);
        $this->assertStringContainsString('Completed', $rows[1]['cells']['allocation:status']);

        try {
            $this->get_rows($user2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\required_capability_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }

        $this->setAdminUser();
        $this->assertSame(['P1'], $this->get_idnumbers($user2->id));
    }

    public function test_invalid_user(): void {
        $this->setAdminUser();
        $this->expectException(\core\exception\invalid_parameter_exception::class);
        user_programs::make_instance(-1)->require_access();
    }

    public function test_customfields(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \core_customfield_generator $cfgenerator */
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('core_customfield');
        /** @var \tool_mureport_generator $mureportgenerator */
        $mureportgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mureport');
        $programcategory = $cfgenerator->create_category(['component' => 'tool_muprog', 'area' => 'program']);
        $allocationcategory = $cfgenerator->create_category(['component' => 'tool_muprog', 'area' => 'allocation']);
        $fields = [
            'program:customfield:pallocated' => $cfgenerator->create_field(['categoryid' => $programcategory->get('id'),
                'shortname' => 'pallocated', 'configdata' => ['visibilityallocated' => 1]]),
            'program:customfield:pprivate' => $cfgenerator->create_field(['categoryid' => $programcategory->get('id'),
                'shortname' => 'pprivate']),
            'allocation:customfield:aallocatee' => $cfgenerator->create_field(['categoryid' => $allocationcategory->get('id'),
                'shortname' => 'aallocatee', 'configdata' => ['visibilityallocatee' => 1]]),
            'allocation:customfield:aprivate' => $cfgenerator->create_field(['categoryid' => $allocationcategory->get('id'),
                'shortname' => 'aprivate']),
        ];
        $mureportgenerator->create_report_column(['report' => user_programs::class, 'selector' => 'program:idnumber']);
        foreach ($fields as $selector => $field) {
            $mureportgenerator->create_report_column(['report' => user_programs::class, 'selector' => $selector]);
        }
        $user = $this->getDataGenerator()->create_user();
        $program = $generator->create_program(['idnumber' => 'P1', 'sources' => ['manual' => []]]);
        $allocation = $generator->create_program_allocation(['programid' => $program->id, 'userid' => $user->id]);
        foreach ($fields as $selector => $field) {
            $instanceid = str_starts_with($selector, 'program:') ? $program->id : $allocation->id;
            $cfgenerator->add_instance_data($field, (int)$instanceid, $field->get('shortname') . ' value');
        }

        $this->setUser($user);
        $cells = $this->get_rows($user->id)[0]['cells'];
        $this->assertSame('pallocated value', $cells['program:customfield:pallocated']);
        $this->assertStringContainsString(get_string('cellhidden', 'tool_mureport'), $cells['program:customfield:pprivate']);
        $this->assertSame('aallocatee value', $cells['allocation:customfield:aallocatee']);
        $this->assertStringContainsString(get_string('cellhidden', 'tool_mureport'), $cells['allocation:customfield:aprivate']);

        $this->setAdminUser();
        $cells = $this->get_rows($user->id)[0]['cells'];
        $this->assertSame('pprivate value', $cells['program:customfield:pprivate']);
        $this->assertSame('aprivate value', $cells['allocation:customfield:aprivate']);
    }
}
