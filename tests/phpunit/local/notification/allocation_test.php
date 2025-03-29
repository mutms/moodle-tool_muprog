<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_muprog\phpunit\local\notification;

use tool_muprog\local\notification_manager;
use tool_muprog\local\source\manual;

/**
 * Program allocation notifications test.
 *
 * @group      muTMS
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_muprog\local\notification\allocation
 */
final class allocation_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_notification(): void {
        global $DB;

        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');

        $guest = guest_user();
        $admin = get_admin();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $program1 = $generator->create_program(['sources' => ['manual' => []]]);
        $source1 = $DB->get_record('tool_muprog_source', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $program2 = $generator->create_program(['sources' => ['manual' => []]]);
        $source2 = $DB->get_record('tool_muprog_source', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $this->setUser($user3);

        $sink = $this->redirectMessages();
        manual::allocate_users($program1->id, $source1->id, [$user1->id]);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
        $allocation1 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user1->id], '*', MUST_EXIST);
        $this->assertNull(notification_manager::get_timenotified($user1->id, $program1->id, 'allocation'));

        $notification = $generator->create_program_notification(['programid' => $program1->id, 'notificationtype' => 'allocation']);
        $sink = $this->redirectMessages();
        $this->setCurrentTimeStart();
        manual::allocate_users($program1->id, $source1->id, [$user2->id]);
        $allocation2 = $DB->get_record('tool_muprog_allocation', ['programid' => $program1->id, 'userid' => $user2->id], '*', MUST_EXIST);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $messages);
        $this->assertTimeCurrent(notification_manager::get_timenotified($user2->id, $program1->id, 'allocation'));
        $message = $messages[0];
        $this->assertSame('Program allocation notification', $message->subject);
        $this->assertStringContainsString('you have been allocated to program', $message->fullmessage);
        $this->assertSame($user2->id, $message->useridto);
        $this->assertSame('-10', $message->useridfrom);
    }
}
