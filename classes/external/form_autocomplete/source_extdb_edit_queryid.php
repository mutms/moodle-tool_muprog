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

namespace tool_muprog\external\form_autocomplete;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_mulib\local\sql;

/**
 * External database sync query autocompletion.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_extdb_edit_queryid extends \tool_mulib\external\form_autocomplete\base {
    /** @var string|null override with name of item database table */
    protected const ITEM_TABLE = 'tool_mulib_extdb_query';
    /** @var string|null override with name of item field used for label */
    protected const ITEM_FIELD = 'name';

    #[\Override]
    public static function get_multiple(): bool {
        return false;
    }

    #[\Override]
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'programid' => new external_value(PARAM_INT, 'Program id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available queries.
     *
     * @param string $query The search request.
     * @param int $programid
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB;

        [
            'query' => $query,
            'programid' => $programid,
        ] = self::validate_parameters(
            self::execute_parameters(),
            [
                'query' => $query,
                'programid' => $programid,
            ]
        );

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        $contextids = $context->get_parent_context_ids(true);
        $contextids = implode(',', $contextids);

        $sql = new sql(
            "SELECT q.id, q.name, q.contextid
               FROM {tool_mulib_extdb_query} q
               JOIN {context} c ON c.id = q.contextid
              WHERE c.id IN ($contextids) /* search */ /* tenant */
           ORDER BY q.name ASC"
        );

        $search = self::get_search_query($query, ['name', 'note'], 'q');
        $sql->replace_comment('search', $search->wrap('AND ', ''));

        if (\tool_mulib\local\mulib::is_mutenancy_active()) {
            if ($context->tenantid) {
                $sql->replace_comment('tenant', "AND (c.tenantid IS NULL OR c.tenantid = ?)", [$context->tenantid]);
            }
        }

        $rs = $DB->get_recordset_sql($sql->sql, $sql->params);

        $queries = [];
        $i = 0;
        foreach ($rs as $query) {
            $qcontext = \context::instance_by_id($query->contextid);
            if (!has_capability('tool/mulib:useextdb', $qcontext)) {
                continue;
            }
            $queries[$query->id] = $query;
            $i++;
            if ($i > self::MAX_RESULTS) {
                break;
            }
        }
        $rs->close();

        return self::prepare_result($queries, $context);
    }

    #[\Override]
    public static function validate_value(int $value, array $args, \context $context): ?string {
        global $DB;
        if (!$value) {
            // No value means sync is disabled.
            return null;
        }
        $programid = $args['programid'];
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $query = $DB->get_record('tool_mulib_extdb_query', ['id' => $value]);
        if (!$query) {
            return get_string('error');
        }
        $qcontext = \context::instance_by_id($query->contextid, IGNORE_MISSING);
        if (!$qcontext) {
            return get_string('error');
        }
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            $programcontext = \context::instance_by_id($program->contextid);
            if ($qcontext->tenantid && $programcontext->tenantid && $qcontext->tenantid != $programcontext->tenantid) {
                // Do not allow queries from other tenants.
                return get_string('error');
            }
        }
        $source = $DB->get_record('tool_muprog_source', ['programid' => $programid, 'type' => 'extdb']);
        if ($source && $source->auxint1 == $value) {
            // Current value is ok, unless it breaks tenant separation rules.
            return null;
        }
        if (!has_capability('tool/mulib:useextdb', $qcontext)) {
            return get_string('error');
        }
        return null;
    }
}
