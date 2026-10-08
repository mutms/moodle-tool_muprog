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

namespace tool_muprog\phpunit\local\operation;

use tool_muprog\local\operation\base;
use tool_muprog\local\operation\programduplicate;
use tool_muprog\local\program;

/**
 * Program duplication test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\operation\programduplicate
 * @covers \tool_muprog\local\operation\base
 */
final class programduplicate_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // The duplication must not run inside a transaction, the same as in real life.
        $this->preventResetByRollback();
    }

    public function test_rename(): void {
        $this->assertSame('Onboarding 2027', programduplicate::rename('Onboarding 2026', '2026', '2027', ' '));
        // Only the first occurrence is replaced.
        $this->assertSame('2027 Onboarding 2026', programduplicate::rename('2026 Onboarding 2026', '2026', '2027', ' '));
        $this->assertSame('Onboarding', programduplicate::rename('Onboarding 2026', ' 2026', '', ' '));
        $this->assertSame('2027 Onboarding', programduplicate::rename('2026 Onboarding', '2026', '2027', ' '));
        $this->assertSame('Onboarding 2027', programduplicate::rename('Onboarding', '2026', '2027', ' '));
        $this->assertSame('ONB-2027', programduplicate::rename('ONB', '2026', '2027', '-'));
        $this->assertSame('ONB-2027', programduplicate::rename('ONB', '', '2027', '-'));
        $this->assertSame('onboarding 2026 2027', programduplicate::rename('onboarding 2026', 'Onboarding', '2027', ' '));

        // Multibyte texts.
        $this->assertSame('Školení jaro 2027', programduplicate::rename('Školení podzim 2026', 'podzim 2026', 'jaro 2027', ' '));
        $this->assertSame('Příliš žluťoučký kůň', programduplicate::rename('Příliš žluťoučký pes', 'pes', 'kůň', ' '));
        $this->assertSame('研修 2027年', programduplicate::rename('研修 2026年', '2026年', '2027年', ' '));
        $this->assertSame('研修-二', programduplicate::rename('研修', '一', '二', '-'));
        $this->assertSame('žluťoučký kůň a pes', programduplicate::rename('žluťoučký pes a pes', 'pes', 'kůň', ' '));
        $this->assertSame('二研修一', programduplicate::rename('一研修一', '一', '二', ' '));
    }

    public function test_get_plan(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $category1 = $this->getDataGenerator()->create_category();
        $category2 = $this->getDataGenerator()->create_category();
        $catcontext2 = \context_coursecat::instance($category2->id);
        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course A 2026', 'shortname' => 'CA2026', 'idnumber' => 'ca-2026', 'category' => $category1->id]);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course B', 'shortname' => 'CB', 'idnumber' => '', 'category' => $category1->id]);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'Course C', 'shortname' => 'CC']);

        $program = $generator->create_program(['fullname' => 'Program 2026', 'idnumber' => 'PRG2026']);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course1->id]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course2->id]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course3->id]);
        delete_course($course3->id, false);

        $operation = new programduplicate($program, '2026', '2027');
        $plan = $operation->get_plan();
        $this->assertSame('Program 2027', $plan->fullname);
        $this->assertSame('PRG2027', $plan->idnumber);
        $this->assertSame((int)$program->contextid, $plan->contextid);
        $this->assertSame([], $plan->errors);
        $this->assertCount(1, $plan->warnings);
        $this->assertSame([(int)$course1->id, (int)$course2->id], array_keys($plan->courses));
        $this->assertSame('Course A 2027', $plan->courses[$course1->id]->fullname);
        $this->assertSame('CA2027', $plan->courses[$course1->id]->shortname);
        $this->assertSame('ca-2027', $plan->courses[$course1->id]->idnumber);
        $this->assertSame((int)$category1->id, $plan->courses[$course1->id]->categoryid);
        $this->assertSame('Course B 2027', $plan->courses[$course2->id]->fullname);
        $this->assertSame('CB-2027', $plan->courses[$course2->id]->shortname);
        $this->assertSame('', $plan->courses[$course2->id]->idnumber);

        // Other program and course categories.
        $operation = new programduplicate($program, '2026', '2027', $catcontext2, programduplicate::COURSECATEGORY_PROGRAM);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertSame((int)$catcontext2->id, $plan->contextid);
        $this->assertSame((int)$category2->id, $plan->courses[$course1->id]->categoryid);
        $operation = new programduplicate($program, '2026', '2027', null, (int)$category2->id);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertSame((int)$program->contextid, $plan->contextid);
        $this->assertSame((int)$category2->id, $plan->courses[$course2->id]->categoryid);

        // Problems.
        $operation = new programduplicate($program, '2026', '2027', null, programduplicate::COURSECATEGORY_PROGRAM);
        $this->assertCount(1, $operation->get_plan()->errors);
        $operation = new programduplicate($program, '2026', '2027', null, -10);
        $this->assertCount(1, $operation->get_plan()->errors);
        $operation = new programduplicate($program, '2026', '2027', null, programduplicate::COURSECATEGORY_SAME, [-1]);
        $this->assertCount(1, $operation->get_plan()->errors);
        $operation = new programduplicate($program, '2026', ' ');
        $this->assertNotEmpty($operation->get_plan()->errors);
        $operation = new programduplicate($program, "\xC3", '2027');
        $this->assertSame(['Search and replacement texts must be valid UTF-8.'], $operation->get_plan()->errors);
        $operation = new programduplicate($program, '2026', "2027\xC3");
        $plan = $operation->get_plan();
        $this->assertSame(['Search and replacement texts must be valid UTF-8.'], $plan->errors);
        $this->assertSame([], $plan->courses);
        $this->assertSame($program->idnumber, $plan->idnumber);

        $generator->create_program(['idnumber' => 'prg2027']);
        $this->getDataGenerator()->create_course(['shortname' => 'CA2027']);
        $this->getDataGenerator()->create_course(['idnumber' => 'ca-2027']);
        $operation = new programduplicate($program, '2026', '2027');
        $errors = $operation->get_plan()->errors;
        $this->assertCount(3, $errors);
        $this->assertStringContainsString('Program idnumber is already used', $errors[0]);
        $this->assertStringContainsString('Course short name is already used', $errors[1]);
        $this->assertStringContainsString('Course idnumber is already used', $errors[2]);

        try {
            $operation->execute();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\invalid_parameter_exception::class, $ex);
        }
        $this->assertSame(0, $DB->count_records('tool_muprog_operation'));

        // Duplication cannot be executed in a transaction.
        $this->assertFalse($DB->is_transaction_started());
        $operation = new programduplicate($program, '2026', '2030');
        $this->assertSame([], $operation->get_plan()->errors);
        $trans = $DB->start_delegated_transaction();
        try {
            $operation->execute();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
            $this->assertStringContainsString('Programs cannot be duplicated inside a transaction', $ex->getMessage());
        }
        $trans->allow_commit();
        $this->assertFalse($DB->record_exists('tool_muprog_program', ['idnumber' => 'PRG2030']));

        // Only site administrators can duplicate programs, courses are copied as current user.
        $manager = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $manager->id, \context_system::instance()->id);
        foreach ([$manager, null] as $user) {
            $this->setUser($user);
            $operation = new programduplicate($program, '2026', '2030');
            $this->assertSame([], $operation->get_plan()->errors);
            try {
                $operation->execute();
                $this->fail('Exception expected');
            } catch (\core\exception\moodle_exception $ex) {
                $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
                $this->assertStringContainsString('Programs can be duplicated by site administrators only', $ex->getMessage());
            }
        }
        $this->assertFalse($DB->record_exists('tool_muprog_program', ['idnumber' => 'PRG2030']));
    }

    public function test_execute(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/group/lib.php');
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher'], '*', MUST_EXIST);
        $category1 = $this->getDataGenerator()->create_category();
        $cohort = $this->getDataGenerator()->create_cohort();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        \cohort_add_member($cohort->id, $student->id);

        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Course A 2026',
            'shortname' => 'CA2026',
            'idnumber' => 'ca-2026',
            'category' => $category1->id,
            'enablecompletion' => 1,
        ]);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course B', 'shortname' => 'CB', 'category' => $category1->id]);
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course1->id, 'name' => 'Some page']);
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, $teacherrole->id);
        $teachergroup = $this->getDataGenerator()->create_group(['courseid' => $course1->id, 'name' => 'Teacher group']);
        // Other enrolment methods must not be copied, manual one keeps its settings.
        $selfplugin = enrol_get_plugin('self');
        $selfplugin->add_instance($course1, ['status' => ENROL_INSTANCE_ENABLED, 'name' => 'Open door', 'roleid' => $teacherrole->id]);
        $cohortplugin = enrol_get_plugin('cohort');
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $cohortplugin->add_instance($course1, ['customint1' => $cohort->id, 'roleid' => $studentrole->id]);
        $DB->set_field('enrol', 'enrolperiod', 123456, ['courseid' => $course1->id, 'enrol' => 'manual']);
        $this->assertTrue($DB->record_exists('enrol', ['courseid' => $course1->id, 'enrol' => 'self', 'name' => 'Open door']));
        $this->assertTrue($DB->record_exists('enrol', ['courseid' => $course1->id, 'enrol' => 'cohort']));

        $program = $generator->create_program([
            'fullname' => 'Program 2026',
            'idnumber' => 'PRG2026',
            'description' => 'Some description',
            'creategroups' => 1,
            'sources' => ['manual' => [], 'cohort' => ['cohortids' => [$cohort->id]], 'selfallocation' => []],
        ]);
        $set = $generator->create_program_item(['programid' => $program->id, 'fullname' => 'Some set', 'sequencetype' => 'allinorder']);
        $generator->create_program_item(['programid' => $program->id, 'parent' => 'Some set', 'courseid' => $course1->id, 'points' => 3]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course2->id]);
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $student->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_group', ['programid' => $program->id, 'courseid' => $course1->id]));
        $this->assertTrue(is_enrolled(\context_course::instance($course1->id), $student, '', true));
        $programgroupname = $DB->get_field('groups', 'name', ['id' => $DB->get_field('tool_muprog_group', 'groupid', ['programid' => $program->id, 'courseid' => $course1->id])]);

        $messages = [];
        $operation = new programduplicate($program, '2026', '2027', null, programduplicate::COURSECATEGORY_SAME, [$teacherrole->id]);
        $newprogram = $operation->execute(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
        $this->assertNotEmpty($messages);

        // Program.
        $this->assertSame('Program 2027', $newprogram->fullname);
        $this->assertSame('PRG2027', $newprogram->idnumber);
        $this->assertSame('1', $newprogram->draft);
        $this->assertSame('0', $newprogram->archived);
        $this->assertSame($program->contextid, $newprogram->contextid);
        $this->assertSame('Some description', $newprogram->description);
        $this->assertSame('1', $newprogram->creategroups);
        $this->assertSame($program->startdatejson, $newprogram->startdatejson);
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $newprogram->id]));

        // Sources.
        $types = $DB->get_fieldset_select('tool_muprog_source', 'type', "programid = ?", [$newprogram->id]);
        sort($types);
        $this->assertSame(['cohort', 'manual', 'selfallocation'], $types);
        $cohortsource = $DB->get_record('tool_muprog_source', ['programid' => $newprogram->id, 'type' => 'cohort'], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('tool_muprog_src_cohort', ['sourceid' => $cohortsource->id, 'cohortid' => $cohort->id]));

        // Operation is gone after success, the program is not frozen.
        $this->assertSame(0, $DB->count_records('tool_muprog_operation'));
        $this->assertNull(base::get_pending_operation($newprogram->id));
        $this->assertSame([], $operation->get_problems());
        // The operation record cannot be used any more.
        $this->assertNull($operation->get_operation_id());
        foreach (['get_record', 'get_state'] as $method) {
            try {
                $operation->{$method}();
                $this->fail('Exception expected');
            } catch (\core\exception\moodle_exception $ex) {
                $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
                $this->assertStringContainsString('Operation record does not exist', $ex->getMessage());
            }
        }

        // Courses.
        $newcourse1 = $DB->get_record('course', ['shortname' => 'CA2027'], '*', MUST_EXIST);
        $newcourse2 = $DB->get_record('course', ['shortname' => 'CB-2027'], '*', MUST_EXIST);
        $this->assertSame('Course A 2027', $newcourse1->fullname);
        $this->assertSame('ca-2027', $newcourse1->idnumber);
        $this->assertSame($category1->id, $newcourse1->category);
        $this->assertSame('1', $newcourse1->enablecompletion);
        $this->assertSame('Course B 2027', $newcourse2->fullname);
        $this->assertSame('', $newcourse2->idnumber);
        $this->assertTrue($DB->record_exists('page', ['course' => $newcourse1->id, 'name' => 'Some page']));

        // Only manual enrolment method is copied.
        $this->assertSame(['manual'], $DB->get_fieldset_select('enrol', 'enrol', "courseid = ?", [$newcourse1->id]));
        $this->assertSame('123456', $DB->get_field('enrol', 'enrolperiod', ['courseid' => $newcourse1->id, 'enrol' => 'manual']));
        $this->assertSame(['manual'], $DB->get_fieldset_select('enrol', 'enrol', "courseid = ?", [$newcourse2->id]));

        // No program enrolments, groups or students, teachers are kept.
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => $newcourse1->id, 'enrol' => 'muprog']));
        $this->assertFalse($DB->record_exists('enrol', ['courseid' => $newcourse2->id, 'enrol' => 'muprog']));
        $newcontext1 = \context_course::instance($newcourse1->id);
        $this->assertFalse(is_enrolled($newcontext1, $student));
        $this->assertTrue(is_enrolled($newcontext1, $teacher, '', true));
        $this->assertTrue(user_has_role_assignment($teacher->id, $teacherrole->id, $newcontext1->id));
        $this->assertSame(1, $DB->count_records('user_enrolments', ['enrolid' => $DB->get_field('enrol', 'id', ['courseid' => $newcourse1->id, 'enrol' => 'manual'])]));
        $groupnames = $DB->get_fieldset_select('groups', 'name', "courseid = ?", [$newcourse1->id]);
        $this->assertSame(['Teacher group'], $groupnames);
        $this->assertNotContains($programgroupname, $groupnames);
        $this->assertSame(0, $DB->count_records('groups_members', ['groupid' => $DB->get_field('groups', 'id', ['courseid' => $newcourse1->id])]));
        $this->assertSame(0, $DB->count_records('groups', ['courseid' => $newcourse2->id]));
        $this->assertFalse($DB->record_exists('tool_muprog_group', ['programid' => $newprogram->id]));

        // Content.
        $top = program::load_content($newprogram->id);
        $children = $top->get_children();
        $this->assertCount(2, $children);
        $this->assertSame('Some set', $children[0]->get_fullname());
        $this->assertSame('allinorder', $children[0]->get_sequencetype());
        $setchildren = $children[0]->get_children();
        $this->assertCount(1, $setchildren);
        $this->assertSame((int)$newcourse1->id, (int)$setchildren[0]->get_courseid());
        $this->assertSame(3, (int)$setchildren[0]->get_points());
        $this->assertSame((int)$newcourse2->id, (int)$children[1]->get_courseid());

        // Original is not changed.
        $this->assertSame(2, $DB->count_records_select('tool_muprog_item', "programid = ? AND courseid IS NOT NULL", [$program->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $student->id]));

        // Release the new program.
        $newprogram = program::release($newprogram->id);
        $this->assertTrue($DB->record_exists('enrol', ['courseid' => $newcourse1->id, 'enrol' => 'muprog', 'customint1' => $newprogram->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $newprogram->id, 'userid' => $student->id]));
        $this->assertTrue($DB->record_exists('tool_muprog_group', ['programid' => $newprogram->id, 'courseid' => $newcourse1->id]));

        program::archive($newprogram->id);
        program::delete($newprogram->id);
    }

    public function test_execute_shared_questions(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \core_question_generator $questiongenerator */
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        // Shared question bank in another course.
        $bankcourse = $this->getDataGenerator()->create_course(['fullname' => 'Bank course', 'shortname' => 'BANK']);
        $qbank = $this->getDataGenerator()->create_module('qbank', ['course' => $bankcourse->id]);
        $bankcontext = \context_module::instance($qbank->cmid);
        $sharedcategory = $questiongenerator->create_question_category(['contextid' => $bankcontext->id, 'name' => 'Shared category']);
        $shared1 = $questiongenerator->create_question('shortanswer', null, ['category' => $sharedcategory->id, 'name' => 'Shared 1']);
        $shared2 = $questiongenerator->create_question('truefalse', null, ['category' => $sharedcategory->id, 'name' => 'Shared 2']);

        // Program course with a quiz using shared questions and one question of its own.
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Quiz course', 'shortname' => 'QC']);
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'name' => 'Some quiz']);
        $quizcontext = \context_module::instance($quiz->cmid);
        $owncategory = $questiongenerator->create_question_category(['contextid' => $quizcontext->id, 'name' => 'Own category']);
        $own1 = $questiongenerator->create_question('shortanswer', null, ['category' => $owncategory->id, 'name' => 'Own 1']);
        quiz_add_quiz_question($shared1->id, $quiz);
        quiz_add_quiz_question($own1->id, $quiz);
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id);
        $quizobj->get_structure()->add_random_questions(1, 1, [
            'filter' => [
                'category' => [
                    'jointype' => \core_question\local\bank\condition::JOINTYPE_DEFAULT,
                    'values' => [$sharedcategory->id],
                    'filteroptions' => ['includesubcategories' => false],
                ],
            ],
        ]);
        $quizobj->get_grade_calculator()->recompute_quiz_sumgrades();

        $sharedentry1 = $DB->get_field('question_versions', 'questionbankentryid', ['questionid' => $shared1->id], MUST_EXIST);
        $ownentry1 = $DB->get_field('question_versions', 'questionbankentryid', ['questionid' => $own1->id], MUST_EXIST);
        $questioncount = $DB->count_records('question');
        $categorycount = $DB->count_records('question_categories');
        $sharedentries = $DB->count_records('question_bank_entries', ['questioncategoryid' => $sharedcategory->id]);

        $program = $generator->create_program(['fullname' => 'Program', 'idnumber' => 'PRG']);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course->id]);

        $operation = new programduplicate($program, '', '2027');
        $operation->execute();

        $newcourse = $DB->get_record('course', ['shortname' => 'QC-2027'], '*', MUST_EXIST);
        $newquiz = $DB->get_record('quiz', ['course' => $newcourse->id], '*', MUST_EXIST);
        $newcm = get_coursemodule_from_instance('quiz', $newquiz->id, $newcourse->id, false, MUST_EXIST);
        $newquizcontext = \context_module::instance($newcm->id);

        // Shared questions are not copied, the new quiz uses the originals.
        $this->assertSame($sharedentries, $DB->count_records('question_bank_entries', ['questioncategoryid' => $sharedcategory->id]));
        $this->assertSame(1, $DB->count_records('question_categories', ['name' => 'Shared category']));
        $this->assertSame(1, $DB->count_records('question', ['name' => 'Shared 1']));
        $this->assertSame(1, $DB->count_records('question', ['name' => 'Shared 2']));
        $references = $DB->get_fieldset_select(
            'question_references',
            'questionbankentryid',
            "usingcontextid = ? AND component = 'mod_quiz' AND questionarea = 'slot'",
            [$newquizcontext->id]
        );
        $this->assertCount(2, $references);
        $this->assertContains($sharedentry1, $references);

        // Own question is copied together with the quiz.
        $this->assertNotContains($ownentry1, $references);
        $this->assertSame(2, $DB->count_records('question', ['name' => 'Own 1']));
        $newowncategory = $DB->get_record('question_categories', ['name' => 'Own category', 'contextid' => $newquizcontext->id], '*', MUST_EXIST);
        $newownentry = $DB->get_field('question_bank_entries', 'id', ['questioncategoryid' => $newowncategory->id], MUST_EXIST);
        $this->assertContains($newownentry, $references);

        // Random question still picks from the shared category.
        $setreferences = $DB->get_records('question_set_references', ['usingcontextid' => $newquizcontext->id]);
        $this->assertCount(1, $setreferences);
        $setreference = reset($setreferences);
        $this->assertSame((string)$bankcontext->id, $setreference->questionscontextid);
        $filter = json_decode($setreference->filtercondition, true);
        $this->assertEquals([$sharedcategory->id], $filter['filter']['category']['values']);

        // Only the own question and its categories were created, nothing is left in course context.
        $this->assertSame($questioncount + 1, $DB->count_records('question'));
        $this->assertSame(0, $DB->count_records('question_categories', ['contextid' => \context_course::instance($newcourse->id)->id]));
        $this->assertSame(
            $DB->count_records('question_categories', ['contextid' => $quizcontext->id]),
            $DB->count_records('question_categories', ['contextid' => $newquizcontext->id])
        );
        $this->assertSame(
            $categorycount + $DB->count_records('question_categories', ['contextid' => $quizcontext->id]),
            $DB->count_records('question_categories')
        );

        // The new quiz can be attempted.
        $newquizobj = \mod_quiz\quiz_settings::create($newquiz->id);
        $attempt = quiz_prepare_and_start_new_attempt($newquizobj, 1, null);
        $this->assertNotEmpty($attempt->id);
    }

    public function test_execute_failure(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Course A', 'shortname' => 'CA']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Course B', 'shortname' => 'CB']);
        $course3 = $this->getDataGenerator()->create_course(['fullname' => 'Course C', 'shortname' => 'CC']);
        $program = $generator->create_program(['fullname' => 'Program', 'idnumber' => 'PRG', 'sources' => ['manual' => []]]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course1->id]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course2->id]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course3->id]);

        // Release is requested, it must not happen if anything fails.
        $operation = new programduplicate($program, '', '2027', null, programduplicate::COURSECATEGORY_SAME, [], null, true);
        $this->assertNull($operation->get_operation_id());
        $this->assertSame([], $operation->get_problems());
        $operation->get_plan();
        // The second course disappears after planning.
        delete_course($course2->id, false);

        // Course that cannot be copied does not stop the duplication.
        $messages = [];
        $newprogram = $operation->execute(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
        $this->assertSame('PRG-2027', $newprogram->idnumber);
        $this->assertSame('1', $newprogram->draft);
        $problems = $operation->get_problems();
        $this->assertCount(1, $problems);
        $this->assertStringStartsWith("Course 'Course B' was not copied: ", $problems[0]);
        $this->assertContains('Program is NOT released because there were problems.', $messages);
        $this->assertNotContains('Program released.', $messages);

        $record = $DB->get_record('tool_muprog_operation', ['programid' => $newprogram->id], '*', MUST_EXIST);
        $this->assertSame((int)$record->id, $operation->get_operation_id());
        $this->assertNotNull($record->timefailed);
        $state = base::decode_state($record);
        $this->assertSame($problems[0], $state['error']);
        $this->assertSame('done', $state['courses'][0]['status']);
        $this->assertNotEmpty($state['courses'][0]['newid']);
        $this->assertSame('failed', $state['courses'][1]['status']);
        $this->assertNotEmpty($state['courses'][1]['error']);
        $this->assertSame('done', $state['courses'][2]['status']);
        $this->assertNotEmpty($state['courses'][2]['newid']);
        $this->assertTrue($DB->record_exists('course', ['id' => $state['courses'][0]['newid'], 'shortname' => 'CA-2027']));
        $this->assertTrue($DB->record_exists('course', ['id' => $state['courses'][2]['newid'], 'shortname' => 'CC-2027']));

        // Program content is created from the courses that were copied.
        $children = program::load_content($newprogram->id)->get_children();
        $this->assertCount(2, $children);
        $this->assertSame($state['courses'][0]['newid'], (int)$children[0]->get_courseid());
        $this->assertSame($state['courses'][2]['newid'], (int)$children[1]->get_courseid());
        $this->assertTrue($DB->record_exists('tool_muprog_source', ['programid' => $newprogram->id, 'type' => 'manual']));

        // The program is frozen, it can be only deleted.
        $this->assertTrue(base::did_operation_fail($newprogram->id));
        $this->assertEquals($record, base::get_pending_operation($newprogram->id));
        $this->assertTrue(\tool_muprog\local\management::is_program_frozen($newprogram));
        $this->assertFalse(\tool_muprog\local\management::is_program_frozen($newprogram, true));
        try {
            \tool_muprog\local\management::require_program_not_frozen($newprogram);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('errorprogramfrozen', $ex->errorcode);
        }
        \tool_muprog\local\management::require_program_not_frozen($newprogram, true);
        $this->assertFalse(\tool_muprog\local\notification_manager::can_manage($newprogram->id));

        program::delete($newprogram->id);
        $this->assertSame(0, $DB->count_records('tool_muprog_operation'));
        $this->assertTrue($DB->record_exists('course', ['shortname' => 'CA-2027']));

        // Failure before the courses are copied stops everything.
        $operation = new programduplicate($program, '', '2028', null, programduplicate::COURSECATEGORY_SAME, [], null, true);
        $this->assertSame(['manual'], $operation->get_plan()->sources);
        $DB->delete_records('tool_muprog_source', ['programid' => $program->id, 'type' => 'manual']);
        try {
            $operation->execute();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
        }
        $this->assertSame([], $operation->get_problems());
        $newprogram = $DB->get_record('tool_muprog_program', ['idnumber' => 'PRG-2028'], '*', MUST_EXIST);
        $this->assertSame('1', $newprogram->draft);
        $record = $DB->get_record('tool_muprog_operation', ['programid' => $newprogram->id], '*', MUST_EXIST);
        $this->assertNotNull($record->timefailed);
        $state = base::decode_state($record);
        $this->assertNotEmpty($state['error']);
        $this->assertSame('todo', $state['courses'][0]['status']);
        $this->assertFalse($DB->record_exists('course', ['shortname' => 'CA-2028']));
        $this->assertSame([], program::load_content($newprogram->id)->get_children());
    }

    public function test_get_pending_operation(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program = $generator->create_program(['draft' => 1]);
        $this->assertFalse(base::did_operation_fail($program->id));
        $this->assertNull(base::get_pending_operation($program->id));
        $this->assertFalse(\tool_muprog\local\management::is_program_frozen($program));

        // Operation that is not locked by any process was aborted, it is marked as failed.
        $record = $generator->create_program_operation(['programid' => $program->id]);
        $this->assertNull($record->timefailed);
        $this->assertEquals($record, base::get_pending_operation($program->id));
        $this->assertNull($DB->get_field('tool_muprog_operation', 'timefailed', ['id' => $record->id]));
        $this->assertTrue(\tool_muprog\local\management::is_program_frozen($program));
        $this->assertNull($DB->get_field('tool_muprog_operation', 'timefailed', ['id' => $record->id]));
        $this->setCurrentTimeStart();
        $pending = base::get_pending_operation($program->id, true);
        $this->assertSame($record->id, $pending->id);
        $this->assertTimeCurrent($pending->timefailed);
        $this->assertSame('Operation was aborted before it finished.', base::decode_state($pending)['error']);
        $this->assertEquals($pending, $DB->get_record('tool_muprog_operation', ['id' => $record->id], '*', MUST_EXIST));
        $this->assertTrue(base::did_operation_fail($program->id));

        // The same when asking about the failure directly.
        $program3 = $generator->create_program(['draft' => 1]);
        $record3 = $generator->create_program_operation(['programid' => $program3->id]);
        $this->assertNull($record3->timefailed);
        $this->assertTrue(base::did_operation_fail($program3->id));
        $this->assertNotNull($DB->get_field('tool_muprog_operation', 'timefailed', ['id' => $record3->id]));

        // Only one operation per program is allowed.
        try {
            $generator->create_program_operation(['programid' => $program->id]);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\dml_write_exception::class, $ex);
        }
        $this->assertSame(1, $DB->count_records('tool_muprog_operation', ['programid' => $program->id]));
        $operation = new programduplicate($program, '', 'x');
        $start = new \ReflectionMethod($operation, 'start');
        try {
            $start->invoke($operation, (int)$program->id, []);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
        }

        // Operation locked by this process is running.
        $program2 = $generator->create_program(['draft' => 1]);
        $record2 = $generator->create_program_operation(['programid' => $program2->id]);
        $factory = \core\lock\lock_config::get_lock_factory('tool_muprog_operation');
        $lock = $factory->get_lock('operation_' . $record2->id, 0);
        $property = new \ReflectionProperty(base::class, 'locks');
        $property->setValue(null, [$record2->id => $lock]);
        $this->assertFalse(base::did_operation_fail($program2->id));
        $pending = base::get_pending_operation($program2->id);
        $this->assertSame($record2->id, $pending->id);
        $this->assertNull($pending->timefailed);
        $this->assertTrue(\tool_muprog\local\management::is_program_frozen($program2));
        $this->assertTrue(\tool_muprog\local\management::is_program_frozen($program2, true));
        // Running operation cannot be dismissed.
        try {
            base::dismiss_failed($program2->id);
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertInstanceOf(\core\exception\coding_exception::class, $ex);
        }
        $this->assertTrue($DB->record_exists('tool_muprog_operation', ['programid' => $program2->id]));
        $property->setValue(null, []);
        $lock->release();

        $this->assertTrue(base::did_operation_fail($program2->id));

        // Failed operation can be dismissed, the program can be used again.
        base::dismiss_failed($program2->id);
        $this->assertFalse($DB->record_exists('tool_muprog_operation', ['programid' => $program2->id]));
        $this->assertFalse(base::did_operation_fail($program2->id));
        $this->assertNull(base::get_pending_operation($program2->id));
        $this->assertFalse(\tool_muprog\local\management::is_program_frozen($program2));
        \tool_muprog\local\management::require_program_not_frozen($program2);
        $program2 = program::release($program2->id);
        $this->assertSame('0', $program2->draft);
        base::dismiss_failed($program2->id);
    }

    public function test_execute_sources(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Newcomers']);
        $other = $generator->create_program();
        $program = $generator->create_program(['fullname' => 'Program', 'idnumber' => 'PRG', 'sources' => [
            'manual' => [],
            'approval' => ['approval_allowrequest' => 0],
            'cohort' => ['cohortids' => [$cohort->id]],
            'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10],
        ]]);
        // Sources that do not support importing are not copied.
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program->id, 'type' => 'program', 'datajson' => '[]', 'auxint1' => $other->id,
        ]);
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program->id, 'type' => 'extdb', 'datajson' => '[]',
            'auxint1' => 77, 'auxint2' => 1, 'auxint3' => 1000, 'auxint4' => 2000, 'auxint5' => 3000,
        ]);
        $DB->insert_record('tool_muprog_source', (object)['programid' => $program->id, 'type' => 'mucertify', 'datajson' => '[]']);
        // Nothing but the settings of each source is copied.
        $DB->set_field('tool_muprog_source', 'auxint5', 55, ['programid' => $program->id, 'type' => 'selfallocation']);
        $originals = $DB->get_records('tool_muprog_source', ['programid' => $program->id], '', 'type, datajson, auxint1, auxint5');

        $operation = new programduplicate($program, '', '2027');
        $newprogram = $operation->execute();

        $sources = $DB->get_records('tool_muprog_source', ['programid' => $newprogram->id], 'type ASC', 'type, id, datajson, auxint1, auxint2, auxint3, auxint4, auxint5');
        $this->assertSame(['approval', 'cohort', 'manual', 'selfallocation'], array_keys($sources));
        $this->assertSame($originals['approval']->datajson, $sources['approval']->datajson);
        $this->assertSame($originals['selfallocation']->datajson, $sources['selfallocation']->datajson);
        $this->assertSame('abc', json_decode($sources['selfallocation']->datajson)->key);
        $this->assertTrue($DB->record_exists('tool_muprog_src_cohort', ['sourceid' => $sources['cohort']->id, 'cohortid' => $cohort->id]));
        foreach ($sources as $source) {
            $this->assertNull($source->auxint1);
            $this->assertNull($source->auxint2);
            $this->assertNull($source->auxint3);
            $this->assertNull($source->auxint4);
            $this->assertNull($source->auxint5);
        }

        // Nobody is allocated to draft.
        $this->assertFalse($DB->record_exists('tool_muprog_allocation', ['programid' => $newprogram->id]));

        // Original is not changed.
        $this->assertEquals($originals, $DB->get_records('tool_muprog_source', ['programid' => $program->id], '', 'type, datajson, auxint1, auxint5'));

        // Sources that were not copied are reported.
        $notes = implode("\n", programduplicate::get_review_notes($newprogram, $program));
        $this->assertSame(3, substr_count($notes, 'NOT COPIED'));
        $this->assertStringContainsString('Allocation, cohorts: Newcomers - THE SAME cohort members', $notes);
        $this->assertStringContainsString('the same sign up key is used, limit 10 users', $notes);

        // Only selected sources are copied.
        $operation = new programduplicate($program, '', 'S1', null, programduplicate::COURSECATEGORY_SAME, [], ['cohort', 'manual', 'cohort']);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertSame(['cohort', 'manual'], $plan->sources);
        $this->assertFalse($plan->release);
        $selected = $operation->execute();
        $types = $DB->get_fieldset_select('tool_muprog_source', 'type', "programid = ?", [$selected->id]);
        sort($types);
        $this->assertSame(['cohort', 'manual'], $types);
        $this->assertSame('1', $selected->draft);
        $notes = implode("\n", programduplicate::get_review_notes($selected, $program));
        $this->assertSame(5, substr_count($notes, 'NOT COPIED'));

        // No sources, the program may be released right away.
        $operation = new programduplicate($program, '', 'S2', null, programduplicate::COURSECATEGORY_SAME, [], [], true);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertSame([], $plan->sources);
        $this->assertTrue($plan->release);
        $messages = [];
        $released = $operation->execute(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
        $this->assertSame('0', $released->draft);
        $this->assertSame('0', $released->archived);
        $this->assertSame(0, $DB->count_records('tool_muprog_source', ['programid' => $released->id]));
        $this->assertSame(0, $DB->count_records('tool_muprog_allocation', ['programid' => $released->id]));
        $this->assertContains('Program released.', $messages);
        $notes = implode("\n", programduplicate::get_review_notes($released, $program));
        $this->assertStringContainsString('no allocation source is enabled in the new program', $notes);

        // Without sources the program stays draft unless release is requested.
        $operation = new programduplicate($program, '', 'S3', null, programduplicate::COURSECATEGORY_SAME, [], []);
        $this->assertSame('1', $operation->execute()->draft);

        // Release is possible with any sources, there is only a warning that nothing is reviewed.
        $member = $this->getDataGenerator()->create_user();
        \cohort_add_member($cohort->id, $member->id);
        $operation = new programduplicate($program, '', 'S4', null, programduplicate::COURSECATEGORY_SAME, [], null, true);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertTrue($plan->release);
        $this->assertCount(1, $plan->warnings);
        $this->assertStringContainsString('released without any review', $plan->warnings[0]);
        $released = $operation->execute();
        $this->assertSame([], $operation->get_problems());
        $this->assertSame('0', $released->draft);
        $this->assertSame(4, $DB->count_records('tool_muprog_source', ['programid' => $released->id]));
        // Cohort members are allocated right away.
        $this->assertTrue($DB->record_exists('tool_muprog_allocation', ['programid' => $released->id, 'userid' => $member->id]));
        // There is no warning without release.
        $operation = new programduplicate($program, '', 'S4b');
        $this->assertSame([], $operation->get_plan()->warnings);

        // Sources that cannot be copied must not be requested.
        $operation = new programduplicate($program, '', 'S5', null, programduplicate::COURSECATEGORY_SAME, [], ['manual', 'extdb', 'nonsense']);
        $errors = $operation->get_plan()->errors;
        $this->assertCount(2, $errors);
        $this->assertSame('Allocation source cannot be imported: extdb', $errors[0]);
        $this->assertSame('Allocation source does not exist: nonsense', $errors[1]);
        $other2 = $generator->create_program(['idnumber' => 'OTHER2', 'sources' => ['manual' => []]]);
        $operation = new programduplicate($other2, '', 'S6', null, programduplicate::COURSECATEGORY_SAME, [], ['approval']);
        $this->assertSame(['Allocation source is not enabled in the original program: approval'], $operation->get_plan()->errors);

        // Sources disabled for new programs are not copied either.
        set_config('source_approval_allownew', 0, 'tool_muprog');
        $operation = new programduplicate($program, '', '2028');
        $newprogram2 = $operation->execute();
        $types = $DB->get_fieldset_select('tool_muprog_source', 'type', "programid = ?", [$newprogram2->id]);
        sort($types);
        $this->assertSame(['cohort', 'manual', 'selfallocation'], $types);
        $notes = implode("\n", programduplicate::get_review_notes($newprogram2, $program));
        $this->assertSame(4, substr_count($notes, 'NOT COPIED'));
    }

    public function test_get_review_notes(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $program0 = $generator->create_program(['draft' => 1]);
        $notes = implode("\n", programduplicate::get_review_notes($program0));
        $this->assertStringContainsString('no allocation source is enabled', $notes);
        $this->assertStringContainsString('Catalogue: the new program is not in any catalogue section', $notes);
        $this->assertStringNotContainsString('Courses:', $notes);
        $this->assertStringNotContainsString('IN THE PAST', $notes);

        $cohort = $this->getDataGenerator()->create_cohort(['name' => 'Newcomers']);
        $course = $this->getDataGenerator()->create_course();
        $other = $generator->create_program(['fullname' => 'Other program', 'idnumber' => 'OTHER']);
        $program = $generator->create_program([
            'timeallocationend' => time() - DAYSECS,
            'startdate' => ['type' => 'date', 'date' => time() - WEEKSECS],
            'duedate' => ['type' => 'date', 'date' => time() + WEEKSECS],
            'sources' => [
                'manual' => [],
                'cohort' => ['cohortids' => [$cohort->id]],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10],
            ],
        ]);
        $generator->create_program_item(['programid' => $program->id, 'courseid' => $course->id]);
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program->id, 'type' => 'program', 'datajson' => '[]', 'auxint1' => $other->id,
        ]);
        $DB->insert_record('tool_muprog_source', (object)[
            'programid' => $program->id, 'type' => 'extdb', 'datajson' => '[]', 'auxint1' => null,
        ]);

        // Notes for the original say what is not going to be copied.
        $plan = (new programduplicate($program, '', 'x'))->get_plan();
        $this->assertSame(['cohort', 'manual', 'selfallocation'], $plan->sources);
        $notes = programduplicate::get_review_notes($program, null, $plan->sources);
        $text = implode("\n", $notes);
        $this->assertStringContainsString('Allocation, manual: enabled.', $text);
        $this->assertStringContainsString('Allocation, cohorts: Newcomers - THE SAME cohort members', $text);
        $this->assertSame(2, substr_count($text, 'NOT COPIED.'));
        $this->assertStringNotContainsString('no allocation source is enabled', $text);

        // Selected sources only.
        $plan = (new programduplicate($program, '', 'x', null, programduplicate::COURSECATEGORY_SAME, [], ['manual']))->get_plan();
        $this->assertSame(['manual'], $plan->sources);
        $selected = implode("\n", programduplicate::get_review_notes($program, null, $plan->sources));
        $this->assertStringContainsString('Allocation, manual: enabled.', $selected);
        $this->assertSame(4, substr_count($selected, 'NOT COPIED.'));

        // No sources at all.
        $none = implode("\n", programduplicate::get_review_notes($program, null, []));
        $this->assertStringContainsString('no allocation source is enabled in the new program', $none);
        $this->assertSame(5, substr_count($none, 'NOT COPIED.'));
        $this->assertStringContainsString('the same sign up key is used, limit 10 users', $text);
        $this->assertStringContainsString('Courses: copied without any user data.', $text);
        $this->assertStringNotContainsString('no allocation source is enabled', $text);
        $past = array_values(array_filter($notes, fn($note) => str_contains($note, 'IN THE PAST')));
        $this->assertCount(2, $past);
        $this->assertStringStartsWith('Allocation end: ', $past[0]);
        $this->assertStringStartsWith('Program start date is fixed: ', $past[1]);
        $due = array_values(array_filter($notes, fn($note) => str_starts_with($note, 'Program due date is fixed: ')));
        $this->assertCount(1, $due);
        $this->assertStringNotContainsString('IN THE PAST', $due[0]);
    }

    public function test_execute_customfields(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        /** @var \core_customfield_generator $cfgenerator */
        $cfgenerator = $this->getDataGenerator()->get_plugin_generator('core_customfield');

        $category = $cfgenerator->create_category(['component' => 'tool_muprog', 'area' => 'program']);
        $normal = $cfgenerator->create_field(['categoryid' => $category->get('id'), 'shortname' => 'normal', 'name' => 'Normal field', 'type' => 'text']);
        $unique = $cfgenerator->create_field([
            'categoryid' => $category->get('id'), 'shortname' => 'ref', 'name' => 'Reference', 'type' => 'text',
            'configdata' => ['uniquevalues' => 1],
        ]);
        $unused = $cfgenerator->create_field([
            'categoryid' => $category->get('id'), 'shortname' => 'ref2', 'name' => 'Other reference', 'type' => 'text',
            'configdata' => ['uniquevalues' => 1],
        ]);

        $program = $generator->create_program(['fullname' => 'Program', 'idnumber' => 'PRG']);
        $cfgenerator->add_instance_data($normal, $program->id, 'Some value');
        $cfgenerator->add_instance_data($unique, $program->id, 'REF-1');

        $value = function (\core_customfield\field_controller $field, int $programid) use ($DB): string|false {
            return $DB->get_field('customfield_data', 'value', ['fieldid' => $field->get('id'), 'instanceid' => $programid]);
        };

        // Unique field with value is reported, release is not possible.
        $operation = new programduplicate($program, '', '2027', null, programduplicate::COURSECATEGORY_SAME, [], null, true);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->errors);
        $this->assertSame(['ref' => 'Reference'], $plan->uniquefields);
        $this->assertFalse($plan->release);
        $this->assertCount(2, $plan->warnings);
        $this->assertSame("Custom field 'Reference' must be unique, its value is not copied.", $plan->warnings[0]);
        $this->assertStringContainsString('NOT going to be released', $plan->warnings[1]);
        $notes = implode("\n", programduplicate::get_review_notes($program, null, $plan->sources));
        $this->assertStringContainsString("Custom field 'Reference': NOT COPIED, the value must be unique.", $notes);
        $this->assertStringNotContainsString('Other reference', $notes);

        $newprogram = $operation->execute();
        $this->assertSame([], $operation->get_problems());
        $this->assertSame('1', $newprogram->draft);
        $this->assertSame('Some value', $value($normal, $newprogram->id));
        $this->assertContains($value($unique, $newprogram->id), [false, '']);
        $this->assertSame('REF-1', $value($unique, $program->id));
        $select = "fieldid = ? AND " . $DB->sql_compare_text('value') . " = ?";
        $this->assertSame(1, $DB->count_records_select('customfield_data', $select, [$unique->get('id'), 'REF-1']));
        $notes = implode("\n", programduplicate::get_review_notes($newprogram, $program));
        $this->assertStringContainsString("Custom field 'Reference': NOT COPIED, the value must be unique.", $notes);

        // Program without value in unique field can be released.
        $program2 = $generator->create_program(['fullname' => 'Other', 'idnumber' => 'OTH']);
        $cfgenerator->add_instance_data($normal, $program2->id, 'Other value');
        $operation = new programduplicate($program2, '', '2027', null, programduplicate::COURSECATEGORY_SAME, [], null, true);
        $plan = $operation->get_plan();
        $this->assertSame([], $plan->uniquefields);
        $this->assertTrue($plan->release);
        $this->assertCount(1, $plan->warnings);
        $newprogram2 = $operation->execute();
        $this->assertSame('0', $newprogram2->draft);
        $this->assertSame('Other value', $value($normal, $newprogram2->id));
    }
}
