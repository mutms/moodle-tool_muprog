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
use tool_muprog\local\extdb\query\allocation;

/**
 * External database allocation query of a program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_edit_queryid extends \tool_mulib\muform\autocomplete\base {
    /** @var string capability required in the query context */
    private const string CAPABILITY = 'tool/mulib:useextdb';

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
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB, $USER;

        [$insql, $inparams] = $DB->get_in_or_equal($this->context->get_parent_context_ids(true), SQL_PARAMS_NAMED, 'qctx');
        $sql = (
            new sql(
                "SELECT q.id, q.name
                   FROM {tool_mulib_extdb_query} q
                   /* capsubquery */
                   /* tenantjoin */
                  WHERE q.contextid $insql AND q.component = :component AND q.type = :type
                        /* search */
               ORDER BY q.name ASC, q.id ASC",
                array_merge($inparams, ['component' => allocation::get_component(), 'type' => allocation::get_type()])
            )
        )
            ->replace_comment(
                'capsubquery',
                context_map::get_contexts_by_capability_query(
                    self::CAPABILITY,
                    $USER->id,
                    new sql("(ctx.contextlevel = ? OR ctx.contextlevel = ?)", [CONTEXT_SYSTEM, CONTEXT_COURSECAT])
                )->wrap("JOIN (", ")capctx ON capctx.id = q.contextid")
            )
            ->replace_comment(
                'search',
                search_util::get_search_query(trim($query), ['name', 'note'], 'q')->wrap('AND ', '')
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantjoin',
                new sql(
                    "JOIN {context} tctx ON tctx.id = q.contextid AND (tctx.tenantid = ? OR tctx.tenantid IS NULL)",
                    [$this->context->tenantid]
                )
            );
        }

        $queries = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($queries) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($queries as $id => $name) {
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
        $query = $DB->get_record('tool_mulib_extdb_query', ['id' => (int)$value]);
        if (!$query) {
            return null;
        }
        $qcontext = \context::instance_by_id($query->contextid, IGNORE_MISSING);
        if (!$qcontext) {
            return null;
        }
        if (mulib::is_mutenancy_active()) {
            if ($qcontext->tenantid && $this->context->tenantid && $qcontext->tenantid != $this->context->tenantid) {
                // Do not allow queries from other tenants, not even the current one.
                return null;
            }
        }
        $label = clean_text(format_string($query->name, true, ['context' => $this->context]));
        $source = $DB->get_record('tool_muprog_source', ['programid' => $this->programid, 'type' => 'extdb']);
        if ($source && $source->auxint1 == $query->id) {
            // Current value is always ok.
            return $label;
        }
        if ($query->component !== allocation::get_component() || $query->type !== allocation::get_type()) {
            return null;
        }
        if (!in_array($qcontext->id, $this->context->get_parent_context_ids(true))) {
            return null;
        }
        if (!has_capability(self::CAPABILITY, $qcontext)) {
            return null;
        }
        return $label;
    }
}
