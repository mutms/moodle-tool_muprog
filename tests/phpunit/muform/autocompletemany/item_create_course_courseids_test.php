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

use tool_muprog\muform\autocompletemany\item_create_course_courseids;
use tool_mulib\local\mulib;

/**
 * Program course items autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocompletemany\item_create_course_courseids
 */
final class item_create_course_courseids_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_labels(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);

        $course1 = $this->getDataGenerator()->create_course(
            ['fullname' => 'Kurz 1', 'shortname' => 'k1', 'category' => $category1->id]
        );
        $course2 = $this->getDataGenerator()->create_course(
            ['fullname' => 'Kurz 2', 'shortname' => 'k2', 'idnumber' => 'abc', 'category' => $category1->id]
        );
        $course3 = $this->getDataGenerator()->create_course(
            ['fullname' => 'Kurz 3', 'shortname' => 'k3', 'category' => $category2->id]
        );

        $program1 = $generator->create_program(['contextid' => $syscontext->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext1->id]);
        $generator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/muprog:addcourse', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $all = [(string)$course1->id, (string)$course2->id, (string)$course3->id, (string)SITEID];

        $this->setAdminUser();
        $source = new item_create_course_courseids((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $this->assertSame(
            [(int)$course2->id => 'Kurz 2', (int)$course3->id => 'Kurz 3'],
            $source->search('', 50, [])
        );
        $this->assertSame([(int)$course3->id => 'Kurz 3'], $source->search('', 50, [(string)$course2->id]));
        $this->assertSame([(int)$course2->id => 'Kurz 2'], $source->search('abc', 50, []));
        $this->assertSame([(int)$course3->id => 'Kurz 3'], $source->search('k3', 50, []));
        $this->assertNull($source->search('', 1, []));
        $this->assertSame(
            [(int)$course2->id => 'Kurz 2', (int)$course3->id => 'Kurz 3'],
            $source->labels($all)
        );
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($course3->id + 100)]));
        $this->assertSame([], $source->validate($all));

        $source = new item_create_course_courseids((int)$program2->id);
        $this->assertSame(
            [(int)$course1->id => 'Kurz 1', (int)$course2->id => 'Kurz 2', (int)$course3->id => 'Kurz 3'],
            $source->search('', 50, [])
        );

        $this->setUser($user1);
        try {
            new item_create_course_courseids((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
        $source = new item_create_course_courseids((int)$program2->id);
        $this->assertSame(
            [(int)$course1->id => 'Kurz 1', (int)$course2->id => 'Kurz 2'],
            $source->search('', 50, [])
        );
        $this->assertSame(
            [(int)$course1->id => 'Kurz 1', (int)$course2->id => 'Kurz 2'],
            $source->labels($all)
        );

        $this->setUser($user2);
        try {
            new item_create_course_courseids((int)$program2->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }

    public function test_search_tenant(): void {
        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);
        $category0 = $this->getDataGenerator()->create_category([]);

        $course0 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 0', 'category' => $category0->id]);
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 1', 'category' => $tenant1->categoryid]);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 2', 'category' => $tenant2->categoryid]);

        $program0 = $generator->create_program([]);
        $program1 = $generator->create_program(['contextid' => $tenant1catcontext->id]);

        $all = [(string)$course0->id, (string)$course1->id, (string)$course2->id];

        $this->setAdminUser();
        $source = new item_create_course_courseids((int)$program0->id);
        $result = $source->search('Kurz', 50, []);
        $this->assertSame([(int)$course0->id, (int)$course1->id, (int)$course2->id], array_keys($result));
        $this->assertSame([], $source->validate($all));

        $source = new item_create_course_courseids((int)$program1->id);
        $result = $source->search('Kurz', 50, []);
        $this->assertSame([(int)$course0->id, (int)$course1->id], array_keys($result));
        $this->assertSame([(int)$course0->id, (int)$course1->id, (int)$course2->id], array_keys($source->labels($all)));
        $this->assertSame(
            [(int)$course2->id => 'Program from another tenant cannot be accessed'],
            $source->validate($all)
        );
    }
}
