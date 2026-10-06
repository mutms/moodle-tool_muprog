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
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_muprog\phpunit\local;

use tool_muprog\local\program;
use core\exception\invalid_parameter_exception;
use core\exception\moodle_exception;

/**
 * Program helper test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\program
 */
final class program_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_create(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$syscontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame('', $program->description);
        $this->assertSame('1', $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->archived);
        $this->assertSame('0', $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($data->archived, $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Yet another program',
            'idnumber' => 'SP3',
            'contextid' => $catcontext->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('1 Feb 2030 00:00 GMT')],
            'duedate' => ['type' => 'delay', 'delay' => 'P1D'],
            'enddate' => (object)['type' => 'delay', 'delay' => 'P2M'],
        ];

        $this->setCurrentTimeStart();
        $program = program::create($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame('{"type":"date","date":1896134400}', $program->startdatejson);
        $this->assertSame('{"type":"delay","delay":"P1D"}', $program->duedatejson);
        $this->assertSame('{"type":"delay","delay":"P2M"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);
        $this->assertFalse($DB->record_exists('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']));

        $data = (object)[
            'fullname' => 'Program with manual source',
            'idnumber' => 'SP4',
            'contextid' => $syscontext->id,
            'addsources' => ['manual' => 1],
        ];
        $program = program::create($data);
        $this->assertTrue($DB->record_exists('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']));

        $data = (object)[
            'fullname' => 'Program without manual source',
            'idnumber' => 'SP5',
            'contextid' => $syscontext->id,
            'addsources' => ['manual' => 0],
        ];
        $program = program::create($data);
        $this->assertFalse($DB->record_exists('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']));
    }

    public function test_update_general(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);

        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $catcontext->id,
        ];

        $oldprogram = program::create($data);

        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_general($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $this->assertDebuggingNotCalled();
        $data = (object)[
            'id' => $oldprogram->id,
            'archived' => 1,
        ];
        $program = program::update_general($data);
        $this->assertDebuggingCalled('Use program::archive() and program::restore() to change archived flag');
        $this->assertSame('0', $program->archived);
        $this->assertSame((string)$catcontext->id, $program->contextid);
    }

    public function test_move_customfields(): void {
        global $DB;

        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \core_customfield_generator $cfgenerator */
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $pcategory = $cfgenerator->create_category(['component' => 'tool_muprog', 'area' => 'program']);
        $pfield = $cfgenerator->create_field(['categoryid' => $pcategory->get('id'), 'shortname' => 'pf', 'type' => 'text']);
        $acategory = $cfgenerator->create_category(['component' => 'tool_muprog', 'area' => 'allocation']);
        $afield = $cfgenerator->create_field(['categoryid' => $acategory->get('id'), 'shortname' => 'af', 'type' => 'text']);

        $program1 = $generator->create_program(['contextid' => $syscontext->id, 'sources' => ['manual' => []]]);
        $program2 = $generator->create_program(['contextid' => $syscontext->id, 'sources' => ['manual' => []]]);
        $user = $this->getDataGenerator()->create_user();
        $allocation1 = $generator->create_program_allocation(['programid' => $program1->id, 'userid' => $user->id]);
        $allocation2 = $generator->create_program_allocation(['programid' => $program2->id, 'userid' => $user->id]);
        $cfgenerator->add_instance_data($pfield, $program1->id, 'p1');
        $cfgenerator->add_instance_data($pfield, $program2->id, 'p2');
        $cfgenerator->add_instance_data($afield, $allocation1->id, 'a1');
        $cfgenerator->add_instance_data($afield, $allocation2->id, 'a2');

        program::move($program1->id, $catcontext->id);

        $this->assertEquals($catcontext->id, $DB->get_field('customfield_data', 'contextid', ['fieldid' => $pfield->get('id'), 'instanceid' => $program1->id]));
        $this->assertEquals($catcontext->id, $DB->get_field('customfield_data', 'contextid', ['fieldid' => $afield->get('id'), 'instanceid' => $allocation1->id]));
        $this->assertEquals($syscontext->id, $DB->get_field('customfield_data', 'contextid', ['fieldid' => $pfield->get('id'), 'instanceid' => $program2->id]));
        $this->assertEquals($syscontext->id, $DB->get_field('customfield_data', 'contextid', ['fieldid' => $afield->get('id'), 'instanceid' => $allocation2->id]));
    }

    public function test_move(): void {
        $syscontext = \context_system::instance();
        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::create($data);
        $this->assertSame((string)$syscontext->id, $program->contextid);

        $program = program::move($program->id, $catcontext->id);
        $this->assertSame((string)$catcontext->id, $program->contextid);

        $program = program::move($program->id, $syscontext->id);
        $this->assertSame((string)$syscontext->id, $program->contextid);

        try {
            program::move($program->id, $coursecontext->id);
            $this->fail('Exception expected');
        } catch (moodle_exception $ex) {
            $this->assertInstanceOf(invalid_parameter_exception::class, $ex);
            $this->assertSame('Invalid parameter value detected (System or category context expected)', $ex->getMessage());
        }

        // Test tags are not moved.

        $data = [
            'fullname' => 'Program 2',
            'idnumber' => 'c2',
            'contextid' => $catcontext->id,
            'tags' => ['hokus', 'pokus'],
        ];
        $program2 = program::create((object)$data);
        $this->assertEqualsCanonicalizing(
            ['hokus', 'pokus'],
            \core_tag_tag::get_item_tags_array('tool_muprog', 'tool_muprog_program', $program2->id)
        );
        $tags = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program2->id);
        foreach ($tags as $tag) {
            $this->assertEquals($syscontext->id, $tag->taginstancecontextid);
        }

        $program2 = program::move($program2->id, $syscontext->id);
        $this->assertEqualsCanonicalizing(
            ['hokus', 'pokus'],
            \core_tag_tag::get_item_tags_array('tool_muprog', 'tool_muprog_program', $program2->id)
        );
        $tags = \core_tag_tag::get_item_tags('tool_muprog', 'tool_muprog_program', $program2->id);
        foreach ($tags as $tag) {
            $this->assertEquals($syscontext->id, $tag->taginstancecontextid);
        }

        // Test images do not move.

        $admin = get_admin();
        $this->setUser($admin);
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);

        $draftid1 = \file_get_unused_draft_itemid();
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid1,
            'filepath' => '/',
            'filename' => 'someimage.jpg',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');
        $draftid2 = \file_get_unused_draft_itemid();
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid2,
            'filepath' => '/',
            'filename' => 'otherimage.jpg',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $data = [
            'fullname' => 'Program 3',
            'idnumber' => 'c3',
            'contextid' => $catcontext->id,
            'description_editor' => ['text' => 'xx', 'format' => FORMAT_HTML, 'itemid' => $draftid1],
            'image' => $draftid2,
        ];
        $program3 = program::create((object)$data);
        $this->assertTrue($fs->file_exists($syscontext->id, 'tool_muprog', 'description', $program3->id, '/', 'someimage.jpg'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'tool_muprog', 'image', $program3->id, '/', 'otherimage.jpg'));

        $program3 = program::move($program3->id, $syscontext->id);
        $this->assertTrue($fs->file_exists($syscontext->id, 'tool_muprog', 'description', $program3->id, '/', 'someimage.jpg'));
        $this->assertTrue($fs->file_exists($syscontext->id, 'tool_muprog', 'image', $program3->id, '/', 'otherimage.jpg'));
    }

    public function test_update_image(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $program = program::create($data);

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'image.png',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $program = program::update_general((object)[
            'id' => $program->id,
            'image' => $draftid,
        ]);
        $this->assertSame('{"image":"image.png"}', $program->presentationjson);
    }

    public function test_get_image_url(): void {
        $syscontext = \context_system::instance();

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = \file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'image.png',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'image' => $draftid,
        ];
        $program1 = program::create($data);
        $this->assertSame('{"image":"image.png"}', $program1->presentationjson);

        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $syscontext->id,
        ];
        $program2 = program::create($data);
        $this->assertSame('[]', $program2->presentationjson);

        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/{$syscontext->id}/tool_muprog/image/{$program1->id}/image.png",
            program::get_image_url($program1, false)->out(false)
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/{$syscontext->id}/tool_muprog/image/{$program1->id}/image.png",
            program::get_image_url($program1, true)->out(false)
        );

        $this->assertSame(
            null,
            program::get_image_url($program2, false)
        );
        $this->assertSame(
            "https://www.example.com/moodle/pluginfile.php/{$syscontext->id}/tool_muprog/image/{$program2->id}/geopattern.svg",
            program::get_image_url($program2, true)->out(false)
        );
    }

    public function test_get_image_geopattern(): void {
        $geopattern = program::get_image_geopattern(77);
        $this->assertStringStartsWith(
            '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="72" height="83"><rect x="0" y="0" width="100%" height="100%"',
            $geopattern->toSVG()
        );
    }

    public function test_archive(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'archived' => 0,
        ];

        $program = program::create($data);
        $this->assertSame('0', $program->archived);

        $program = program::archive($program->id);
        $this->assertSame('1', $program->archived);

        $program = program::archive($program->id);
        $this->assertSame('1', $program->archived);
    }

    public function test_restore(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
            'archived' => 1,
        ];

        $program = program::create($data);
        $this->assertSame('1', $program->archived);

        $program = program::restore($program->id);
        $this->assertSame('0', $program->archived);

        $program = program::restore($program->id);
        $this->assertSame('0', $program->archived);
    }

    public function test_update_allocation(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::create($data);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_allocation($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame($oldprogram->contextid, $program->contextid);
        $this->assertSame($oldprogram->fullname, $program->fullname);
        $this->assertSame($oldprogram->idnumber, $program->idnumber);
        $this->assertSame($oldprogram->description, $program->description);
        $this->assertSame($oldprogram->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($oldprogram->archived, $program->archived);
        $this->assertSame($oldprogram->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);
    }

    public function test_import_allocation(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program1 = $generator->create_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
            'sources' => [
                'manual' => [],
                'approval' => [],
                'cohort' => ['cohortids' => [$cohort2->id]],
                'selfallocation' => [],
            ],
        ];
        $program2 = $generator->create_program($data);
        $data = (object)[
            'id' => $program2->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program2 = program::update_scheduling($data);
        $scohort2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'cohort'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
        ];
        $program1x = program::import_allocation($data);
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importallocationstart' => 1,
            'importallocationend' => 1,
        ];
        $program1x = program::import_allocation($data);
        $program1->timeallocationstart = $program2->timeallocationstart;
        $program1->timeallocationend = $program2->timeallocationend;
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importprogramstart' => 1,
            'importprogramdue' => 1,
            'importprogramend' => 1,
        ];
        $program1x = program::import_allocation($data);
        $program1->startdatejson = $program2->startdatejson;
        $program1->duedatejson = $program2->duedatejson;
        $program1->enddatejson = $program2->enddatejson;
        $this->assertSame((array)$program1, (array)$program1x);

        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(0, $sources1);

        $sources2 = $DB->get_records('tool_muprog_source', ['programid' => $program2->id]);
        $this->assertCount(4, $sources2);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
        ];
        $program1x = program::import_allocation($data);
        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(1, $sources1);
        $smanual1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
            'importsourcecohort' => 1,
            'importsourceapproval' => 1,
            'importsourceselfallocation' => 1,
        ];
        $program1x = program::import_allocation($data);
        $sources1 = $DB->get_records('tool_muprog_source', ['programid' => $program1->id]);
        $this->assertCount(4, $sources1);
        $smanual1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $scohort1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);
        $sapproval1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        $sselfallocation1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $cohorts = $DB->get_records('tool_muprog_src_cohort', ['sourceid' => $scohort1->id]);
        $this->assertCount(1, $cohorts);
        $cr = reset($cohorts);
        $this->assertSame($cohort2->id, $cr->cohortid);

        $DB->delete_records('tool_muprog_src_cohort', ['sourceid' => $scohort2->id]);
        $DB->insert_record('tool_muprog_src_cohort', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort1->id]);
        $DB->insert_record('tool_muprog_src_cohort', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort3->id]);
        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcecohort' => 1,
        ];
        $program1x = program::import_allocation($data);
        $cohorts = $DB->get_records('tool_muprog_src_cohort', ['sourceid' => $scohort1->id]);
        $this->assertCount(3, $cohorts);
    }

    public function test_get_program_startdate_types(): void {
        $types = program::get_program_startdate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('allocation', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_duedate_types(): void {
        $types = program::get_program_duedate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_enddate_types(): void {
        $types = program::get_program_enddate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_update_scheduling(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $oldprogram = program::create($data);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'allocation',
            'programdue_type' => 'notset',
            'programend_type' => 'notset',
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'allocation']), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'notset']), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'notset']), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programstart_date]), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programdue_date]), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'date', 'date' => $data->programend_date]), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'delay',
            'programstart_delay' => ['type' => 'hours', 'value' => 3],
            'programdue_type' => 'delay',
            'programdue_delay' => ['type' => 'days', 'value' => 6],
            'programend_type' => 'delay',
            'programend_delay' => ['type' => 'months', 'value' => 2],
        ];
        $program = program::update_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'PT3H']), $program->startdatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'P6D']), $program->duedatejson);
        $this->assertSame(\tool_muprog\local\util::json_encode(['type' => 'delay', 'delay' => 'P2M']), $program->enddatejson);
    }

    public function test_delete(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = $generator->create_program($data);

        program::delete($program->id);
        $this->assertFalse($DB->record_exists('tool_muprog_program', ['id' => $program->id]));

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = $generator->create_program($data);
        $user1 = $this->getDataGenerator()->create_user();
        $allocation1 = $generator->create_program_allocation(['programid' => $program->id, 'userid' => $user1->id]);

        $favservice = \core_favourites\service_factory::get_service_for_component('tool_muprog');
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($user1->id));
        $ufservice->create_favourite('tool_muprog', 'programs', $program->id, $syscontext);
        $this->assertTrue($ufservice->favourite_exists('tool_muprog', 'programs', $program->id, $syscontext));

        program::delete($program->id);
        $this->assertFalse($ufservice->favourite_exists('tool_muprog', 'programs', $program->id, $syscontext));
    }

    public function test_load_content(): void {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::create($data);

        $top = program::load_content($program->id);
        $this->assertInstanceOf(\tool_muprog\local\content\top::class, $top);
    }

    public function test_category_pre_delete(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $program1->contextid);
        $this->assertSame((string)$catcontext2->id, $program2->contextid);

        program::pre_course_category_delete($category2->get_db_record());
        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $program2->contextid);

        program::pre_course_category_delete($category1->get_db_record());
        $program1 = $DB->get_record('tool_muprog_program', ['id' => $program1->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program1->contextid);
        $program2 = $DB->get_record('tool_muprog_program', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program2->contextid);
    }

    public function test_get_catalogue_item(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $guest = guest_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();
        $program3 = $generator->create_program();
        $program4 = $generator->create_program();

        // Catalogue is not active without active sections.
        $this->setUser($user1);
        $section0 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_DRAFT]);
        $item0 = $cataloggenerator->create_item(['sectionid' => $section0->id, 'type' => 'program', 'referenceid' => $program1->id]);
        $this->assertNull(program::get_catalogue_item($program1));
        $this->assertNull(program::get_catalogue_item($program1, $user1->id));
        $this->assertNull(program::get_catalogue_item($program1, $user2->id));

        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $section2 = $cataloggenerator->create_section([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
            'status' => \tool_mucatalog\local\util::STATUS_ACTIVE,
        ]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);
        $item2 = $cataloggenerator->create_item(['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program2->id]);
        $item3 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program3->id]);
        $program3 = program::archive($program3->id);

        $this->setUser($user1);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        $this->assertNull(program::get_catalogue_item($program2));
        $this->assertNull(program::get_catalogue_item($program3));
        $this->assertNull(program::get_catalogue_item($program4));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user1->id));
        $this->assertNull(program::get_catalogue_item($program2, $user1->id));
        $this->assertNull(program::get_catalogue_item($program3, $user1->id));
        $this->assertNull(program::get_catalogue_item($program4, $user1->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user2->id));
        $this->assertEquals($item2, program::get_catalogue_item($program2, $user2->id));
        $this->assertNull(program::get_catalogue_item($program3, $user2->id));
        $this->assertNull(program::get_catalogue_item($program4, $user2->id));
        $this->assertNull(program::get_catalogue_item($program1, $guest->id));
        $this->assertNull(program::get_catalogue_item($program2, $guest->id));
        $this->assertNull(program::get_catalogue_item($program1, 0));

        $this->setUser($user2);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        $this->assertEquals($item2, program::get_catalogue_item($program2));
        $this->assertNull(program::get_catalogue_item($program3));
        $this->assertNull(program::get_catalogue_item($program4));
        $this->assertNull(program::get_catalogue_item($program2, $user1->id));

        $this->setUser($guest);
        $this->assertNull(program::get_catalogue_item($program1));
        $this->setUser(null);
        $this->assertNull(program::get_catalogue_item($program1));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user1->id));

        $section1 = \tool_mucatalog\local\section::update((object)['id' => $section1->id, 'guestvisible' => 1]);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $guest->id));
        $this->setUser($guest);
        $this->assertEquals($item1, program::get_catalogue_item($program1));

        // First visible item is used when there are multiple items.
        $item4 = $cataloggenerator->create_item(['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program1->id]);
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user1->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user2->id));
        $item1 = \tool_mucatalog\local\item\program::archive($item1->id);
        $this->assertNull(program::get_catalogue_item($program1, $user1->id));
        $this->assertEquals($item4, program::get_catalogue_item($program1, $user2->id));
    }

    public function test_get_catalogue_item_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $tenantgenerator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();

        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user3 = $this->getDataGenerator()->create_user();

        $catcontext1 = \context_coursecat::instance($tenant1->categoryid);
        $catcontext2 = \context_coursecat::instance($tenant2->categoryid);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();

        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $this->setUser($user1);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        $this->assertNull(program::get_catalogue_item($program2));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user1->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user2->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user3->id));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        $program1 = program::move($program1->id, $catcontext1->id);

        $this->setUser($user1);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user1->id));
        $this->assertNull(program::get_catalogue_item($program1, $user2->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user3->id));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertNull(program::get_catalogue_item($program1));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();
        \tool_mutenancy\local\tenancy::force_current_tenantid(null);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        $this->setUser($user2);
        $this->assertNull(program::get_catalogue_item($program1));
        $this->setUser($user3);
        $this->assertEquals($item1, program::get_catalogue_item($program1));

        $program1 = program::move($program1->id, $catcontext2->id);

        $this->setUser($user1);
        $this->assertNull(program::get_catalogue_item($program1));
        $this->assertNull(program::get_catalogue_item($program1, $user1->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user2->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user3->id));
        \tool_mutenancy\local\tenancy::force_current_tenantid($tenant2->id);
        $this->assertEquals($item1, program::get_catalogue_item($program1));
        \tool_mutenancy\local\tenancy::unforce_current_tenantid();

        // Sections may be hidden from tenant members.
        $section1 = \tool_mucatalog\local\section::update((object)['id' => $section1->id, 'hiddenfromtenants' => 1]);
        $this->assertNull(program::get_catalogue_item($program1, $user1->id));
        $this->assertNull(program::get_catalogue_item($program1, $user2->id));
        $this->assertEquals($item1, program::get_catalogue_item($program1, $user3->id));
    }

    public function test_get_catalogue_item_url(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program();

        $this->setUser($user2);
        $this->assertNull(program::get_catalogue_item_url($program1));

        $section1 = $cataloggenerator->create_section([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
            'status' => \tool_mucatalog\local\util::STATUS_ACTIVE,
        ]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);

        $this->setUser($user1);
        $this->assertNull(program::get_catalogue_item_url($program1));
        $this->assertNull(program::get_catalogue_item_url($program2));

        $this->setUser($user2);
        $url = program::get_catalogue_item_url($program1);
        $this->assertInstanceOf(\core\url::class, $url);
        $this->assertSame("https://www.example.com/moodle/admin/tool/mucatalog/item.php?id=$item1->id", $url->out(false));
        $this->assertNull(program::get_catalogue_item_url($program2));
    }

    public function test_get_catalogue_actions(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user1->id);

        $program1 = $generator->create_program();
        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $program3 = $generator->create_program(['sources' => ['manual' => [], 'selfallocation' => []]]);
        $source3s = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'selfallocation'], '*', MUST_EXIST);
        $program4 = $generator->create_program(['sources' => ['selfallocation' => [], 'approval' => []]]);
        $source4s = $DB->get_record('tool_muprog_source', ['programid' => $program4->id, 'type' => 'selfallocation'], '*', MUST_EXIST);
        $source4a = $DB->get_record('tool_muprog_source', ['programid' => $program4->id, 'type' => 'approval'], '*', MUST_EXIST);
        $program5 = $generator->create_program(['sources' => ['selfallocation' => []]]);

        $section1 = $cataloggenerator->create_section([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
            'status' => \tool_mucatalog\local\util::STATUS_ACTIVE,
        ]);
        $item1 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);
        $item2 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program2->id]);
        $item3 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program3->id]);
        $item4 = $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program4->id]);

        $this->setUser($user1);
        $this->assertSame([], program::get_catalogue_actions($program1));
        $this->assertSame([], program::get_catalogue_actions($program2));
        $actions = program::get_catalogue_actions($program3);
        $this->assertCount(1, $actions);
        $this->assertStringContainsString("/admin/tool/muprog/my/source_selfallocation.php?sourceid=$source3s->id", $actions[0]);
        $actions = program::get_catalogue_actions($program4);
        $this->assertCount(2, $actions);
        $actions = implode('', $actions);
        $this->assertStringContainsString("/admin/tool/muprog/my/source_selfallocation.php?sourceid=$source4s->id", $actions);
        $this->assertStringContainsString("/admin/tool/muprog/my/source_approval_request.php?sourceid=$source4a->id", $actions);
        // Not in catalogue.
        $this->assertSame([], program::get_catalogue_actions($program5));

        // Section not visible.
        $this->setUser($user2);
        $this->assertSame([], program::get_catalogue_actions($program1));
        $this->assertSame([], program::get_catalogue_actions($program2));
        $this->assertSame([], program::get_catalogue_actions($program3));
        $this->assertSame([], program::get_catalogue_actions($program4));
        $this->assertSame([], program::get_catalogue_actions($program5));

        $this->setUser($user1);
        $program3 = program::archive($program3->id);
        $this->assertSame([], program::get_catalogue_actions($program3));
    }

    public function test_get_tagged_programs(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \tool_mucatalog_generator $cataloggenerator */
        $cataloggenerator = $this->getDataGenerator()->get_plugin_generator('tool_mucatalog');

        $syscontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        $cohort1 = $this->getDataGenerator()->create_cohort();
        cohort_add_member($cohort1->id, $user2->id);
        cohort_add_member($cohort1->id, $user3->id);

        $program1 = $generator->create_program(['fullname' => 'Prvni']);
        $program2 = $generator->create_program(['fullname' => 'Druhy']);
        $program3 = $generator->create_program(['fullname' => 'Treti', 'sources' => ['manual' => []]]);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program4 = $generator->create_program(['fullname' => 'Ctvrty', 'contextid' => $catcontext1->id]);
        $program5 = $generator->create_program(['fullname' => 'Paty']);
        $program6 = $generator->create_program(['fullname' => 'Sesty', 'contextid' => $catcontext1->id, 'sources' => ['manual' => []]]);
        $source6 = $DB->get_record('tool_muprog_source', ['programid' => $program6->id, 'type' => 'manual'], '*', MUST_EXIST);

        \tool_muprog\local\source\manual::allocate_users($program3->id, $source3->id, [$user3->id]);
        \tool_muprog\local\source\manual::allocate_users($program6->id, $source6->id, [$user3->id]);

        $section1 = $cataloggenerator->create_section(['uservisible' => 1, 'status' => \tool_mucatalog\local\util::STATUS_ACTIVE]);
        $section2 = $cataloggenerator->create_section([
            'uservisible' => 0,
            'cohortvisible' => [$cohort1->id],
            'status' => \tool_mucatalog\local\util::STATUS_ACTIVE,
        ]);
        $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program1->id]);
        $cataloggenerator->create_item(['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program2->id]);
        $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program3->id]);
        $cataloggenerator->create_item(['sectionid' => $section2->id, 'type' => 'program', 'referenceid' => $program4->id]);
        $cataloggenerator->create_item(['sectionid' => $section1->id, 'type' => 'program', 'referenceid' => $program5->id]);
        // Archived programs cannot be added to catalogue.
        $program3 = program::archive($program3->id);

        foreach ([$program1, $program2, $program3, $program4, $program6] as $program) {
            \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program->id, $syscontext, ['Tag A']);
        }
        \core_tag_tag::set_item_tags('tool_muprog', 'tool_muprog_program', $program5->id, $syscontext, ['Tag B']);
        $taga = $DB->get_record('tag', ['rawname' => 'Tag A'], '*', MUST_EXIST);
        $tagb = $DB->get_record('tag', ['rawname' => 'Tag B'], '*', MUST_EXIST);

        $link = function (\stdClass $program): string {
            return 'href="https://www.example.com/moodle/admin/tool/muprog/my/program.php?id=' . $program->id . '"';
        };

        $this->setUser($user1);
        $result = program::get_tagged_programs($taga->id, true, 0, 10);
        $this->assertSame(1, $result['totalcount']);
        $this->assertStringContainsString($link($program1), $result['content']);
        $this->assertStringContainsString('Prvni', $result['content']);
        $this->assertStringNotContainsString($link($program2), $result['content']);
        $this->assertStringNotContainsString($link($program3), $result['content']);
        $this->assertStringNotContainsString($link($program4), $result['content']);
        $this->assertStringNotContainsString($link($program5), $result['content']);
        $this->assertStringNotContainsString($link($program6), $result['content']);
        $result = program::get_tagged_programs($tagb->id, false, 0, 10);
        $this->assertSame(1, $result['totalcount']);
        $this->assertStringContainsString($link($program5), $result['content']);
        $this->assertStringNotContainsString($link($program1), $result['content']);

        $this->setUser($user2);
        $result = program::get_tagged_programs($taga->id, true, 0, 10);
        $this->assertSame(3, $result['totalcount']);
        $this->assertStringContainsString($link($program1), $result['content']);
        $this->assertStringContainsString($link($program2), $result['content']);
        $this->assertStringNotContainsString($link($program3), $result['content']);
        $this->assertStringContainsString($link($program4), $result['content']);
        $this->assertStringNotContainsString($link($program6), $result['content']);

        $this->setUser($user3);
        $result = program::get_tagged_programs($taga->id, true, 0, 10);
        $this->assertSame(4, $result['totalcount']);
        $this->assertStringContainsString($link($program1), $result['content']);
        $this->assertStringContainsString($link($program2), $result['content']);
        $this->assertStringNotContainsString($link($program3), $result['content']);
        $this->assertStringContainsString($link($program4), $result['content']);
        $this->assertStringContainsString($link($program6), $result['content']);

        // Ordered by name with paging.
        $result = program::get_tagged_programs($taga->id, true, 1, 2);
        $this->assertSame(4, $result['totalcount']);
        $this->assertStringNotContainsString($link($program4), $result['content']);
        $this->assertStringContainsString($link($program2), $result['content']);
        $this->assertStringContainsString($link($program1), $result['content']);
        $this->assertStringNotContainsString($link($program6), $result['content']);

        $result = program::get_tagged_programs($tagb->id + 1000, true, 0, 10);
        $this->assertSame(['content' => '', 'totalcount' => 0], $result);
    }

    public function test_create_draft(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $syscontext = \context_system::instance();
        $program = program::create((object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ]);
        $this->assertSame('0', $program->draft);

        $program = program::create((object)[
            'fullname' => 'Draft program',
            'idnumber' => 'SP2',
            'contextid' => $syscontext->id,
            'draft' => 1,
            'creategroups' => 1,
        ]);
        $this->assertSame('1', $program->draft);
        $this->assertSame('0', $program->archived);

        $course = $this->getDataGenerator()->create_course();
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course->id]);
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'muprog']));
        $this->assertFalse($DB->record_exists('tool_muprog_group', ['programid' => $program->id]));

        $this->assertNull(program::get_catalogue_item($program));

        // Draft flag cannot be changed when updating.
        $program = program::update_general((object)['id' => $program->id, 'draft' => 0]);
        $this->assertDebuggingCalled('Use program::release() to remove draft flag, programs cannot be returned to draft');
        $this->assertSame('1', $program->draft);

        $program = program::archive($program->id);
        $this->assertSame('1', $program->draft);
        $this->assertSame('1', $program->archived);
        $program = program::restore($program->id);
        $this->assertSame('1', $program->draft);
        $this->assertSame('0', $program->archived);
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'muprog']));
    }

    public function test_release(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user = $this->getDataGenerator()->create_user();
        $cohort = $this->getDataGenerator()->create_cohort();
        \cohort_add_member($cohort->id, $user->id);
        $course = $this->getDataGenerator()->create_course();

        $program = $generator->create_program(['draft' => 1, 'creategroups' => 1, 'sources' => ['cohort' => ['cohortids' => [$cohort->id]]]]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course->id]);
        \tool_muprog\local\allocation::fix_allocation_sources(null, null);
        \tool_muprog\local\allocation::fix_enrol_instances(null);
        $this->assertSame('1', $program->draft);
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id]));
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'muprog']));
        $this->assertFalse($DB->record_exists('tool_muprog_group', ['programid' => $program->id]));

        $sink = $this->redirectEvents();
        $program = program::release($program->id);
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof \tool_muprog\event\program_released);
        $sink->close();
        $this->assertSame('0', $program->draft);
        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertSame($program->id, (string)$event->objectid);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $user->id]));
        $this->assertTrue($DB->record_exists('enrol', ['courseid' => $course->id, 'enrol' => 'muprog', 'customint1' => $program->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_group', ['programid' => $program->id, 'courseid' => $course->id]));

        // Releasing of non-draft program does nothing.
        $sink = $this->redirectEvents();
        $program = program::release($program->id);
        $this->assertCount(0, $sink->get_events());
        $sink->close();
        $this->assertSame('0', $program->draft);

        // Released programs cannot be returned to draft.
        $program = program::update_general((object)['id' => $program->id, 'draft' => 1]);
        $this->assertDebuggingCalled('Use program::release() to remove draft flag, programs cannot be returned to draft');
        $this->assertSame('0', $program->draft);

        // Archived drafts must be restored first.
        $program2 = $generator->create_program(['draft' => 1, 'archived' => 1]);
        try {
            program::release($program2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
        }
        $program2 = program::restore($program2->id);
        $this->assertSame('1', $program2->draft);
        $program2 = program::release($program2->id);
        $this->assertSame('0', $program2->draft);
    }
}
