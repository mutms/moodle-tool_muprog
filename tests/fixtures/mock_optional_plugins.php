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

/**
 * Test doubles for optional tool_mutenancy and tool_mutrain hooks.
 *
 * @package    tool_muprog
 * @copyright  2025 e-Learning Team, Universiti Malaysia Terengganu
 */

namespace tool_mutenancy\hook {
    if (!class_exists(tenant_management_menu::class)) {
        /**
         * Minimal tenant management menu hook double for tests.
         */
        class tenant_management_menu {
            /** @var \context */
            public $catcontext;
            /** @var tenant_management_node */
            public $tenantnode;

            public function __construct(\context $catcontext) {
                $this->catcontext = $catcontext;
                $this->tenantnode = new tenant_management_node();
            }
        }
    }

    /**
     * Node double that records added entries.
     */
    class tenant_management_node {
        /** @var array<int, array{string, \moodle_url}> */
        public array $entries = [];

        public function add(string $text, \moodle_url $url): void {
            $this->entries[] = [$text, $url];
        }
    }
}

namespace tool_mutrain\hook {
    if (!class_exists(framework_usage::class)) {
        /**
         * Minimal framework usage hook double.
         */
        class framework_usage {
            /** @var int */
            protected int $frameworkid;
            /** @var int */
            protected int $usage = 0;

            public function __construct(int $frameworkid) {
                $this->frameworkid = $frameworkid;
            }

            public function get_frameworkid(): int {
                return $this->frameworkid;
            }

            public function add_usage(int $count): void {
                $this->usage += $count;
            }

            public function get_usage(): int {
                return $this->usage;
            }
        }
    }

    if (!class_exists(completion_updated::class)) {
        /**
         * Minimal completion updated hook double.
         */
        class completion_updated {
            /** @var int */
            protected int $userid;
            /** @var int[] */
            protected array $frameworkids;

            public function __construct(int $userid, array $frameworkids) {
                $this->userid = $userid;
                $this->frameworkids = $frameworkids;
            }

            /**
             * @return int[]
             */
            public function get_frameworkids(): array {
                return $this->frameworkids;
            }

            public function get_userid(): int {
                return $this->userid;
            }
        }
    }
}
