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

use tool_mulib\local\sql;
use tool_muprog\muform\util\autocomplete\program_trait;

/**
 * Programs whose completion allocates users to the edited program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_program_edit_programid extends \tool_mulib\muform\autocomplete\base {
    use program_trait;

    /** @var string capability required in the selected program context */
    private const string CAPABILITY = 'tool/muprog:allocate';

    /** @var \context edited program context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $programid edited program
     */
    public function __construct(
        /** @var int edited program id */
        private readonly int $programid
    ) {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($program->contextid);
        require_capability('tool/muprog:edit', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        return $this->search_programs(self::CAPABILITY, $query, $maxitems, [], $this->get_where());
    }

    #[\Override]
    public function label(string $value): ?string {
        return $this->program_label(self::CAPABILITY, $value, $this->get_where());
    }

    /**
     * Other programs of the same tenant, or programs without tenant when the edited program has none.
     *
     * @return sql
     */
    private function get_where(): sql {
        return sql::join(' AND ', array_filter([
            new sql('p.id <> ?', [$this->programid]),
            self::get_program_tenant_where($this->context, true),
        ]));
    }
}
