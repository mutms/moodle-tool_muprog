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
 * Provides list of programs from which the user can import notifications.
 *
 * @package     tool_muprog
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @copyright   2025 Petr Skoda
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_notification_import_frominstance extends \tool_mulib\external\form_autocomplete_field {
    /** @var int max results */
    const MAX_RESULTS = 20;

    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
            'id' => new external_value(PARAM_INT, 'Program id', VALUE_REQUIRED),
        ]);
    }

    /**
     * Gets list of programs that user can import notifications from.
     *
     * @param string $query The search request.
     * @param int $id The Program to which the program notifications have to be imported, we will exclude this program.
     * @return array
     */
    public static function execute(string $query, int $id): array {
        global $DB;

        ['query' => $query, 'id' => $id] = self::validate_parameters(
            self::execute_parameters(), ['query' => $query, 'id' => $id]);

        $targetprogram = $DB->get_record('tool_muprog_program', ['id' => $id], '*', MUST_EXIST);
        $context = \context::instance_by_id($targetprogram->contextid);

        self::validate_context($context);
        require_capability('tool/muprog:edit', $context);

        list($searchsql, $params) = \tool_muprog\local\management::get_program_search_query(null, $query, 'p');
        $params['programid'] = $id;

        $tenantselect = '';
        if (\tool_muprog\local\util::is_mutenancy_active()) {
            $targetprogramtenantid = $DB->get_field('context', 'tenantid', ['id' => $context->id]);
            if ($targetprogramtenantid) {
                $tenantselect = "AND (c.tenantid = :tenantid OR c.tenantid IS NULL)";
                $params['tenantid'] = $targetprogramtenantid;
            }
        }

        $sql = "SELECT p.id, p.fullname, p.contextid
                  FROM {tool_muprog_program} p
                  JOIN {context} c ON c.id = p.contextid
                 WHERE p.id <> :programid AND $searchsql
                       $tenantselect
                       AND EXISTS(
                           SELECT 1
                             FROM {tool_mulib_notification} lon
                            WHERE lon.instanceid = p.id AND lon.component = 'tool_muprog' AND lon.enabled = 1
                       )
              ORDER BY p.fullname ASC";
        $rs = $DB->get_recordset_sql($sql, $params);

        $notice = null;
        $list = [];
        $count = 0;
        foreach ($rs as $program) {
            if ($count > self::MAX_RESULTS) {
                $notice = get_string('toomanyrecords', 'tool_mulib', self::MAX_RESULTS);
                break;
            }
            $pcontext = \context::instance_by_id($program->contextid);
            if (!has_capability('tool/muprog:clone', $pcontext)) {
                continue;
            }
            $count++;
            $list[] = ['value' => $program->id, 'label' => format_string($program->fullname)];
        }
        $rs->close();

        return [
            'notice' => $notice,
            'list' => $list,
        ];
    }
}
