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

use tool_muprog\muform\autocomplete\source_program_edit_programid;
use tool_mulib\local\mulib;

/**
 * Program completion allocation source program autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocomplete\source_program_edit_programid
 * @covers \tool_muprog\muform\util\autocomplete\program_trait
 */
final class source_program_edit_programid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'contextid' => $syscontext->id,
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'contextid' => $catcontext1->id,
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'contextid' => $syscontext->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user2->id, $syscontext->id);
        $allocatorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $allocatorroleid, $syscontext);
        role_assign($allocatorroleid, $user2->id, $catcontext1->id);

        $this->setAdminUser();
        $source = new source_program_edit_programid((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $this->assertSame([(int)$program2->id => 'pokus', (int)$program3->id => 'Prog3'], $source->search('', 50));
        $this->assertSame([(int)$program3->id => 'Prog3'], $source->search('p3', 50));
        $this->assertNull($source->search('', 1));
        $this->assertSame('pokus', $source->label((string)$program2->id));
        $this->assertNull($source->label((string)$program1->id));
        $this->assertNull($source->label((string)($program3->id + 100)));
        $this->assertNull($source->label('abc'));

        $this->setUser($user1);
        try {
            new source_program_edit_programid((int)$program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user2);
        $source = new source_program_edit_programid((int)$program1->id);
        $this->assertSame([(int)$program2->id => 'pokus'], $source->search('', 50));
        $this->assertSame('pokus', $source->label((string)$program2->id));
        $this->assertNull($source->label((string)$program3->id));
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

        $program1 = $generator->create_program([]);
        $program2 = $generator->create_program([]);
        $program3 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program4 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program5 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/muprog:allocate', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $tenant1catcontext->id);

        $this->setAdminUser();
        $source = new source_program_edit_programid((int)$program1->id);
        $this->assertSame([(int)$program2->id => $program2->fullname], $source->search('', 50));
        $this->assertSame($program2->fullname, $source->label((string)$program2->id));
        $this->assertNull($source->label((string)$program3->id));

        $source = new source_program_edit_programid((int)$program3->id);
        $this->assertSame([(int)$program4->id => $program4->fullname], $source->search('', 50));
        $this->assertSame($program4->fullname, $source->label((string)$program4->id));
        $this->assertNull($source->label((string)$program1->id));
        $this->assertNull($source->label((string)$program5->id));

        $source = new source_program_edit_programid((int)$program5->id);
        $this->assertSame([], $source->search('', 50));

        $this->setUser($user1);
        $source = new source_program_edit_programid((int)$program3->id);
        $this->assertSame([(int)$program4->id => $program4->fullname], $source->search('', 50));
        try {
            new source_program_edit_programid((int)$program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }
}
