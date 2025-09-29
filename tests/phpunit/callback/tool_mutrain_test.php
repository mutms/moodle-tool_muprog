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

namespace tool_muprog\phpunit\callback;

use tool_muprog\callback\tool_mutrain;
use tool_muprog\local\allocation;

require_once(__DIR__ . '/../../fixtures/mock_optional_plugins.php');

/**
 * tool_mutrain callback coverage.
 *
 * @package    tool_muprog
 * @copyright  2025 e-Learning Team, Universiti Malaysia Terengganu
 * @covers     \tool_muprog\callback\tool_mutrain
 */
final class tool_mutrain_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_framework_usage_counts_training_items(): void {
        global $DB;

        $this->setAdminUser();

        $frameworkid = $this->create_framework('Framework A');

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program();

        $this->insert_training_item($program->id, $frameworkid);
        $this->insert_training_item($program->id, $frameworkid);

        $hook = new \tool_mutrain\hook\framework_usage($frameworkid);
        tool_mutrain::framework_usage($hook);

        $this->assertSame(2, $hook->get_usage());

        $hooknone = new \tool_mutrain\hook\framework_usage($frameworkid + 1);
        tool_mutrain::framework_usage($hooknone);
        $this->assertSame(0, $hooknone->get_usage());

        // Clean-up verification.
        $count = $DB->count_records('tool_muprog_item', ['trainingid' => $frameworkid]);
        $this->assertSame(2, $count);
    }

    public function test_completion_updated_reenrols_user_single_program(): void {
        global $DB;

        $this->setAdminUser();
        $this->ensure_role_config();

        $frameworkid = $this->create_framework('Framework B');

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $program = $generator->create_program(['fullname' => 'Single Program', 'idnumber' => 'SP']);
        $generator->create_program_item([
            'programid' => $program->id,
            'courseid' => $course->id,
        ]);
        $this->insert_training_item($program->id, $frameworkid);

        $generator->create_program_allocation([
            'programid' => $program->id,
            'userid' => $user->id,
        ]);

        allocation::fix_enrol_instances($program->id);

        $enrol = $DB->get_record('enrol', [
            'enrol' => 'muprog',
            'customint1' => $program->id,
            'courseid' => $course->id,
        ], '*', MUST_EXIST);

        $plugin = enrol_get_plugin('muprog');
        $plugin->unenrol_user($enrol, $user->id);
        role_unassign_all([
            'contextid' => \context_course::instance($course->id)->id,
            'component' => 'enrol_muprog',
            'itemid' => $enrol->id,
            'userid' => $user->id,
        ]);

        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user->id]));

        $hook = new \tool_mutrain\hook\completion_updated($user->id, [$frameworkid]);
        tool_mutrain::completion_updated($hook);

        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $user->id]));
    }

    public function test_completion_updated_reenrols_user_multiple_programs(): void {
        global $DB;

        $this->setAdminUser();
        $this->ensure_role_config();

        $frameworkid = $this->create_framework('Framework Shared');

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        [$program1, $enrol1] = $this->create_program_with_course($generator, 'Program One', 'PONE', $course1->id, $frameworkid, $user->id);
        [$program2, $enrol2] = $this->create_program_with_course($generator, 'Program Two', 'PTWO', $course2->id, $frameworkid, $user->id);

        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $enrol1->id, 'userid' => $user->id]));
        $this->assertFalse($DB->record_exists('user_enrolments', ['enrolid' => $enrol2->id, 'userid' => $user->id]));

        $hook = new \tool_mutrain\hook\completion_updated($user->id, [$frameworkid]);
        tool_mutrain::completion_updated($hook);

        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol1->id, 'userid' => $user->id]));
        $this->assertTrue($DB->record_exists('user_enrolments', ['enrolid' => $enrol2->id, 'userid' => $user->id]));

        $this->assertFalse((bool)$program1->archived);
        $this->assertFalse((bool)$program2->archived);
    }

    private function ensure_role_config(): void {
        global $DB;
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        set_config('roleid', $roleid, 'tool_muprog');
    }

    private function create_framework(string $name): int {
        global $DB;

        $this->ensure_mutrain_tables();

        $record = (object) [
            'contextid' => \context_system::instance()->id,
            'name' => $name,
            'restrictedcompletion' => 0,
            'requiredtraining' => 0,
            'archived' => 0,
            'publicaccess' => 0,
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
            'timecreated' => time(),
        ];

        return (int)$DB->insert_record('tool_mutrain_framework', $record, true);
    }

    private function ensure_mutrain_tables(): void {
        global $DB;

        $dbman = $DB->get_manager();

        $framework = new \xmldb_table('tool_mutrain_framework');
        if (!$dbman->table_exists($framework)) {
            $framework->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $framework->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '1');
            $framework->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $framework->add_field('restrictedcompletion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $framework->add_field('requiredtraining', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $framework->add_field('archived', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $framework->add_field('publicaccess', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $framework->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $framework->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, (string)FORMAT_HTML);
            $framework->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $framework->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($framework);
        }

        $field = new \xmldb_table('tool_mutrain_field');
        if (!$dbman->table_exists($field)) {
            $field->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $field->add_field('frameworkid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $field->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $field->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($field);
        }

        $completion = new \xmldb_table('tool_mutrain_completion');
        if (!$dbman->table_exists($completion)) {
            $completion->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $completion->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $completion->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $completion->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $completion->add_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $completion->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($completion);
        }
    }

    private function insert_training_item(int $programid, int $frameworkid): void {
        global $DB;

        $record = (object) [
            'programid' => $programid,
            'topitem' => null,
            'courseid' => null,
            'trainingid' => $frameworkid,
            'previtemid' => null,
            'fullname' => 'Training ' . $frameworkid,
            'sequencejson' => '[]',
            'minprerequisites' => null,
            'points' => 1,
            'minpoints' => null,
            'completiondelay' => 0,
        ];
        $DB->insert_record('tool_muprog_item', $record);
    }

    /**
     * Create a program with a single course item and manual allocation for user, then remove enrolment.
     *
     * @param \tool_muprog_generator $generator
     * @param string $fullname
     * @param string $idnumber
     * @param int $courseid
     * @param int $frameworkid
     * @param int $userid
     * @return array{0: \stdClass, 1: \stdClass}
     */
    private function create_program_with_course(
        \tool_muprog_generator $generator,
        string $fullname,
        string $idnumber,
        int $courseid,
        int $frameworkid,
        int $userid
    ): array {
        global $DB;

        $program = $generator->create_program([
            'fullname' => $fullname,
            'idnumber' => $idnumber,
        ]);
        $generator->create_program_item([
            'programid' => $program->id,
            'courseid' => $courseid,
        ]);
        $this->insert_training_item($program->id, $frameworkid);

        $generator->create_program_allocation([
            'programid' => $program->id,
            'userid' => $userid,
        ]);

        allocation::fix_enrol_instances($program->id);

        $enrol = $DB->get_record('enrol', [
            'enrol' => 'muprog',
            'customint1' => $program->id,
            'courseid' => $courseid,
        ], '*', MUST_EXIST);

        $plugin = enrol_get_plugin('muprog');
        $plugin->unenrol_user($enrol, $userid);
        role_unassign_all([
            'contextid' => \context_course::instance($courseid)->id,
            'component' => 'enrol_muprog',
            'itemid' => $enrol->id,
            'userid' => $userid,
        ]);

        return [$program, $enrol];
    }
}
