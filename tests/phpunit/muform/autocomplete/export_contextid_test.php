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

namespace tool_muprog\phpunit\muform\autocomplete;

use tool_muprog\muform\autocomplete\export_contextid;
use tool_mulib\local\mulib;

/**
 * Program export context autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocomplete\export_contextid
 */
final class export_contextid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_label(): void {
        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Kategorie A']);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Kategorie B']);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $category3 = $this->getDataGenerator()->create_category(['name' => 'Kategorie C', 'parent' => $category1->id]);
        $catcontext3 = \context_coursecat::instance($category3->id);
        $course = $this->getDataGenerator()->create_course(['category' => $category1->id]);
        $coursecontext = \context_course::instance($course->id);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:export', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $catcontext1->id);

        $this->setAdminUser();
        $source = new export_contextid((int)$syscontext->id);
        $this->assertSame([(int)$syscontext->id], $source->get_args());
        $result = $source->search('', 50);
        $this->assertSame('System', $result[$syscontext->id]);
        $this->assertSame('Kategorie A', $result[$catcontext1->id]);
        $this->assertSame('Kategorie B', $result[$catcontext2->id]);
        $this->assertSame('Kategorie A / Kategorie C', $result[$catcontext3->id]);
        $this->assertSame([(int)$catcontext2->id => 'Kategorie B'], $source->search('Kategorie B', 50));
        $this->assertNull($source->search('Kategorie', 2));

        $this->assertSame('System', $source->label((string)$syscontext->id));
        $this->assertSame('Kategorie A', $source->label((string)$catcontext1->id));
        $this->assertSame('Kategorie A / Kategorie C', $source->label((string)$catcontext3->id));
        $this->assertNull($source->label((string)$coursecontext->id));
        $this->assertNull($source->label('abc'));
        $this->assertNull($source->label('0'));
        $this->assertNull($source->validate((string)$catcontext1->id));

        $this->setUser($user1);
        try {
            new export_contextid((int)$syscontext->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
        $source = new export_contextid((int)$catcontext1->id);
        $this->assertSame(
            [(int)$catcontext1->id => 'Kategorie A', (int)$catcontext3->id => 'Kategorie A / Kategorie C'],
            $source->search('', 50)
        );
        $this->assertSame('Kategorie A', $source->label((string)$catcontext1->id));
        $this->assertSame('Kategorie A / Kategorie C', $source->label((string)$catcontext3->id));
        $this->assertNull($source->label((string)$syscontext->id));
        $this->assertNull($source->label((string)$catcontext2->id));

        $this->setUser($user2);
        try {
            new export_contextid((int)$catcontext1->id);
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

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $this->setAdminUser();
        $source = new export_contextid((int)$syscontext->id);
        $result = $source->search('', 50);
        $this->assertArrayHasKey($syscontext->id, $result);
        $this->assertArrayHasKey($tenant1catcontext->id, $result);
        $this->assertArrayHasKey($tenant2catcontext->id, $result);

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant1->id);
        $result = $source->search('', 50);
        $this->assertArrayHasKey($syscontext->id, $result);
        $this->assertArrayHasKey($tenant1catcontext->id, $result);
        $this->assertArrayNotHasKey($tenant2catcontext->id, $result);
    }
}
