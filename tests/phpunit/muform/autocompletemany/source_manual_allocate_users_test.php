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

use tool_muprog\muform\autocompletemany\source_manual_allocate_users;
use tool_mulib\local\mulib;

/**
 * Manual allocation users autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocompletemany\source_manual_allocate_users
 */
final class source_manual_allocate_users_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_search(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(
            ['idnumber' => 'pokus', 'contextid' => $catcontext2->id, 'sources' => ['manual' => []]]
        );

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 3']);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 4']);
        $user5 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 5']);

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user5->id, $catcontext2);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $source = new source_manual_allocate_users((int)$program1->id);
        $this->assertSame([(int)$program1->id], $source->get_args());

        $result = $source->search('', 10, []);
        $this->assertCount(4, $result); // Admin is included.
        foreach ($result as $userid => $label) {
            if ($userid == $user3->id) {
                $this->assertStringContainsString(\fullname($user3, true), $label);
            } else if ($userid == $user4->id) {
                $this->assertStringContainsString(\fullname($user4, true), $label);
            } else if ($userid == $user5->id) {
                $this->assertStringContainsString(\fullname($user5, true), $label);
            } else if ($userid == $admin->id) {
                $this->assertStringContainsString(\fullname($admin, true), $label);
            } else {
                $this->fail('Unexpected user returned: ' . $label);
            }
        }
        $this->assertNull($source->search('', 3, []));

        $result = $source->search('Prijmeni', 10, []);
        $this->assertCount(3, $result); // Admin is NOT included.
        foreach ($result as $userid => $label) {
            if ($userid == $user3->id) {
                $this->assertStringContainsString(\fullname($user3, true), $label);
            } else if ($userid == $user4->id) {
                $this->assertStringContainsString(\fullname($user4, true), $label);
            } else if ($userid == $user5->id) {
                $this->assertStringContainsString(\fullname($user5, true), $label);
            } else {
                $this->fail('Unexpected user returned: ' . $label);
            }
        }

        $result = $source->search('Prijmeni', 10, [(string)$user3->id, (string)$user4->id]);
        $this->assertSame([(int)$user5->id], array_keys($result));

        $this->setUser($user5);
        try {
            new source_manual_allocate_users((int)$program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf('required_capability_exception', $ex);
            $this->assertSame(
                'Sorry, but you do not currently have permissions to do that (Allocate users to programs).',
                $ex->getMessage()
            );
        }

        $this->setUser($user5);
        $source = new source_manual_allocate_users((int)$program2->id);
        $result = $source->search('', 10, []);
        $this->assertCount(6, $result);
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
            ['idnumber' => 'prg2', 'contextid' => $tenant1catcontext->id, 'sources' => ['manual' => []]]
        );
        $program2 = $generator->create_program(
            ['idnumber' => 'prg3', 'contextid' => $tenant2catcontext->id, 'sources' => ['manual' => []]]
        );

        $user0 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2', 'tenantid' => $tenant2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $source = new source_manual_allocate_users((int)$program0->id);
        $this->assertSame(
            [(int)$user0->id, (int)$user1->id, (int)$user2->id, (int)$admin->id],
            array_keys($source->search('', 50, []))
        );

        $source = new source_manual_allocate_users((int)$program1->id);
        $this->assertSame([(int)$user1->id], array_keys($source->search('', 50, [])));
        $this->assertSame([(int)$user1->id], array_keys($source->labels([(string)$user1->id, (string)$user2->id])));

        $source = new source_manual_allocate_users((int)$program2->id);
        $this->assertSame([(int)$user2->id], array_keys($source->search('', 50, [])));

        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant1->id);

        $source = new source_manual_allocate_users((int)$program0->id);
        $this->assertSame([(int)$user1->id], array_keys($source->search('', 50, [])));

        $source = new source_manual_allocate_users((int)$program1->id);
        $this->assertSame([(int)$user1->id], array_keys($source->search('', 50, [])));

        $source = new source_manual_allocate_users((int)$program2->id);
        $this->assertSame([(int)$user2->id], array_keys($source->search('', 50, [])));
    }

    public function test_labels_validate(): void {
        global $DB, $CFG;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'email' => 'user1@example.com']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2', 'email' => 'user2@example.com']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 3', 'suspended' => 1]);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 4', 'deleted' => 1]);

        \tool_muprog\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id]);

        $this->setAdminUser();
        $source = new source_manual_allocate_users((int)$program1->id);

        $values = [
            (string)$user1->id, (string)$user2->id, (string)$user3->id, (string)$user4->id,
            (string)$CFG->siteguest, '0', 'abc', '', (string)($user4->id + 100),
        ];
        $labels = $source->labels($values);
        $this->assertSame([(int)$user2->id, (int)$user3->id], array_keys($labels));
        $this->assertStringContainsString(\fullname($user2, true), $labels[$user2->id]);
        $this->assertStringContainsString('user2@example.com', $labels[$user2->id]);
        $this->assertStringContainsString(\fullname($user3, true), $labels[$user3->id]);

        $this->assertSame([(int)$user3->id => 'Suspended user'], $source->validate($values));
        $this->assertSame([], $source->validate([(string)$user1->id, (string)$user2->id]));
    }
}
