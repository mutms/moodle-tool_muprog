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

namespace tool_muprog\mureport\system_report;

use tool_mureport\mureport\system_report;
use tool_muprog\mureport\entity\program;
use core\exception\invalid_parameter_exception;
use core\lang_string;

/**
 * Programs in a category and its subcategories, or all programs, for program managers.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class programs extends system_report {
    /**
     * Make report instance.
     *
     * @param int $contextid system or category context
     * @return self
     */
    public static function make_instance(int $contextid): self {
        return self::from_constraints(['contextid' => $contextid]);
    }

    #[\Override]
    public static function get_title(): string {
        return get_string('programs', 'tool_muprog');
    }

    #[\Override]
    public function get_context(): \core\context {
        $context = $this->get_constraint_context();
        if (!$context || !in_array((int)$context->contextlevel, [CONTEXT_SYSTEM, CONTEXT_COURSECAT], true)) {
            throw new invalid_parameter_exception('Invalid program context');
        }
        return $context;
    }

    #[\Override]
    protected function check_access(): void {
        $this->require_capability('tool/muprog:view');
    }

    #[\Override]
    protected function define(): void {
        $this->set_main_entity(new program('program'));
        $this->add_capability_restriction('program', 'tool/muprog:view');

        $this->set_default_columns([
            'program:fullname',
            'program:idnumber',
            'program:category',
            'program:coursecount',
            'program:allocationcount',
            'program:draft',
            'program:archived',
        ]);
        $this->set_default_filters([
            'program:fullname' => ['multisearch' => true],
            'program:idnumber' => ['multisearch' => true],
            'program:id' => ['multisearch' => true],
            'program:category',
            'program:draft',
            'program:archived',
            'program:creategroups',
            'program:timecreated',
        ]);
        $this->set_preset_filters(['program:archived' => ['value' => 0]]);
        $this->set_multisearch_label(new lang_string('programsearch', 'tool_muprog'));
        $this->set_default_sorting(['program:fullname' => 'asc']);
    }

    #[\Override]
    protected function apply_constraints(): void {
        $context = $this->get_context();
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            $this->add_context_restriction('program', $context);
        }
    }
}
