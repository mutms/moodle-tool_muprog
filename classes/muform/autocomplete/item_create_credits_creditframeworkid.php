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

use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Credit frameworks that may be added to a program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_credits_creditframeworkid extends \tool_mulib\muform\autocomplete\base {
    /** @var \context program context */
    private \context $context;

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
        $this->context = \context::instance_by_id($program->contextid);
        require_capability('tool/muprog:edit', $this->context);
        if (!mulib::is_mutrain_available()) {
            throw new \core\exception\coding_exception('mutrain is not available');
        }
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB, $USER;

        $sql = (
            new sql(
                "SELECT f.id, f.name
                   FROM {tool_mutrain_framework} f
                   /* capsubquery */
                   /* tenantjoin */
              LEFT JOIN {tool_muprog_item} pi ON pi.creditframeworkid = f.id AND pi.type = 'credits' AND pi.programid = :programid
                  WHERE f.archived = 0 AND (f.publicaccess = 1 OR capctx.id IS NOT NULL)
                        AND pi.id IS NULL /* searchsql */
               ORDER BY f.name ASC, f.id ASC",
                ['programid' => $this->programid]
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    'tool/mutrain:viewframeworks',
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [CONTEXT_SYSTEM, CONTEXT_COURSECAT])
                )->wrap("LEFT JOIN (", ")capctx ON capctx.id = f.contextid")
            )
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['name', 'idnumber'], 'f')->wrap('AND ', '')
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantjoin',
                new sql(
                    "JOIN {context} tctx ON tctx.id = f.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)",
                    [$this->context->tenantid]
                )
            );
        }

        $frameworks = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($frameworks) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($frameworks as $id => $name) {
            $result[(string)$id] = clean_text(format_string($name, true, ['context' => $this->context]));
        }
        return $result;
    }

    #[\Override]
    public function label(string $value): ?string {
        global $DB;

        if (!preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        $framework = $DB->get_record('tool_mutrain_framework', ['id' => (int)$value]);
        if (!$framework || $framework->archived) {
            return null;
        }
        $params = ['programid' => $this->programid, 'type' => 'credits', 'creditframeworkid' => $framework->id];
        if ($DB->record_exists('tool_muprog_item', $params)) {
            return null;
        }
        $frameworkcontext = \context::instance_by_id($framework->contextid, IGNORE_MISSING);
        if (!$frameworkcontext) {
            return null;
        }
        if (!$framework->publicaccess && !has_capability('tool/mutrain:viewframeworks', $frameworkcontext)) {
            return null;
        }
        if (mulib::is_mutenancy_active() && $frameworkcontext->tenantid && $this->context->tenantid) {
            if ($frameworkcontext->tenantid != $this->context->tenantid) {
                return null;
            }
        }
        return clean_text(format_string($framework->name, true, ['context' => $this->context]));
    }
}
