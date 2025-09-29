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

namespace tool_muprog\phpunit\db;

require_once(__DIR__ . '/../../../db/upgrade.php');
require_once($GLOBALS['CFG']->dirroot . '/lib/upgradelib.php');

/**
 * Upgrade path coverage.
 *
 * @package    tool_muprog
 * @copyright  2025 e-Learning Team, Universiti Malaysia Terengganu
 * @covers     ::xmldb_tool_muprog_upgrade
 */
final class upgrade_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_upgrade_applies_recent_changes(): void {
        global $DB;

        $dbman = $DB->get_manager();

        $snapshotprogram = new \xmldb_table('tool_muprog_prg_snapshot');
        if ($dbman->table_exists($snapshotprogram)) {
            $dbman->drop_table($snapshotprogram);
        }
        $snapshotprogram->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $snapshotprogram->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($snapshotprogram);

        $snapshotuser = new \xmldb_table('tool_muprog_usr_snapshot');
        if ($dbman->table_exists($snapshotuser)) {
            $dbman->drop_table($snapshotuser);
        }
        $snapshotuser->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $snapshotuser->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($snapshotuser);

        $allocationtable = new \xmldb_table('tool_muprog_allocation');
        $calendarfield = new \xmldb_field('calendarupdated', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($allocationtable, $calendarfield)) {
            $dbman->drop_field($allocationtable, $calendarfield);
        }

        $categoryid = $DB->insert_record('customfield_category', (object) [
            'name' => 'Programs',
            'component' => 'tool_muprog',
            'area' => 'fields',
            'itemid' => 0,
            'contextid' => \context_system::instance()->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ], true);

        $programtable = new \xmldb_table('tool_muprog_program');
        $publicaccess = new \xmldb_field('publicaccess', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null, 'presentationjson');
        if ($dbman->field_exists($programtable, $publicaccess)) {
            $dbman->rename_field($programtable, $publicaccess, 'public');
        }

        set_config('version', 2024010100, 'tool_muprog');

        $result = xmldb_tool_muprog_upgrade(2024010100);
        $this->assertTrue($result);

        $this->assertFalse($dbman->table_exists($snapshotprogram));
        $this->assertFalse($dbman->table_exists($snapshotuser));

        $this->assertTrue($dbman->field_exists($allocationtable, $calendarfield));

        $category = $DB->get_record('customfield_category', ['id' => $categoryid], '*', MUST_EXIST);
        $this->assertSame('program', $category->area);

        $this->assertFalse($dbman->field_exists($programtable, new \xmldb_field('public', XMLDB_TYPE_INTEGER, '1')));
        $this->assertTrue($dbman->field_exists($programtable, $publicaccess));
    }
}
