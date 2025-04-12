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

namespace tool_muprog\phpunit\external;

/**
 * External API for form import program allocation
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\external\form_export_programids
 */
final class form_export_programids_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_execute(): void {
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
            'public' => 1,
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
            'public' => 0,
            'archived' => 0,
            'contextid' => $catcontext1->id,
            'sources' => ['manual' => [], 'cohort' => []],
            'cohorts' => [$cohort1->id, $cohort2->id],
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'public' => 1,
            'archived' => 1,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:export', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);

        $this->setUser($user1->id);
        $response = \tool_muprog\external\form_export_programids::execute('');
        $results = \tool_muprog\external\form_export_programids::clean_returnvalue(\tool_muprog\external\form_export_programids::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $this->assertCount(1, $results['list']);

        $this->assertNull(\tool_muprog\external\form_export_programids::validate_programids([$program2->id]));
        $this->assertNotNull(\tool_muprog\external\form_export_programids::validate_programids([$program2->id, $program1->id]));

        $this->setAdminUser();
        $response = \tool_muprog\external\form_export_programids::execute('');
        $results = \tool_muprog\external\form_export_programids::clean_returnvalue(\tool_muprog\external\form_export_programids::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $this->assertCount(3, $results['list']);
        $this->assertNull(\tool_muprog\external\form_export_programids::validate_programids([$program2->id]));
        $this->assertNull(\tool_muprog\external\form_export_programids::validate_programids([$program3->id, $program2->id, $program1->id]));
    }
}
