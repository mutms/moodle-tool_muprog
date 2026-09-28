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

use tool_muprog\muform\autocomplete\item_create_credits_creditframeworkid;
use tool_mulib\local\mulib;

/**
 * Credits item framework autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocomplete\item_create_credits_creditframeworkid
 */
final class item_create_credits_creditframeworkid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        if (!mulib::is_mutrain_available()) {
            $this->markTestSkipped('mutrain not available');
        }
        $this->resetAfterTest();
    }

    public function test_search(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \tool_mutrain_generator $creditsgenerator */
        $creditsgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']
        );
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field1']
        );
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'mutrain', 'shortname' => 'field2']
        );

        $framework1 = $creditsgenerator->create_framework((object)[
            'name' => 'Some framework',
            'publicaccess' => 1,
            'fields' => [$field1->get('id')],
        ]);
        $framework2 = $creditsgenerator->create_framework((object)[
            'name' => 'Other framework',
            'publicaccess' => 1,
            'idnumber' => 'ofr2',
            'fields' => [$field2->get('id')],
        ]);
        $framework3 = $creditsgenerator->create_framework((object)[
            'name' => 'Another framework',
            'contextid' => $catcontext1->id,
            'publicaccess' => 0,
            'fields' => [],
        ]);
        $framework4 = $creditsgenerator->create_framework((object)[
            'name' => 'Grrr framework',
            'publicaccess' => 1,
            'archived' => 1,
            'fields' => [],
        ]);

        $program1 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $syscontext->id);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $fviewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/mutrain:viewframeworks', CAP_ALLOW, $fviewerroleid, $syscontext);
        role_assign($fviewerroleid, $user1->id, $syscontext->id);

        $this->setUser($user1);
        $source = new item_create_credits_creditframeworkid((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $expected = [
            (int)$framework3->id => $framework3->name,
            (int)$framework2->id => $framework2->name,
            (int)$framework1->id => $framework1->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertSame($expected, $source->search('framework', 50));
        $this->assertNull($source->search('framework', 2));
        $this->assertSame([(int)$framework3->id => $framework3->name], $source->search('Another', 50));
        $this->assertSame([(int)$framework2->id => $framework2->name], $source->search('fr2', 50));
        $this->assertSame([], $source->search('xxx', 50));
        $this->assertSame($framework1->name, $source->label((string)$framework1->id));
        $this->assertSame($framework3->name, $source->label((string)$framework3->id));
        $this->assertNull($source->label((string)$framework4->id));
        $this->assertNull($source->label((string)($framework4->id + 100)));
        $this->assertNull($source->label('abc'));
        $this->assertNull($source->validate((string)$framework1->id));

        $this->setUser($user2);
        $source = new item_create_credits_creditframeworkid((int)$program1->id);
        $expected = [
            (int)$framework2->id => $framework2->name,
            (int)$framework1->id => $framework1->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertSame($framework1->name, $source->label((string)$framework1->id));
        $this->assertNull($source->label((string)$framework3->id));

        $this->setUser($user3);
        $source = new item_create_credits_creditframeworkid((int)$program2->id);
        $expected = [
            (int)$framework2->id => $framework2->name,
            (int)$framework1->id => $framework1->name,
        ];
        $this->assertSame($expected, $source->search('', 50));

        $this->setUser($user1);
        $generator->create_program_item([
            'programid' => $program1->id,
            'creditframeworkid' => $framework1->id,
        ]);
        $source = new item_create_credits_creditframeworkid((int)$program1->id);
        $expected = [
            (int)$framework3->id => $framework3->name,
            (int)$framework2->id => $framework2->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertNull($source->label((string)$framework1->id));

        $this->setUser($user3);
        try {
            new item_create_credits_creditframeworkid((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setUser($user4);
        try {
            new item_create_credits_creditframeworkid((int)$program1->id);
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

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program([]);
        $program1 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program2 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $user0 = $this->getDataGenerator()->create_user(['tenantid' => 0]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        /** @var \tool_mutrain_generator $creditsgenerator */
        $creditsgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutrain');

        $framework0 = $creditsgenerator->create_framework((object)[
            'name' => 'Framework 0',
            'contextid' => $syscontext->id,
            'publicaccess' => 1,
        ]);
        $framework1 = $creditsgenerator->create_framework((object)[
            'name' => 'Framework 1',
            'contextid' => $tenant1catcontext->id,
            'publicaccess' => 1,
        ]);
        $framework2 = $creditsgenerator->create_framework((object)[
            'name' => 'Framework 2',
            'contextid' => $tenant2catcontext->id,
            'publicaccess' => 1,
        ]);

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user0->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $tenant2catcontext->id);

        $this->setUser($user0);
        $source = new item_create_credits_creditframeworkid((int)$program0->id);
        $expected = [
            (int)$framework0->id => $framework0->name,
            (int)$framework1->id => $framework1->name,
            (int)$framework2->id => $framework2->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertSame($framework2->name, $source->label((string)$framework2->id));

        $source = new item_create_credits_creditframeworkid((int)$program1->id);
        $expected = [
            (int)$framework0->id => $framework0->name,
            (int)$framework1->id => $framework1->name,
        ];
        $this->assertSame($expected, $source->search('', 50));
        $this->assertSame($framework0->name, $source->label((string)$framework0->id));
        $this->assertSame($framework1->name, $source->label((string)$framework1->id));
        $this->assertNull($source->label((string)$framework2->id));

        $source = new item_create_credits_creditframeworkid((int)$program2->id);
        $expected = [
            (int)$framework0->id => $framework0->name,
            (int)$framework2->id => $framework2->name,
        ];
        $this->assertSame($expected, $source->search('', 50));

        $this->setUser($user2);
        $source = new item_create_credits_creditframeworkid((int)$program2->id);
        $this->assertSame($expected, $source->search('', 50));
    }
}
