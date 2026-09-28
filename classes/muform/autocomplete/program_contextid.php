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

namespace tool_muprog\muform\autocomplete;

use tool_mulib\muform\util\autocomplete\category_context_trait;

/**
 * Program context: the system context or a course category where the user may edit programs.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_contextid extends \tool_mulib\muform\autocomplete\base {
    use category_context_trait;

    /** @var string capability required in the selected context */
    private const string CAPABILITY = 'tool/muprog:edit';

    /**
     * Constructor.
     *
     * @param int $currentcontextid current program context or the context of new program,
     *      it stays selectable without the capability
     */
    public function __construct(
        /** @var int current context id */
        private readonly int $currentcontextid
    ) {
        require_capability(self::CAPABILITY, \core\context::instance_by_id($currentcontextid));
    }

    #[\Override]
    public function get_args(): array {
        return [$this->currentcontextid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        return $this->search_category_contexts(self::CAPABILITY, $query, $maxitems);
    }

    #[\Override]
    public function label(string $value): ?string {
        return $this->category_context_label(self::CAPABILITY, $value, $this->currentcontextid);
    }
}
