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

namespace tool_muprog\muform\autocomplete;

use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Programs that notifications can be imported from.
 *
 * @package     tool_muprog
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class notification_import_frominstance extends \tool_mulib\muform\autocomplete\base {
    /** @var \stdClass target program */
    private \stdClass $program;

    /**
     * Constructor.
     *
     * @param int $programid target program
     */
    public function __construct(int $programid) {
        global $DB;
        $this->program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        require_capability('tool/muprog:edit', \context::instance_by_id($this->program->contextid));
    }

    #[\Override]
    public function get_args(): array {
        return [(int)$this->program->id];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB, $USER;

        $context = \context::instance_by_id($this->program->contextid);
        $sql = (
            new sql(
                "SELECT p.id, p.fullname
                   FROM {tool_muprog_program} p
                   /* capsubquery */
                   /* tenantjoin */
                  WHERE p.id <> :programid /* searchsql */
                        AND EXISTS(
                            SELECT 1
                              FROM {tool_mulib_notification} lon
                             WHERE lon.instanceid = p.id AND lon.component = 'tool_muprog' AND lon.enabled = 1
                        )
               ORDER BY p.fullname ASC",
                ['programid' => $this->program->id]
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'tool/muprog:clone',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [\context_system::LEVEL, \context_coursecat::LEVEL])
                )->wrap("JOIN (", ")capctx ON capctx.id = p.contextid")
            )
            ->replace_comment(
                'searchsql',
                \tool_muprog\local\management::get_program_search_query(null, $query, 'p')->wrap('AND ', '')
            );
        if (mulib::is_mutenancy_active() && $context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantjoin',
                new sql("JOIN {context} tctx ON tctx.id = p.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)", [$context->tenantid])
            );
        }

        $programs = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($programs) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($programs as $program) {
            $result[(string)$program->id] = clean_text(format_string($program->fullname, true, ['context' => $context]));
        }
        return $result;
    }

    #[\Override]
    public function label(string $value): ?string {
        global $DB;

        $program = $DB->get_record('tool_muprog_program', ['id' => (int)$value]);
        if (!$program || (int)$program->id === (int)$this->program->id) {
            return null;
        }
        $programcontext = \context::instance_by_id($program->contextid);
        if (!has_capability('tool/muprog:clone', $programcontext)) {
            return null;
        }
        return clean_text(format_string($program->fullname, true, ['context' => $programcontext]));
    }
}
