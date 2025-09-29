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

use tool_muprog\callback\tool_mutenancy;

require_once(__DIR__ . '/../../fixtures/mock_optional_plugins.php');

/**
 * tool_mutenancy callback coverage.
 *
 * @package    tool_muprog
 * @copyright  2025 e-Learning Team, Universiti Malaysia Terengganu
 * @covers     \tool_muprog\callback\tool_mutenancy
 */
final class tool_mutenancy_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_menu_not_visible_when_programs_inactive(): void {
        set_config('active', 0, 'tool_muprog');

        $hook = new \tool_mutenancy\hook\tenant_management_menu(\context_system::instance());

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        tool_mutenancy::tenant_management_menu($hook);

        $this->assertEmpty($hook->tenantnode->entries);
    }

    public function test_menu_not_visible_without_capability(): void {
        set_config('active', 1, 'tool_muprog');

        $category = $this->getDataGenerator()->create_category();
        $context = \context_coursecat::instance($category->id);

        $hook = new \tool_mutenancy\hook\tenant_management_menu($context);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        tool_mutenancy::tenant_management_menu($hook);

        $this->assertEmpty($hook->tenantnode->entries);
    }

    public function test_menu_visible_for_authorised_user(): void {
        set_config('active', 1, 'tool_muprog');
        $this->setAdminUser();

        $category = $this->getDataGenerator()->create_category();
        $context = \context_coursecat::instance($category->id);

        $hook = new \tool_mutenancy\hook\tenant_management_menu($context);

        tool_mutenancy::tenant_management_menu($hook);

        $this->assertCount(1, $hook->tenantnode->entries);
        [$text, $url] = $hook->tenantnode->entries[0];
        $this->assertSame(get_string('management', 'tool_muprog'), $text);
        $this->assertSame((string)$context->id, $url->get_param('contextid'));
    }
}
