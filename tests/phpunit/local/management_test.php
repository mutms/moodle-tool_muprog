<?php
// This file is part of Programs for Moodle™.
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

namespace tool_muprog\phpunit\local;

/**
 * Programs management helper test.
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\management
 */
final class management_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_get_management_url(): void {
        global $DB;

        $syscontext = \context_system::instance();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $program2 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program3 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program4 = $generator->create_program(['contextid' => $catcontext2->id]);

        $admin = get_admin();
        $guest = guest_user();
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_assign($managerrole->id, $manager->id, $catcontext2->id);

        $viewer = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:view', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $viewer->id, $catcontext1->id);

        $this->setUser(null);
        $this->assertNull(\tool_muprog\local\management::get_management_url());

        $this->setUser($guest);
        $this->assertNull(\tool_muprog\local\management::get_management_url());

        $this->setUser($admin);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php');
        $this->assertSame((string)$expected, (string)\tool_muprog\local\management::get_management_url());

        $this->setUser($manager);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext2->id]);
        $this->assertSame((string)$expected, (string)\tool_muprog\local\management::get_management_url());

        $this->setUser($viewer);
        $expected = new \moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext1->id]);
        $this->assertSame((string)$expected, (string)\tool_muprog\local\management::get_management_url());
    }

    public function test_fetch_programs(): void {
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program(['fullname' => 'hokus']);
        $program2 = $generator->create_program(['idnumber' => 'pokus']);
        $program3 = $generator->create_program();
        $program4 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program5 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program6 = $generator->create_program(['contextid' => $catcontext2->id]);

        $program3 = \tool_muprog\local\program::archive($program3->id);
        $program5 = \tool_muprog\local\program::archive($program5->id);

        $result = \tool_muprog\local\management::fetch_programs(null, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(4, $result['programs']);
        $this->assertSame(4, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program1->id, $programs);
        $this->assertArrayHasKey($program2->id, $programs);
        $this->assertArrayHasKey($program4->id, $programs);
        $this->assertArrayHasKey($program6->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs(null, false, 'hokus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(1, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program1->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs(null, false, 'okus', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['programs']);
        $this->assertSame(2, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program1->id, $programs);
        $this->assertArrayHasKey($program2->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs(null, true, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['programs']);
        $this->assertSame(2, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program3->id, $programs);
        $this->assertArrayHasKey($program5->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs($catcontext1, false, '', 0, 100, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(1, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program4->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs(null, false, '', 1, 2, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(2, $result['programs']);
        $this->assertSame(4, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program4->id, $programs);
        $this->assertArrayHasKey($program6->id, $programs);

        $result = \tool_muprog\local\management::fetch_programs(null, false, '', 3, 1, 'id ASC');
        $this->assertCount(2, $result);
        $this->assertCount(1, $result['programs']);
        $this->assertSame(4, $result['totalcount']);
        $programs = $result['programs'];
        $this->assertArrayHasKey($program6->id, $programs);
    }

    public function test_get_used_contexts_menu(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $category3 = $this->getDataGenerator()->create_category([]);
        $catcontext3 = \context_coursecat::instance($category3->id);

        $user = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user->id, $catcontext1);
        role_assign($managerrole->id, $user->id, $catcontext3);
        // Undo work hackery.
        $userrole = $DB->get_record('role', ['shortname' => 'user'], '*', MUST_EXIST);
        assign_capability('moodle/category:viewcourselist', CAP_ALLOW, $managerrole->id, $syscontext->id);
        $coursecatcache = \cache::make('core', 'coursecat');
        $coursecatcache->purge();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();
        $program4 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program5 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program6 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->setAdminUser();
        $expected = [
            0 => 'All programs (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
        ];
        $contexts = \tool_muprog\local\management::get_used_contexts_menu($syscontext);
        $this->assertSame($expected, $contexts);

        $expected = [
            0 => 'All programs (6)',
            $syscontext->id => 'System (3)',
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext2->id => $category2->name . ' (1)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = \tool_muprog\local\management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);

        $this->setUser($user);
        $coursecatcache->purge();

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
        ];
        $contexts = \tool_muprog\local\management::get_used_contexts_menu($catcontext1);
        $this->assertSame($expected, $contexts);

        $expected = [
            $catcontext1->id => $category1->name . ' (2)',
            $catcontext3->id => $category3->name,
        ];
        $contexts = \tool_muprog\local\management::get_used_contexts_menu($catcontext3);
        $this->assertSame($expected, $contexts);
    }

    public function test_get_program_search_query(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $program1 = $generator->create_program(['fullname' => 'First program', 'idnumber' => 'PRG1', 'description' => 'prvni popis']);
        $program2 = $generator->create_program(['fullname' => 'Second program', 'idnumber' => 'PRG2', 'description' => 'druhy popis']);
        $program3 = $generator->create_program(['fullname' => 'Third program', 'idnumber' => 'PR3', 'description' => 'treti popis', 'contextid' => $catcontext1->id]);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query(null, 'First', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query(null, 'First', '');
        $programids = $DB->get_fieldset_sql("SELECT * FROM {tool_muprog_program} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$program1->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query(null, 'PRG', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query(null, 'popis', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id, $program3->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query(null, '', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program1->id, $program2->id, $program3->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query($catcontext1, '', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query($catcontext1, 'PR', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query($catcontext1, 'PR', '');
        $programids = $DB->get_fieldset_sql("SELECT * FROM {tool_muprog_program} WHERE $search ORDER BY id ASC", $params);
        $this->assertSame([$program3->id], $programids);

        list($search, $params) = \tool_muprog\local\management::get_program_search_query($catcontext1, 'PRG', 'p');
        $programids = $DB->get_fieldset_sql("SELECT p.* FROM {tool_muprog_program} AS p WHERE $search ORDER BY p.id ASC", $params);
        $this->assertSame([], $programids);
    }

    public function test_fetch_current_cohorts_menu(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $cohort1 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort A']);
        $cohort2 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort B']);
        $cohort3 = $this->getDataGenerator()->create_cohort(['name' => 'Cohort C']);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();

        \tool_muprog\local\program::update_visibility((object)[
            'id' => $program1->id,
            'public' => 0,
            'cohorts' => [$cohort1->id, $cohort2->id],
        ]);
        \tool_muprog\local\program::update_visibility((object)[
            'id' => $program2->id,
            'public' => 1,
            'cohorts' => [$cohort3->id],
        ]);

        $expected = [
            $cohort1->id => $cohort1->name,
            $cohort2->id => $cohort2->name,
        ];
        $menu = \tool_muprog\local\management::fetch_current_cohorts_menu($program1->id);
        $this->assertSame($expected, $menu);

        $menu = \tool_muprog\local\management::fetch_current_cohorts_menu($program3->id);
        $this->assertSame([], $menu);
    }

    public function test_setup_index_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        \tool_muprog\local\management::setup_index_page(
            new \moodle_url('/admin/tool/muprog/management/index.php'),
            $syscontext,
            0
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        \tool_muprog\local\management::setup_index_page(
            new \moodle_url('/admin/tool/muprog/management/index.php'),
            $syscontext,
            $syscontext->id
        );
    }

    public function test_setup_program_page(): void {
        global $PAGE;

        $syscontext = \context_system::instance();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program1 = $generator->create_program();
        $user = $this->getDataGenerator()->create_user();

        $PAGE = new \moodle_page();
        \tool_muprog\local\management::setup_program_page(
            new \moodle_url('/admin/tool/muprog/management/new.php'),
            $syscontext,
            $program1,
            'program_general'
        );

        $this->setUser($user);
        $PAGE = new \moodle_page();
        \tool_muprog\local\management::setup_program_page(
            new \moodle_url('/admin/tool/muprog/management/new.php'),
            $syscontext,
            $program1,
            'program_general'
        );
    }
}
