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

namespace tool_muprog\muform\tagarea;

use core\context;

/**
 * Program tags, tag instances live in the system context.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program extends \tool_mulib\muform\tagarea\base {
    /**
     * Constructor.
     *
     * @param int|null $programid null for new program
     * @param int $contextid program context, context of new program
     */
    public function __construct(
        /** @var int|null program id */
        private readonly ?int $programid,
        /** @var int program context id */
        private readonly int $contextid
    ) {
        global $DB;
        if ($programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
            if ($program->contextid != $contextid) {
                throw new \core\exception\invalid_parameter_exception('Program context mismatch');
            }
        }
        require_capability('tool/muprog:edit', context::instance_by_id($contextid));
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid, $this->contextid];
    }

    #[\Override]
    public function get_component(): string {
        return 'tool_muprog';
    }

    #[\Override]
    public function get_itemtype(): string {
        return 'tool_muprog_program';
    }

    #[\Override]
    public function get_context(): context {
        return \core\context\system::instance();
    }

    #[\Override]
    public function get_itemid(): ?int {
        return $this->programid;
    }
}
