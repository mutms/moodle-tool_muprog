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

namespace tool_muprog\muform\autocompletemany;

use tool_muprog\muform\util\autocomplete\program_trait;

/**
 * Programs that may be exported.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class export_programids extends \tool_mulib\muform\autocompletemany\base {
    use program_trait;

    /** @var string capability required in program contexts */
    private const string CAPABILITY = 'tool/muprog:export';

    /**
     * Constructor.
     *
     * @param int $contextid context of the export page
     */
    public function __construct(
        /** @var int context id of the export page */
        private readonly int $contextid
    ) {
        require_capability(self::CAPABILITY, \context::instance_by_id($contextid));
    }

    #[\Override]
    public function get_args(): array {
        return [$this->contextid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        return $this->search_programs(self::CAPABILITY, $query, $maxitems, $exclude);
    }

    #[\Override]
    public function labels(array $values): array {
        return $this->program_labels(self::CAPABILITY, $values);
    }
}
