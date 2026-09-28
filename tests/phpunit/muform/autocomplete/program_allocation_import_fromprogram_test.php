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

use tool_muprog\muform\autocomplete\program_allocation_import_fromprogram;
use tool_mulib\local\mulib;

/**
 * Allocation import program autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocomplete\program_allocation_import_fromprogram
 * @covers \tool_muprog\muform\util\autocomplete\program_trait
 */
final class program_allocation_import_fromprogram_test extends \advanced_testcase {
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

        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'description' => 'some desc 1',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'publicaccess' => 1,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
            'cohorts' => [$cohort1->id],
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'description' => '<b>some desc 2</b>',
            'descriptionformat' => \FORMAT_HTML,
            'publicaccess' => 0,
            'archived' => 0,
            'contextid' => $catcontext1->id,
            'sources' => ['manual' => [], 'cohort' => []],
            'cohorts' => [$cohort1->id, $cohort2->id],
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'publicaccess' => 1,
            'archived' => 1,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);
        $program4 = $generator->create_program([
            'fullname' => 'Prog4',
            'idnumber' => 'p4',
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/muprog:clone', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user2->id, $catcontext1->id);

        $this->setAdminUser();
        $source = new program_allocation_import_fromprogram((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());
        $this->assertSame(
            [(int)$program2->id => 'pokus', (int)$program3->id => 'Prog3', (int)$program4->id => 'Prog4'],
            $source->search('', 50)
        );
        $this->assertSame([(int)$program2->id => 'pokus'], $source->search('desc 2', 50));
        $this->assertSame([(int)$program3->id => 'Prog3'], $source->search('p3', 50));
        $this->assertNull($source->search('', 2));
        $this->assertSame('pokus', $source->label((string)$program2->id));
        $this->assertSame('Prog3', $source->label((string)$program3->id));
        $this->assertNull($source->label((string)$program1->id));
        $this->assertNull($source->label((string)($program4->id + 100)));
        $this->assertNull($source->label('abc'));
        $this->assertNull($source->validate((string)$program2->id));

        $this->setUser($user1);
        try {
            new program_allocation_import_fromprogram((int)$program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user2);
        $source = new program_allocation_import_fromprogram((int)$program2->id);
        $this->assertSame([(int)$program4->id => 'Prog4'], $source->search('', 50));
        $this->assertSame('Prog4', $source->label((string)$program4->id));
        $this->assertNull($source->label((string)$program1->id));
        $this->assertNull($source->label((string)$program2->id));
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

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program(['fullname' => 'prg0', 'sources' => ['manual' => []]]);
        $program1 = $generator->create_program(
            ['fullname' => 'prg1', 'contextid' => $tenant1catcontext->id, 'sources' => ['manual' => []]]
        );
        $program2 = $generator->create_program(
            ['fullname' => 'prg2', 'contextid' => $tenant2catcontext->id, 'sources' => ['manual' => []]]
        );
        $program3 = $generator->create_program(
            ['fullname' => 'prg3', 'contextid' => $tenant1catcontext->id, 'sources' => ['manual' => []]]
        );

        $user0 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => $tenant1->id]);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $editorroleid, $syscontext);
        assign_capability('tool/muprog:clone', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $tenant1catcontext->id);

        $this->setAdminUser();
        $source = new program_allocation_import_fromprogram((int)$program0->id);
        $this->assertSame(
            [(int)$program1->id => 'prg1', (int)$program2->id => 'prg2', (int)$program3->id => 'prg3'],
            $source->search('', 50)
        );
        $this->assertSame('prg2', $source->label((string)$program2->id));

        $source = new program_allocation_import_fromprogram((int)$program1->id);
        $this->assertSame(
            [(int)$program0->id => 'prg0', (int)$program3->id => 'prg3'],
            $source->search('', 50)
        );
        $this->assertSame('prg0', $source->label((string)$program0->id));
        $this->assertNull($source->label((string)$program2->id));

        $this->setUser($user0);
        try {
            new program_allocation_import_fromprogram((int)$program1->id);
            $this->fail('Exception excepted');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user1);
        $source = new program_allocation_import_fromprogram((int)$program1->id);
        $this->assertSame([(int)$program3->id => 'prg3'], $source->search('', 50));
        $this->assertSame('prg3', $source->label((string)$program3->id));
        $this->assertNull($source->label((string)$program0->id));
    }
}
