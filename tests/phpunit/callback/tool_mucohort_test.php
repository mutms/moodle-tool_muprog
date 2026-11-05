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

namespace tool_muprog\phpunit\callback;

use tool_muprog\callback\tool_mucohort;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests of tool_mucohort hook callbacks.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\tool_muprog\callback\tool_mucohort::class)]
#[Group('MuTMS')]
final class tool_mucohort_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        if (!\tool_mulib\local\mulib::is_mucohort_available()) {
            $this->markTestSkipped('tool_mucohort is not installed');
        }
    }

    /**
     * Returns usage items as "section: name|url|visible" strings.
     *
     * @param \stdClass $cohort
     * @return string[]
     */
    private function get_usage(\stdClass $cohort): array {
        $hook = new \tool_mucohort\hook\cohort_usage($cohort);
        tool_mucohort::cohort_usage($hook);
        $result = [];
        foreach ($hook->get_sections() as $section => $items) {
            foreach ($items as $item) {
                $result[] = $section . ': ' . $item->name . '|' . ($item->url ? 'url' : '') . '|' . ($item->visible ? 'visible' : 'hidden');
            }
        }
        return $result;
    }

    public function test_cohort_usage(): void {
        /** @var \tool_muprog_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_muprog');
        $cohort = $this->getDataGenerator()->create_cohort();
        $other = $this->getDataGenerator()->create_cohort();
        $generator->create_program(['fullname' => 'Program <b>B</b>', 'sources' => ['cohort' => ['cohortids' => [$cohort->id]]]]);
        $generator->create_program(['fullname' => 'Program A', 'sources' => ['cohort' => ['cohortids' => [$cohort->id, $other->id]]]]);

        $section = get_string('cohort_usage_source', 'tool_muprog', get_string('source_cohort', 'tool_muprog'));
        $this->setAdminUser();
        $this->assertSame([$section . ': Program A|url|visible', $section . ': Program B|url|visible'], $this->get_usage($cohort));
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame([$section . ': Program A||hidden', $section . ': Program B||hidden'], $this->get_usage($cohort));
        $this->assertSame([], $this->get_usage($this->getDataGenerator()->create_cohort()));
    }
}
