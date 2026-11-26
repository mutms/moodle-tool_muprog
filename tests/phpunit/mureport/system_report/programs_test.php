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

use tool_muprog\mureport\system_report\programs;
use tool_mureport\mureport\request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Programs report tests.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(programs::class)]
#[CoversClass(\tool_muprog\mureport\entity\program::class)]
#[Group('MuTMS')]
final class programs_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Returns program idnumbers of report rows.
     *
     * @param int $contextid
     * @param request $request
     * @return string[]
     */
    private function get_idnumbers(int $contextid, request $request = new request([])): array {
        $report = programs::make_instance($contextid);
        $report->require_access();
        $rows = $report->get_query()->get_page($request)->export_rows();
        return array_map(fn(array $row): string => $row['cells']['program:idnumber'], $rows);
    }

    public function test_rows(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $category3 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $syscontext = \context_system::instance();
        $program0 = $generator->create_program(['idnumber' => 'P0', 'fullname' => 'Alpha']);
        $program1 = $generator->create_program(['idnumber' => 'P1', 'fullname' => 'Beta', 'contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['idnumber' => 'P2', 'fullname' => 'Gamma', 'contextid' => $catcontext2->id]);
        $generator->create_program(['idnumber' => 'P3', 'fullname' => 'Delta',
            'contextid' => \context_coursecat::instance($category3->id)->id]);
        $generator->create_program(['idnumber' => 'P4', 'fullname' => 'Old', 'archived' => 1]);

        $this->setAdminUser();
        $this->assertSame(['P0', 'P1', 'P3', 'P2', 'P4'], $this->get_idnumbers($syscontext->id));
        $rows = programs::make_instance($syscontext->id)->get_query()->get_page(new request([]))->export_rows();
        $this->assertSame([false, false, false, false, true], array_column($rows, 'muted'));
        $this->assertSame(['P0', 'P1', 'P3', 'P2'], $this->get_idnumbers($syscontext->id, new request(['program:archived' => ['value' => 0]])));
        $this->assertSame(['P1', 'P2'], $this->get_idnumbers($catcontext1->id));
        $this->assertSame(['P2'], $this->get_idnumbers($catcontext2->id));
        $this->assertSame(['P4'], $this->get_idnumbers($syscontext->id, new request(['program:archived' => ['value' => 1]])));
        $this->assertSame(['P1'], $this->get_idnumbers($syscontext->id, new request([], null, 0, null, 'beta')));
        $this->assertSame(['P2'], $this->get_idnumbers($syscontext->id, new request([], null, 0, null, (string)$program2->id)));
        $category = new request(['program:category' => ['values' => [(string)$catcontext1->id]]]);
        $this->assertSame(['P1'], $this->get_idnumbers($syscontext->id, $category));

        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $roleid, $syscontext);
        role_assign($roleid, $user->id, $catcontext1->id);
        assign_capability('tool/muprog:view', CAP_PROHIBIT, $roleid, $catcontext2);
        $this->setUser($user);
        $this->assertSame(['P1'], $this->get_idnumbers($catcontext1->id));
        try {
            $this->get_idnumbers($syscontext->id);
            $this->fail('Exception expected');
        } catch (\core\exception\required_capability_exception $ex) {
            $this->assertInstanceOf(\core\exception\required_capability_exception::class, $ex);
        }

        $this->setAdminUser();
        $report = programs::make_instance($syscontext->id);
        $rows = $report->get_query()->get_page(new request(['program:fullname' => ['value' => 'Alpha']]))->export_rows();
        $url = '/admin/tool/muprog/management/program.php?id=' . $program0->id;
        $this->assertStringContainsString($url, $rows[0]['cells']['program:fullname']);
        $this->assertStringContainsString('System', $rows[0]['cells']['program:category']);
        $labels = \tool_muprog\mureport\entity\program::get_category_labels([(string)$catcontext1->id, (string)$syscontext->id]);
        $this->assertSame($category1->name, $labels[$catcontext1->id]);
        $this->assertSame('System', $labels[$syscontext->id]);

        $source = new \tool_mureport\picker\filtervalue\report_filter(
            (int)\tool_mureport\mureport\util\registry::get_system_report(programs::class)->id,
            json_encode(['contextid' => $catcontext1->id]),
            'program:category'
        );
        $values = array_column($source->search('', [], 0)['rows'], 'value');
        $this->assertSame([(string)$catcontext1->id, (string)$catcontext2->id], $values);
        $this->assertSame($catcontext1->id, (int)$DB->get_field('tool_muprog_program', 'contextid', ['id' => $program1->id]));
    }

    public function test_invalid_context(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->expectException(\core\exception\invalid_parameter_exception::class);
        programs::make_instance(\context_course::instance($course->id)->id)->require_access();
    }
}
