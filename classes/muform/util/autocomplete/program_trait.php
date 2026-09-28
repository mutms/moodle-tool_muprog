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

namespace tool_muprog\muform\util\autocomplete;

use core\context;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Program search helpers for autocomplete sources of either family.
 *
 * Programs are offered when the user has a capability in the program context,
 * extra conditions may use table aliases "p" (program) and "pctx" (program context).
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait program_trait {
    /**
     * Search programs.
     *
     * @param string $capability required in the program context
     * @param string $query
     * @param int $maxitems
     * @param string[] $exclude program ids not to return
     * @param sql|null $where extra condition on table aliases "p" and "pctx"
     * @return array|null [id => label html], null when more than $maxitems match
     */
    protected function search_programs(
        string $capability,
        string $query,
        int $maxitems,
        array $exclude = [],
        ?sql $where = null
    ): ?array {
        global $DB, $USER;

        $sql = (new sql(
            "SELECT p.id, p.fullname, p.contextid
               FROM {tool_muprog_program} p
               JOIN {context} pctx ON pctx.id = p.contextid
               /* capsubquery */
              WHERE 1=1 /* exclude */ /* where */ /* searchsql */
           ORDER BY p.fullname ASC, p.id ASC"
        ))
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    $capability,
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [CONTEXT_SYSTEM, CONTEXT_COURSECAT])
                )->wrap("JOIN (", ")capctx ON capctx.id = p.contextid")
            )
            ->replace_comment(
                'searchsql',
                \tool_muprog\local\management::get_program_search_query(null, trim($query), 'p')->wrap('AND ', '')
            )
            ->replace_comment('where', ($where ?? new sql(''))->wrap('AND (', ')'));

        $exclude = array_values(array_filter($exclude, fn($id) => is_string($id) && preg_match('/^\d+$/D', $id)));
        if ($exclude) {
            [$notin, $params] = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'pex', false);
            $sql = $sql->replace_comment('exclude', new sql("AND p.id $notin", $params));
        }

        $programs = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($programs) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($programs as $program) {
            $result[(string)$program->id] = self::format_program_label($program);
        }
        return $result;
    }

    /**
     * Labels of programs that may be selected, unknown or not allowed ids are left out.
     *
     * @param string $capability required in the program context
     * @param string[] $ids
     * @param sql|null $where extra condition on table aliases "p" and "pctx"
     * @return array [id => label html]
     */
    protected function program_labels(string $capability, array $ids, ?sql $where = null): array {
        global $DB;

        $ids = array_values(array_filter($ids, fn($id) => is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id)));
        if (!$ids) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'pids');
        $sql = (new sql(
            "SELECT p.id, p.fullname, p.contextid
               FROM {tool_muprog_program} p
               JOIN {context} pctx ON pctx.id = p.contextid
              WHERE p.id $insql /* where */
           ORDER BY p.id ASC",
            $inparams
        ))->replace_comment('where', ($where ?? new sql(''))->wrap('AND (', ')'));

        $result = [];
        foreach ($DB->get_records_sql($sql->sql, $sql->params) as $program) {
            if (!has_capability($capability, context::instance_by_id($program->contextid))) {
                continue;
            }
            $result[(string)$program->id] = self::format_program_label($program);
        }
        return $result;
    }

    /**
     * Label of a program that may be selected.
     *
     * @param string $capability required in the program context
     * @param string $value program id
     * @param sql|null $where extra condition on table aliases "p" and "pctx"
     * @return string|null label html, null when unknown or not allowed
     */
    protected function program_label(string $capability, string $value, ?sql $where = null): ?string {
        return $this->program_labels($capability, [$value], $where)[$value] ?? null;
    }

    /**
     * Tenant condition on program context alias "pctx" for programs used by an item in the context.
     *
     * @param context $context context of the edited item
     * @param bool $strict true means programs of the same tenant only, or programs without tenant
     *      when the item has no tenant; false means programs of the same tenant or without tenant
     * @return sql|null null when no condition is necessary
     */
    protected static function get_program_tenant_where(context $context, bool $strict): ?sql {
        if (!mulib::is_mutenancy_active()) {
            return null;
        }
        if ($context->tenantid) {
            if ($strict) {
                return new sql("pctx.tenantid = ?", [$context->tenantid]);
            }
            return new sql("(pctx.tenantid = ? OR pctx.tenantid IS NULL)", [$context->tenantid]);
        }
        if ($strict) {
            return new sql("pctx.tenantid IS NULL");
        }
        return null;
    }

    /**
     * Format program label.
     *
     * @param \stdClass $program with fullname and contextid
     * @return string label html
     */
    private static function format_program_label(\stdClass $program): string {
        $context = context::instance_by_id($program->contextid);
        return clean_text(format_string($program->fullname, true, ['context' => $context]));
    }
}
