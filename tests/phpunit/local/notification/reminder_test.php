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

namespace tool_muprog\phpunit\local\notification;

use tool_muprog\local\notification_manager;
use tool_muprog\local\notification\reminder;
use tool_muprog\local\source\manual;

/**
 * Weekly reminder notification test.
 *
 * @group      MuTMS
 * @package    tool_muprog
 * @copyright  2026 CNW Co.
 * @author     Szabolcs Varhegyi
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\reminder
 */
final class reminder_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notify_users(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id, $user3->id, $user4->id, $user5->id]);

        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $allocation3 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user3->id], '*', MUST_EXIST);
        $allocation4 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id], '*', MUST_EXIST);
        $allocation5 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user5->id], '*', MUST_EXIST);

        $now = time();

        // User 1: timestart was 8 days ago (eligible for first reminder).
        $allocation1->timestart = $now - reminder::TIME_INTERVAL - 100;
        $DB->update_record('tool_muprog_allocation', $allocation1);

        // User 2: timestart was only 2 days ago (not yet eligible for first reminder).
        $allocation2->timestart = $now - (2 * 86400);
        $DB->update_record('tool_muprog_allocation', $allocation2);

        // User 3: timestart was 8 days ago, but program already completed.
        $allocation3->timestart = $now - reminder::TIME_INTERVAL - 100;
        $allocation3->timecompleted = $now - 100;
        $DB->update_record('tool_muprog_allocation', $allocation3);

        // User 4: timestart was 8 days ago, but program already ended in the past.
        $allocation4->timestart = $now - reminder::TIME_INTERVAL - 200;
        $allocation4->timeend = $now - 100;
        $DB->update_record('tool_muprog_allocation', $allocation4);

        // User 5: timestart was 95 days ago (older than TIME_MAX of 90 days, no reminder).
        $allocation5->timestart = $now - reminder::TIME_MAX - (5 * 86400);
        $DB->update_record('tool_muprog_allocation', $allocation5);

        $generator->create_program_notification(['notificationtype' => 'reminder', 'programid' => $program1->id]);

        // First run: only User 1 should receive reminder.
        $this->setCurrentTimestart();
        $sink = $this->redirectMessages();
        reminder::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();

        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('Reminder: program completion pending', $message->subject);
        $this->assertSame('-10', $message->useridfrom);
        $this->assertSame($user1->id, $message->useridto);
        $this->assertSame('tool_muprog', $message->component);
        $this->assertSame('reminder_notification', $message->eventtype);
        $this->assertSame($program1->fullname, $message->contexturlname);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user1->id, $program1->id, 'reminder'));
        $this->assertNull(notification_manager::get_timenotified($user2->id, $program1->id, 'reminder'));
        $this->assertNull(notification_manager::get_timenotified($user5->id, $program1->id, 'reminder'));

        // Immediate rerun: User 1 should NOT receive another reminder yet (less than 7 days since last).
        $sink = $this->redirectMessages();
        reminder::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);

        // Simulate 8 days passing for User 1's previous notification.
        $notificationuser = $DB->get_record(
            'tool_mulib_notification_user',
            ['userid' => $user1->id, 'otherid1' => $allocation1->id],
            '*',
            MUST_EXIST
        );
        $notificationuser->timenotified = $now - reminder::TIME_INTERVAL - 100;
        $DB->update_record('tool_mulib_notification_user', $notificationuser);

        // Next run: User 1 should receive the second reminder.
        $sink = $this->redirectMessages();
        reminder::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertSame($user1->id, $messages[0]->useridto);

        // Verify there are now 2 sent notification records for User 1.
        $count = $DB->count_records(
            'tool_mulib_notification_user',
            ['userid' => $user1->id, 'otherid1' => $allocation1->id]
        );
        $this->assertSame(2, $count);

        // Complete program for User 1: should not receive any more reminders.
        $allocation1->timecompleted = time();
        $DB->update_record('tool_muprog_allocation', $allocation1);

        $sink = $this->redirectMessages();
        reminder::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
    }

    public function test_notify_users_filtering(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program3 = $generator->create_program(['sources' => ['manual' => []]]);
        $source3 = $DB->get_record('tool_muprog_source', ['programid' => $program3->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id, $user3->id, $user4->id, $user5->id, $user6->id]);
        manual::allocate_users($program2->id, $source2->id, [$user1->id]);
        manual::allocate_users($program3->id, $source3->id, [$user1->id]);

        $now = time();
        $DB->set_field('tool_muprog_allocation', 'timestart', $now - reminder::TIME_INTERVAL - 100, []);

        // User 2: suspended user.
        $DB->set_field('user', 'suspended', 1, ['id' => $user2->id]);
        // User 3: archived allocation.
        $DB->set_field('tool_muprog_allocation', 'archived', 1, ['programid' => $program1->id, 'userid' => $user3->id]);
        // User 4: program ends in the future, start just inside TIME_MAX.
        $allocation4 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user4->id], '*', MUST_EXIST);
        $allocation4->timestart = $now - reminder::TIME_MAX + 100;
        $allocation4->timeend = $now + 1000;
        $DB->update_record('tool_muprog_allocation', $allocation4);
        // User 6: program not started yet.
        $DB->set_field('tool_muprog_allocation', 'timestart', $now + 1000, ['programid' => $program1->id, 'userid' => $user6->id]);
        // Program 3 is archived.
        $DB->set_field('tool_muprog_program', 'archived', 1, ['id' => $program3->id]);

        $notification1 = $generator->create_program_notification(['notificationtype' => 'reminder', 'programid' => $program1->id]);
        $notification2 = $generator->create_program_notification(['notificationtype' => 'reminder', 'programid' => $program2->id]);
        $generator->create_program_notification(['notificationtype' => 'reminder', 'programid' => $program3->id]);
        $DB->set_field('tool_mulib_notification', 'enabled', 0, ['id' => $notification2->id]);

        // User 5: multiple old reminders, must be notified only once.
        $allocation5 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user5->id], '*', MUST_EXIST);
        foreach ([3, 2] as $weeks) {
            $DB->insert_record('tool_mulib_notification_user', [
                'notificationid' => $notification1->id,
                'userid' => $user5->id,
                'otherid1' => $allocation5->id,
                'timenotified' => $now - ($weeks * reminder::TIME_INTERVAL),
            ]);
        }

        // User filter: only program 1 for user 1, program 2 is disabled and program 3 archived.
        $sink = $this->redirectMessages();
        reminder::notify_users(null, $user1);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertSame($user1->id, $messages[0]->useridto);
        $this->assertSame($program1->fullname, $messages[0]->contexturlname);

        // Program filter: remaining eligible users 4 and 5.
        $sink = $this->redirectMessages();
        reminder::notify_users($program1, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);
        $this->assertSame($user4->id, $messages[0]->useridto);
        $this->assertSame($user5->id, $messages[1]->useridto);
        $count = $DB->count_records(
            'tool_mulib_notification_user',
            ['notificationid' => $notification1->id, 'userid' => $user5->id]
        );
        $this->assertSame(3, $count);

        // Enabled notification is processed.
        $DB->set_field('tool_mulib_notification', 'enabled', 1, ['id' => $notification2->id]);
        $sink = $this->redirectMessages();
        reminder::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertSame($user1->id, $messages[0]->useridto);
        $this->assertSame($program2->fullname, $messages[0]->contexturlname);

        // Nothing more to send.
        $sink = $this->redirectMessages();
        reminder::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
    }
}
