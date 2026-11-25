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

namespace tool_muprog\mureport\entity;

use tool_mureport\mureport\action;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\row;
use tool_mulib\local\sql;
use core\lang_string;
use core\url;
use stdClass;

/**
 * Program allocation request entity, requests of users in tool_muprog_request waiting for approval.
 *
 * Helper join alias_program for the program of the approval source.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class request extends entity {
    #[\Override]
    public function get_default_source(): sql {
        return new sql('{tool_muprog_request}');
    }

    #[\Override]
    protected function get_default_title(): string {
        return get_string('source_approval_request', 'tool_muprog');
    }

    #[\Override]
    public function get_column_names(): array {
        return ['timerequested', 'timerejected', 'status', 'actions'];
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        $alias = $this->alias;
        $column = match ($selector) {
            'timerequested', 'timerejected' => $this->create_column(
                $selector,
                self::get_date_title($selector),
                column::TYPE_TIMESTAMP
            )
                ->add_field($selector, "$alias.$selector")
                ->set_sortable(true),
            'status' => $this->create_column('status', new lang_string('status'), column::TYPE_ENUMERATION)
                ->add_field('status', $this->get_status_sql())
                ->set_options(fn(): array => self::get_status_options())
                ->set_sortable(true),
            'actions' => $this->define_actions_column(),
            default => null,
        };
        return $column ? [$column] : [];
    }

    #[\Override]
    public function get_filter_names(): array {
        return ['status', 'timerequested', 'timerejected'];
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        $alias = $this->alias;
        $filter = match ($selector) {
            'status' => (new filter\select($alias, 'status', new lang_string('status'), $this->get_status_sql()))
                ->set_options(fn(): array => self::get_status_options()),
            'timerequested', 'timerejected' => new filter\date(
                $alias,
                $selector,
                self::get_date_title($selector),
                "$alias.$selector"
            ),
            default => null,
        };
        return $filter ? [$filter] : [];
    }

    #[\Override]
    public function get_helper_join(string $helperalias): sql {
        if ($helperalias === "{$this->alias}_program") {
            return new sql("JOIN (SELECT {$helperalias}s.id AS sourceid, {$helperalias}p.id, {$helperalias}p.archived
                                    FROM {tool_muprog_source} {$helperalias}s
                                    JOIN {tool_muprog_program} {$helperalias}p ON {$helperalias}p.id = {$helperalias}s.programid
                                 ) $helperalias ON $helperalias.sourceid = {$this->alias}.sourceid");
        }
        return parent::get_helper_join($helperalias);
    }

    #[\Override]
    public function get_context_sql(): ?sql {
        $source = "{$this->alias}_ctxs";
        $program = "{$this->alias}_ctxp";
        return new sql("(SELECT $program.contextid
                           FROM {tool_muprog_source} $source
                           JOIN {tool_muprog_program} $program ON $program.id = $source.programid
                          WHERE $source.id = {$this->alias}.sourceid)");
    }

    #[\Override]
    public function get_context_for_key(int|string $key): ?\core\context {
        global $DB;
        $sql = "SELECT p.contextid
                  FROM {tool_muprog_request} r
                  JOIN {tool_muprog_source} s ON s.id = r.sourceid
                  JOIN {tool_muprog_program} p ON p.id = s.programid
                 WHERE r.id = :id";
        $contextid = $DB->get_field_sql($sql, ['id' => $key]);
        return $contextid ? (\core\context::instance_by_id($contextid, IGNORE_MISSING) ?: null) : null;
    }

    /**
     * Define request actions.
     *
     * @return column
     */
    private function define_actions_column(): column {
        $alias = $this->alias;
        $column = $this->create_actions_column('actions', new lang_string('actions'))
            ->add_helper_join("{$alias}_program")
            ->add_field('id', "$alias.id")
            ->add_field('timerejected', "$alias.timerejected")
            ->add_field('programarchived', "{$alias}_program.archived")
            ->add_field('allocated', "CASE
                                          WHEN EXISTS (SELECT 'x'
                                                         FROM {tool_muprog_allocation} {$alias}_a
                                                        WHERE {$alias}_a.programid = {$alias}_program.id
                                                              AND {$alias}_a.userid = $alias.userid)
                                          THEN 1 ELSE 0
                                      END");

        $allowed = function (row $row) use ($alias): bool {
            $context = $row->get_context($alias);
            return $context && has_capability('tool/muprog:allocate', $context);
        };
        $url = fn(string $script): \Closure => fn(stdClass $fields): url
            => new url("/admin/tool/muprog/management/$script.php", ['id' => $fields->id]);

        $column->add_action(new action('approve', new lang_string('source_approval_requestapprove', 'tool_muprog'), 't/approve'))
            ->set_available(fn(stdClass $fields, row $row): bool => !$fields->timerejected && !$fields->programarchived
                && !$fields->allocated && $allowed($row))
            ->set_url($url('source_approval_approve'))
            ->set_dialog('sm');
        $column->add_action(new action('reject', new lang_string('source_approval_requestreject', 'tool_muprog'), 't/block'))
            ->set_available(fn(stdClass $fields, row $row): bool => !$fields->timerejected && $allowed($row))
            ->set_url($url('source_approval_reject'))
            ->set_dialog('sm');
        $column->add_action(new action('delete', new lang_string('source_approval_requestdelete', 'tool_muprog'), 't/delete'))
            ->set_available(fn(stdClass $fields, row $row): bool => ($fields->timerejected || $fields->allocated)
                && $allowed($row))
            ->set_url($url('source_approval_delete'))
            ->set_dialog('sm')
            ->set_destructive();

        return $column;
    }

    /**
     * Returns status of requests in SQL.
     *
     * @return string
     */
    private function get_status_sql(): string {
        return "CASE WHEN {$this->alias}.timerejected IS NULL THEN 'pending' ELSE 'rejected' END";
    }

    /**
     * Returns request statuses.
     *
     * @return array status => label
     */
    public static function get_status_options(): array {
        return [
            'pending' => get_string('source_approval_requestpending', 'tool_muprog'),
            'rejected' => get_string('source_approval_requestrejected', 'tool_muprog'),
        ];
    }

    /**
     * Returns title of a request date.
     *
     * @param string $name
     * @return lang_string
     */
    private static function get_date_title(string $name): lang_string {
        return match ($name) {
            'timerequested' => new lang_string('source_approval_daterequested', 'tool_muprog'),
            'timerejected' => new lang_string('source_approval_daterejected', 'tool_muprog'),
        };
    }
}
