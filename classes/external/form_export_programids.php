<?php
// This file is part of Programs for Moodle™.
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

namespace tool_muprog\external;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Provides list of programs that can be exported.
 *
 * @package     tool_muprog
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_export_programids extends \tool_mulib\external\form_autocomplete_field {
    /** @var int max results */
    const MAX_RESULTS = 20;

    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return true;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of available programs.
     *
     * @param string $query The search request.
     * @return array
     */
    public static function execute(string $query): array {
        global $DB;

        $params['query'] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        list($searchsql, $params) = \tool_muprog\local\management::get_program_search_query(null, $query, 'p');

        $sql = "SELECT p.id, p.fullname, p.contextid
                  FROM {tool_muprog_program} p
                  JOIN {context} c ON c.id = p.contextid
                 WHERE $searchsql
              ORDER BY p.fullname ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        $notice = null;
        $list = [];
        $count = 0;
        foreach ($rs as $program) {
            $context = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:export', $context)) {
                continue;
            }
            $count++;
            if ($count > self::MAX_RESULTS) {
                $notice = get_string('toomanyrecords', 'tool_mulib', self::MAX_RESULTS);
                break;
            }
            $list[] = ['value' => $program->id, 'label' => format_string($program->fullname)];
        }
        $rs->close();

        return [
            'notice' => $notice,
            'list' => $list,
        ];
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            global $DB;

            $program = $DB->get_record('tool_muprog_program', ['id' => $value]);
            if (!$program) {
                return get_string('error');
            }
            return format_string($program->fullname);
        };
    }

    /**
     * Validate user can export programs.
     *
     * @param array $programids
     * @return string|null null means ids ok, string is error
     */
    public static function validate_programids(array $programids): ?string {
        global $DB;
        foreach ($programids as $programid) {
            $program = $DB->get_record('tool_muprog_program', ['id' => $programid]);
            if (!$program) {
                return get_string('error');
            }
            $context = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:export', $context)) {
                return get_string('error');
            }
        }
        return null;
    }
}
