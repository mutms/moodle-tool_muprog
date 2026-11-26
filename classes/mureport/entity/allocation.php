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
use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\customfields;
use tool_mureport\mureport\entity;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\row;
use tool_mulib\local\sql;
use core\lang_string;
use core_customfield\field_controller;
use core\url;
use stdClass;

/**
 * Program allocation entity, allocations of users in tool_muprog_allocation.
 *
 * Helper joins: alias_program for the allocated program, alias_source for the allocation source.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allocation extends entity {
    /** @var customfields|null */
    private ?customfields $customfields = null;

    /** @var bool rows are restricted to allocations of programs the user may view */
    private bool $customfieldsmanaged = false;

    #[\Override]
    public function get_default_source(): sql {
        return new sql('{tool_muprog_allocation}');
    }

    #[\Override]
    protected function get_default_title(): string {
        return get_string('allocation', 'tool_muprog');
    }

    #[\Override]
    public function get_column_names(): array {
        return [
            'timeallocated', 'timestart', 'timedue', 'timeend', 'timecompleted', 'source', 'progress', 'status', 'archived',
            'actions',
            ...$this->get_customfields()->get_column_names(),
        ];
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        $customfields = $this->get_customfields()->define_columns($selector);
        if ($customfields !== null) {
            return $customfields;
        }
        $alias = $this->alias;
        $column = match ($selector) {
            'timeallocated', 'timestart', 'timedue', 'timeend', 'timecompleted' => $this->create_column(
                $selector,
                self::get_date_title($selector),
                column::TYPE_TIMESTAMP
            )
                ->add_field($selector, "$alias.$selector")
                ->set_sortable(true),
            'source' => $this->define_source_column(),
            'progress' => $this->define_progress_column(),
            'status' => $this->define_status_column(),
            'actions' => $this->define_actions_column(),
            'archived' => $this->create_column('archived', new lang_string('archived', 'tool_muprog'), column::TYPE_BOOLEAN)
                ->add_field('archived', "$alias.archived")
                ->set_sortable(true),
            default => null,
        };
        return $column ? [$column] : [];
    }

    #[\Override]
    public function get_filter_names(): array {
        return [
            'status', 'source', 'archived', 'timeallocated', 'timestart', 'timedue', 'timeend', 'timecompleted',
            ...$this->get_customfields()->get_filter_names(),
        ];
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        $customfields = $this->get_customfields()->define_filters($selector);
        if ($customfields !== null) {
            return $customfields;
        }
        $alias = $this->alias;
        $filter = match ($selector) {
            'status' => (new filter\checkboxes(
                $alias,
                'status',
                new lang_string('programstatus', 'tool_muprog'),
                $this->get_status_sql()
            ))
                ->add_helper_join("{$alias}_program")
                ->set_options(fn(): array => self::get_status_options()),
            'source' => (new filter\checkboxes($alias, 'source', new lang_string('source', 'tool_muprog'), "{$alias}_source.type"))
                ->add_helper_join("{$alias}_source")
                ->set_options(fn(): array => \tool_muprog\local\allocation::get_source_names())
                ->set_present_values_only(),
            'archived' => new filter\boolean($alias, 'archived', new lang_string('archived', 'tool_muprog'), "$alias.archived"),
            'timeallocated', 'timestart', 'timedue', 'timeend', 'timecompleted' => new filter\date(
                $alias,
                $selector,
                self::get_date_title($selector),
                "$alias.$selector"
            ),
            default => null,
        };
        return $filter ? [$filter] : [];
    }

    /**
     * Allocation custom fields visible to managers are not checked in each row,
     * for reports with rows restricted to allocations of programs the user may view.
     *
     * @return self
     */
    public function set_customfields_managed(): self {
        $this->customfieldsmanaged = true;
        return $this;
    }

    /**
     * Returns allocation custom fields.
     *
     * @return customfields
     */
    public function get_customfields(): customfields {
        if ($this->customfields === null) {
            $this->customfields = (new customfields($this, 'tool_muprog', 'allocation'))
                ->set_unrestricted(fn(field_controller $field): bool => $field->get_configdata_property('visibilityeveryone')
                    || ($this->customfieldsmanaged && $field->get_configdata_property('visibilitymanagers'))
                    || has_any_capability(['tool/muprog:allocate', 'tool/muprog:admin'], \core\context\system::instance()))
                ->set_cell_visibility(function (field_controller $field, row $row): bool {
                    $context = $row->get_context($this->alias);
                    if (!$context) {
                        return false;
                    }
                    if ($field->get_configdata_property('visibilitymanagers') && has_capability('tool/muprog:view', $context)) {
                        return true;
                    }
                    if (has_capability('tool/muprog:allocate', $context) || has_capability('tool/muprog:admin', $context)) {
                        return true;
                    }
                    if ($field->get_configdata_property('visibilityallocatee')) {
                        return $this->customfields->get_handler()->can_view($field, (int)$row->get_entity_key($this->alias));
                    }
                    return false;
                });
        }
        return $this->customfields;
    }

    #[\Override]
    public function get_helper_join(string $helperalias): sql {
        if ($helperalias === "{$this->alias}_program") {
            return new sql("JOIN {tool_muprog_program} $helperalias ON $helperalias.id = {$this->alias}.programid");
        }
        if ($helperalias === "{$this->alias}_source") {
            return new sql("JOIN {tool_muprog_source} $helperalias ON $helperalias.id = {$this->alias}.sourceid");
        }
        return $this->get_customfields()->get_helper_join($helperalias) ?? parent::get_helper_join($helperalias);
    }

    #[\Override]
    public function get_context_sql(): ?sql {
        $program = "{$this->alias}_ctxp";
        return new sql("(SELECT $program.contextid
                           FROM {tool_muprog_program} $program
                          WHERE $program.id = {$this->alias}.programid)");
    }

    #[\Override]
    public function get_context_for_key(int|string $key): ?\core\context {
        global $DB;
        $sql = "SELECT p.contextid
                  FROM {tool_muprog_allocation} a
                  JOIN {tool_muprog_program} p ON p.id = a.programid
                 WHERE a.id = :id";
        $contextid = $DB->get_field_sql($sql, ['id' => $key]);
        return $contextid ? (\core\context::instance_by_id($contextid, IGNORE_MISSING) ?: null) : null;
    }

    /**
     * Define allocation source column, sources may link to related records such as certifications.
     *
     * @return column
     */
    private function define_source_column(): column {
        $alias = $this->alias;
        $name = function (stdClass $fields): string {
            $class = \tool_muprog\local\allocation::get_source_classname($fields->type);
            return $class ? $class::get_name() : '';
        };
        return $this->create_column('source', new lang_string('source', 'tool_muprog'), column::TYPE_TEXT)
            ->add_helper_join("{$alias}_source")
            ->add_field('type', "{$alias}_source.type")
            ->add_field('id', "$alias.id")
            ->add_field('sourceid', "$alias.sourceid")
            ->add_field('programid', "$alias.programid")
            ->add_formatter(
                'name',
                fn(cell $cell): string => s($name($cell->get_fields())),
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields()))
            )
            ->add_formatter('details', function (cell $cell): string {
                global $DB;
                $fields = $cell->get_fields();
                $class = \tool_muprog\local\allocation::get_source_classname($fields->type);
                if (!$class) {
                    return '';
                }
                $row = $cell->get_row();
                $program = $row->get_cached_record('tool_muprog_program', (int)$fields->programid);
                $source = $row->get_cached_record('tool_muprog_source', (int)$fields->sourceid);
                $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $fields->id], '*', MUST_EXIST);
                return $class::render_allocation_source($program, $source, $allocation);
            }, fn(cell $cell): export_value => export_value::text($name($cell->get_fields())))
            ->set_default_formatter('details');
    }

    /**
     * Define progress column, percentage of completed program items.
     *
     * @return column
     */
    private function define_progress_column(): column {
        $alias = $this->alias;
        $percentage = fn(cell $cell): ?int => $cell->get_value() === null ? null : (int)round((float)$cell->get_value());
        return $this->create_column('progress', new lang_string('programprogress', 'tool_muprog'), column::TYPE_DECIMAL)
            ->add_helper_join("{$alias}_program")
            ->add_field('progress', "CASE
                                         WHEN {$alias}_program.itemscount = 0 THEN NULL
                                         ELSE 100.0 * $alias.itemscompleted / {$alias}_program.itemscount
                                     END")
            ->set_sortable(true)
            ->add_formatter(
                'percentage',
                fn(cell $cell): string => $percentage($cell) === null ? '' : $percentage($cell) . ' %',
                fn(cell $cell): export_value => $cell->get_value() === null
                    ? export_value::empty() : export_value::percent((float)$cell->get_value() / 100)
            )
            ->set_default_formatter('percentage');
    }

    /**
     * Define completion status column, the badge of program allocation status.
     *
     * @return column
     */
    private function define_status_column(): column {
        $alias = $this->alias;
        $column = $this->create_column('status', new lang_string('programstatus', 'tool_muprog'), column::TYPE_TEXT)
            ->add_helper_join("{$alias}_program")
            ->add_field('programarchived', "{$alias}_program.archived");
        foreach (['id', 'timestart', 'timedue', 'timeend', 'timecompleted', 'archived'] as $field) {
            $column->add_field($field, "$alias.$field");
        }
        $program = fn(stdClass $fields): stdClass => (object)['archived' => $fields->programarchived];
        return $column
            ->add_formatter(
                'badge',
                function (cell $cell) use ($program): string {
                    $fields = $cell->get_fields();
                    return \tool_muprog\local\allocation::get_completion_status_html($program($fields), $fields);
                },
                function (cell $cell) use ($program): export_value {
                    $fields = $cell->get_fields();
                    $status = \tool_muprog\local\allocation::get_completion_status_plain($program($fields), $fields);
                    return export_value::text($status);
                }
            )
            ->set_default_formatter('badge');
    }

    /**
     * Define allocation actions, the source of each allocation decides what is possible.
     *
     * @return column
     */
    private function define_actions_column(): column {
        $alias = $this->alias;
        $column = $this->create_actions_column('actions', new lang_string('actions'))
            ->add_helper_join("{$alias}_program")
            ->add_helper_join("{$alias}_source")
            ->add_field('type', "{$alias}_source.type")
            ->add_field('programarchived', "{$alias}_program.archived");
        foreach (['id', 'programid', 'sourceid', 'archived'] as $field) {
            $column->add_field($field, "$alias.$field");
        }

        $allocations = [];
        $possible = function (stdClass $fields, row $row, string $capability, string $check) use ($alias, &$allocations): bool {
            global $DB;
            $context = $row->get_context($alias);
            if (!$context || !has_capability($capability, $context)) {
                return false;
            }
            $class = \tool_muprog\local\allocation::get_source_classname($fields->type);
            if (!$class) {
                return false;
            }
            $allocations[$fields->id] ??= $DB->get_record('tool_muprog_allocation', ['id' => $fields->id]);
            if (!$allocations[$fields->id]) {
                return false;
            }
            $program = $row->get_cached_record('tool_muprog_program', (int)$fields->programid);
            $source = $row->get_cached_record('tool_muprog_source', (int)$fields->sourceid);
            return $class::$check($program, $source, $allocations[$fields->id]);
        };
        $url = fn(string $script): \Closure => fn(stdClass $fields): url
            => new url("/admin/tool/muprog/management/$script.php", ['id' => $fields->id]);

        $column->add_action(new action('update', new lang_string('allocation_update', 'tool_muprog'), 'i/settings'))
            ->set_available(fn(stdClass $fields, row $row): bool => !$fields->archived && !$fields->programarchived
                && $possible($fields, $row, 'tool/muprog:manageallocation', 'is_allocation_update_possible'))
            ->set_url($url('allocation_update'))
            ->set_dialog('lg');
        $column->add_action(new action('archive', new lang_string('allocation_archive', 'tool_muprog'), 'i/lock'))
            ->set_available(fn(stdClass $fields, row $row): bool => !$fields->archived && !$fields->programarchived
                && $possible($fields, $row, 'tool/muprog:deallocate', 'is_allocation_archive_possible'))
            ->set_url($url('allocation_archive'))
            ->set_dialog('sm');
        $column->add_action(new action('restore', new lang_string('allocation_restore', 'tool_muprog'), 'i/unlock'))
            ->set_available(fn(stdClass $fields, row $row): bool => $fields->archived && !$fields->programarchived
                && $possible($fields, $row, 'tool/muprog:allocate', 'is_allocation_restore_possible'))
            ->set_url($url('allocation_restore'))
            ->set_dialog('sm');
        $column->add_action(new action('delete', new lang_string('deleteallocation', 'tool_muprog'), 'i/delete'))
            ->set_available(fn(stdClass $fields, row $row): bool
                => $possible($fields, $row, 'tool/muprog:deallocate', 'is_allocation_delete_possible'))
            ->set_url($url('allocation_delete'))
            ->set_dialog('sm')
            ->set_destructive();

        return $column;
    }

    /**
     * Returns status of allocations in SQL, the same order of checks as allocation::get_completion_status().
     *
     * @return sql
     */
    private function get_status_sql(): sql {
        $alias = $this->alias;
        $program = "{$alias}_program";
        return new sql(
            "CASE
                WHEN ($program.archived = 1 OR $alias.archived = 1) AND $alias.timecompleted IS NOT NULL THEN 'archivedcompleted'
                WHEN $program.archived = 1 OR $alias.archived = 1 THEN 'archived'
                WHEN $alias.timecompleted IS NOT NULL THEN 'completed'
                WHEN $alias.timestart > :{$alias}now1 THEN 'future'
                WHEN $alias.timeend IS NOT NULL AND $alias.timeend < :{$alias}now2 THEN 'failed'
                WHEN $alias.timedue IS NOT NULL AND $alias.timedue < :{$alias}now3 THEN 'overdue'
                ELSE 'open'
            END",
            ["{$alias}now1" => time(), "{$alias}now2" => time(), "{$alias}now3" => time()]
        );
    }

    /**
     * Returns allocation statuses.
     *
     * @return array status => label
     */
    public static function get_status_options(): array {
        $result = [];
        foreach (['open', 'future', 'overdue', 'completed', 'failed', 'archived', 'archivedcompleted'] as $status) {
            $result[$status] = get_string('programstatus_' . $status, 'tool_muprog');
        }
        return $result;
    }

    /**
     * Returns title of an allocation date.
     *
     * @param string $name
     * @return lang_string
     */
    private static function get_date_title(string $name): lang_string {
        return match ($name) {
            'timeallocated' => new lang_string('allocationdate', 'tool_muprog'),
            'timestart' => new lang_string('programstart', 'tool_muprog'),
            'timedue' => new lang_string('duedate', 'tool_muprog'),
            'timeend' => new lang_string('programend', 'tool_muprog'),
            'timecompleted' => new lang_string('programcompletion', 'tool_muprog'),
        };
    }
}
