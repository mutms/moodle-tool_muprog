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

use tool_muprog\local\notification\endverysoon;
use tool_muprog\local\notification_manager;
use tool_muprog\local\source\manual;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Last warning before program end notification test.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\tool_muprog\local\notification\endverysoon::class)]
#[Group('MuTMS')]
final class endverysoon_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notify_users(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $this->assertSame(DAYSECS, endverysoon::TIME_VERY_SOON);
        $this->assertSame(DAYSECS, endverysoon::get_default_interval());

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id, $user3->id, $user4->id]);

        $now = time();
        $DB->set_field('tool_muprog_allocation', 'timeend', $now - 100, ['userid' => $user1->id]);
        $DB->set_field('tool_muprog_allocation', 'timeend', $now + DAYSECS - 100, ['userid' => $user2->id]);
        $DB->set_field('tool_muprog_allocation', 'timeend', $now + DAYSECS + 100, ['userid' => $user3->id]);
        $DB->set_field('tool_muprog_allocation', 'timeend', $now + DAYSECS - 100, ['userid' => $user4->id]);
        $DB->set_field('tool_muprog_allocation', 'timecompleted', $now - 100, ['userid' => $user4->id]);

        $notification = $generator->create_program_notification(['notificationtype' => 'endverysoon', 'programid' => $program1->id]);
        $this->assertNull($notification->auxint1);
        $this->assertSame(['Time before end date' => '1 day'], endverysoon::get_aux_details($notification));
        $current = endverysoon::prepare_update_form_data((array)$notification);
        $this->assertSame(DAYSECS, $current['auxint1']);

        $this->setCurrentTimestart();
        $sink = $this->redirectMessages();
        endverysoon::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $message = $messages[0];
        $this->assertSame('Urgent: complete your program before it ends', $message->subject);
        $this->assertStringContainsString('this is the last warning', $message->fullmessage);
        $this->assertSame('-10', $message->useridfrom);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('tool_muprog', $message->component);
        $this->assertSame('endverysoon_notification', $message->eventtype);
        $this->assertSame($program1->fullname, $message->contexturlname);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program1->id, 'endverysoon'));

        $sink = $this->redirectMessages();
        endverysoon::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
    }

    public function test_notify_users_custom_interval(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $now = time();
        $DB->set_field('tool_muprog_allocation', 'timeend', $now + DAYSECS * 4, ['userid' => $user1->id]);
        $DB->set_field('tool_muprog_allocation', 'timeend', $now + DAYSECS * 6, ['userid' => $user2->id]);

        $notification = $generator->create_program_notification([
            'notificationtype' => 'endverysoon',
            'programid' => $program1->id,
            'auxint1' => DAYSECS * 5,
        ]);
        $this->assertSame((string)(DAYSECS * 5), $notification->auxint1);
        $this->assertSame(['Time before end date' => '5 days'], endverysoon::get_aux_details($notification));

        $sink = $this->redirectMessages();
        endverysoon::notify_users(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertSame($user1->id, $messages[0]->useridto);

        // End soon and last warning are independent, both may be sent.
        $generator->create_program_notification([
            'notificationtype' => 'endsoon',
            'programid' => $program1->id,
            'auxint1' => DAYSECS * 30,
        ]);
        $sink = $this->redirectMessages();
        notification_manager::trigger_notifications(null, null);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $messages);
        $this->assertSame('endsoon_notification', $messages[0]->eventtype);
        $this->assertSame('endsoon_notification', $messages[1]->eventtype);
    }
}
