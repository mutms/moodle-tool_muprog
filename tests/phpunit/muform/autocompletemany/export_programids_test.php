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

use tool_muprog\muform\autocompletemany\export_programids;

/**
 * Export programs autocomplete source test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\muform\autocompletemany\export_programids
 * @covers \tool_muprog\muform\util\autocomplete\program_trait
 */
final class export_programids_test extends \advanced_testcase {
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

        $program1 = $generator->create_program([
            'fullname' => 'hokus',
            'idnumber' => 'p1',
            'description' => 'some desc 1',
            'descriptionformat' => \FORMAT_MARKDOWN,
            'archived' => 0,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);
        $program2 = $generator->create_program([
            'fullname' => 'pokus',
            'idnumber' => 'p2',
            'description' => '<b>some desc 2</b>',
            'descriptionformat' => \FORMAT_HTML,
            'archived' => 0,
            'contextid' => $catcontext1->id,
            'sources' => ['manual' => [], 'cohort' => []],
        ]);
        $program3 = $generator->create_program([
            'fullname' => 'Prog3',
            'idnumber' => 'p3',
            'archived' => 1,
            'contextid' => $syscontext->id,
            'sources' => ['manual' => []],
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $viewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('tool/muprog:export', CAP_ALLOW, $viewerroleid, $syscontext);
        role_assign($viewerroleid, $user1->id, $catcontext1->id);
        $user2 = $this->getDataGenerator()->create_user();

        $all = [(string)$program1->id, (string)$program2->id, (string)$program3->id];

        $this->setUser($user1);
        $source = new export_programids((int)$catcontext1->id);
        $this->assertSame([(int)$catcontext1->id], $source->get_args());
        $this->assertSame([(int)$program2->id => 'pokus'], $source->search('', 50, []));
        $this->assertSame([], $source->search('', 50, [(string)$program2->id]));
        $this->assertSame([(int)$program2->id => 'pokus'], $source->labels($all));
        $this->assertSame([], $source->validate($all));
        try {
            new export_programids((int)$syscontext->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setAdminUser();
        $source = new export_programids((int)$syscontext->id);
        $this->assertSame(
            [(int)$program1->id => 'hokus', (int)$program2->id => 'pokus', (int)$program3->id => 'Prog3'],
            $source->search('', 50, [])
        );
        $this->assertSame(
            [(int)$program1->id => 'hokus', (int)$program3->id => 'Prog3'],
            $source->search('', 50, [(string)$program2->id])
        );
        $this->assertSame([(int)$program2->id => 'pokus'], $source->search('desc 2', 50, []));
        $this->assertNull($source->search('', 2, []));
        $this->assertSame(
            [(int)$program1->id => 'hokus', (int)$program2->id => 'pokus', (int)$program3->id => 'Prog3'],
            $source->labels($all)
        );
        $this->assertSame([], $source->labels(['0', '-1', 'abc', '', (string)($program3->id + 100)]));

        $this->setUser($user2);
        try {
            new export_programids((int)$catcontext1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }
}
