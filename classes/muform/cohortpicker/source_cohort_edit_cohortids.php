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

namespace tool_muprog\muform\cohortpicker;

use core\context;

/**
 * Cohorts of the cohort allocation source.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_cohort_edit_cohortids extends \tool_mulib\muform\cohortpicker\base {
    /** @var context program context */
    private context $context;

    /**
     * Constructor.
     *
     * @param int $programid
     */
    public function __construct(
        /** @var int program id */
        private readonly int $programid
    ) {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $this->context = context::instance_by_id($program->contextid);
        require_capability('tool/muprog:edit', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid];
    }

    #[\Override]
    protected function get_context(): context {
        return $this->context;
    }

    /**
     * Cohorts already allocated stay valid.
     *
     * @return int[]
     */
    #[\Override]
    protected function get_current_cohortids(): array {
        global $DB;
        $source = $DB->get_record('tool_muprog_source', ['programid' => $this->programid, 'type' => 'cohort']);
        if (!$source) {
            return [];
        }
        return array_map('intval', $DB->get_fieldset('tool_muprog_src_cohort', 'cohortid', ['sourceid' => $source->id]));
    }
}
