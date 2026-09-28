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

use tool_muprog\muform\autocomplete\source_extdb_edit_queryid;
use tool_mulib\local\mulib;

/**
 * External database allocation query autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocomplete\source_extdb_edit_queryid
 */
final class source_extdb_edit_queryid_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search_label(): void {
        global $CFG, $DB;
        $this->preventResetByRollback();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $programgenerator->create_program(['contextid' => $syscontext->id]);
        $program1 = $programgenerator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $programgenerator->create_program(['contextid' => $catcontext2->id]);

        $server = $generator->create_extdb_server([]);
        $query0 = $generator->create_extdb_query([
            'contextid' => $syscontext->id,
            'name' => 'System query',
            'note' => 'grrr',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query1 = $generator->create_extdb_query([
            'contextid' => $catcontext1->id,
            'name' => 'Category 1 query',
            'note' => 'argh',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query2 = $generator->create_extdb_query([
            'contextid' => $catcontext2->id,
            'name' => 'Kategoie 2 query',
            'note' => '',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query3 = $generator->create_extdb_query([
            'contextid' => $syscontext->id,
            'name' => 'Other query',
            'note' => '',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        // Queries of other plugins or types must not be used for program allocation.
        $DB->set_field('tool_mulib_extdb_query', 'type', 'xallocation', ['id' => $query3->id]);

        $progroleid = $this->getDataGenerator()->create_role();
        $queryroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $progroleid, $syscontext->id);
        assign_capability('tool/mulib:useextdb', CAP_ALLOW, $queryroleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        role_assign($progroleid, $user0->id, $syscontext->id);
        role_assign($queryroleid, $user0->id, $syscontext->id);

        role_assign($progroleid, $user1->id, $syscontext->id);
        role_assign($queryroleid, $user1->id, $catcontext1->id);

        role_assign($queryroleid, $user2->id, $syscontext->id);

        $this->setUser($user0);

        $source = new source_extdb_edit_queryid((int)$program0->id);
        $this->assertSame([(int)$program0->id], $source->get_args());
        $this->assertSame([(int)$query0->id => 'System query'], $source->search('', 50));
        $this->assertSame('System query', $source->label((string)$query0->id));
        $this->assertNull($source->label((string)$query1->id));
        $this->assertNull($source->label((string)$query3->id));
        $this->assertNull($source->label((string)($query3->id + 100)));
        $this->assertNull($source->label('abc'));

        $source = new source_extdb_edit_queryid((int)$program1->id);
        $this->assertSame(
            [(int)$query1->id => 'Category 1 query', (int)$query0->id => 'System query'],
            $source->search('', 50)
        );
        $this->assertNull($source->search('', 1));
        $this->assertSame([(int)$query0->id => 'System query'], $source->search('System', 50));
        $this->assertSame([(int)$query1->id => 'Category 1 query'], $source->search('argh', 50));
        $this->assertSame('System query', $source->label((string)$query0->id));
        $this->assertSame('Category 1 query', $source->label((string)$query1->id));
        $this->assertNull($source->label((string)$query2->id));
        $this->assertNull($source->validate((string)$query1->id));

        $this->setUser($user1);

        $source = new source_extdb_edit_queryid((int)$program1->id);
        $this->assertSame([(int)$query1->id => 'Category 1 query'], $source->search('', 50));
        $this->assertSame('Category 1 query', $source->label((string)$query1->id));
        $this->assertNull($source->label((string)$query0->id));

        $source = new source_extdb_edit_queryid((int)$program2->id);
        $this->assertSame([], $source->search('', 50));
        $this->assertNull($source->label((string)$query2->id));

        // Current value is always allowed.
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program2->id,
            'type' => 'extdb',
            'datajson' => '{}',
            'auxint1' => $query2->id,
        ]);
        $this->assertSame('Kategoie 2 query', $source->label((string)$query2->id));
        $this->assertNull($source->label((string)$query0->id));

        $this->setUser($user2);

        try {
            new source_extdb_edit_queryid((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Add and update programs).',
                $ex->getMessage()
            );
        }
    }

    public function test_search_tenant(): void {
        global $CFG, $DB;

        if (!mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mulib_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mulib');
        /** @var \tool_muprog_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $syscontext = \context_system::instance();
        $tenantcatcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $tenantcatcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $programgenerator->create_program(['contextid' => $syscontext->id]);
        $program1 = $programgenerator->create_program(['contextid' => $tenantcatcontext1->id]);

        $server = $generator->create_extdb_server([]);
        $query0 = $generator->create_extdb_query([
            'contextid' => $syscontext->id,
            'name' => 'System query',
            'note' => 'grrr',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query1 = $generator->create_extdb_query([
            'contextid' => $tenantcatcontext1->id,
            'name' => 'Category 1 query',
            'note' => 'argh',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);
        $query2 = $generator->create_extdb_query([
            'contextid' => $tenantcatcontext2->id,
            'name' => 'Kategoie 2 query',
            'note' => '',
            'serverid' => $server->id,
            'component' => 'tool_muprog',
            'type' => 'allocation',
            'sqlquery' => "SELECT id AS userid FROM {$CFG->prefix}user WHERE id=2",
        ]);

        $progroleid = $this->getDataGenerator()->create_role();
        $queryroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:edit', CAP_ALLOW, $progroleid, $syscontext->id);
        assign_capability('tool/mulib:useextdb', CAP_ALLOW, $queryroleid, $syscontext->id);

        $user0 = $this->getDataGenerator()->create_user();
        role_assign($progroleid, $user0->id, $syscontext->id);
        role_assign($queryroleid, $user0->id, $syscontext->id);

        $this->setUser($user0);

        $source = new source_extdb_edit_queryid((int)$program0->id);
        $this->assertSame([(int)$query0->id => 'System query'], $source->search('', 50));

        $source = new source_extdb_edit_queryid((int)$program1->id);
        $this->assertSame(
            [(int)$query1->id => 'Category 1 query', (int)$query0->id => 'System query'],
            $source->search('', 50)
        );
        $this->assertSame([(int)$query0->id => 'System query'], $source->search('System', 50));
        $this->assertSame([(int)$query1->id => 'Category 1 query'], $source->search('argh', 50));

        // Queries from other tenants are not allowed, not even the current value.
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program1->id,
            'type' => 'extdb',
            'datajson' => '{}',
            'auxint1' => $query2->id,
        ]);
        $this->assertNull($source->label((string)$query2->id));
        $this->assertSame('Category 1 query', $source->label((string)$query1->id));
    }
}
