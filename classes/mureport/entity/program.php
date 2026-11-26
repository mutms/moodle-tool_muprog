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

use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\customfields;
use tool_mureport\mureport\entity;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\row;
use tool_mulib\local\sql;
use core\lang_string;
use core\url;
use core_customfield\field_controller;
use stdClass;

/**
 * Program entity, programs in tool_muprog_program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program extends entity {
    /** @var customfields|null */
    private ?customfields $customfields = null;

    /** @var bool rows are restricted to programs the user may view */
    private bool $customfieldsmanaged = false;

    #[\Override]
    public function get_default_source(): sql {
        return new sql('{tool_muprog_program}');
    }

    #[\Override]
    protected function get_default_title(): string {
        return get_string('program', 'tool_muprog');
    }

    #[\Override]
    public function get_context_sql(): ?sql {
        return new sql("{$this->alias}.contextid");
    }

    #[\Override]
    public function get_context_for_key(int|string $key): ?\core\context {
        global $DB;
        $contextid = $DB->get_field('tool_muprog_program', 'contextid', ['id' => $key]);
        return $contextid ? (\core\context::instance_by_id($contextid, IGNORE_MISSING) ?: null) : null;
    }

    #[\Override]
    public function get_column_names(): array {
        return [
            'fullname', 'idnumber', 'id', 'category', 'coursecount', 'allocationcount', 'draft', 'archived', 'creategroups',
            'timecreated', ...$this->get_customfields()->get_column_names(),
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
            'fullname' => $this->define_fullname_column(),
            'idnumber' => $this->create_column('idnumber', new lang_string('programidnumber', 'tool_muprog'), column::TYPE_TEXT)
                ->add_field('idnumber', "$alias.idnumber")
                ->set_sortable(true),
            'id' => $this->create_column('id', new lang_string('programinternalid', 'tool_muprog'), column::TYPE_INTEGER)
                ->add_field('id', "$alias.id")
                ->set_sortable(true),
            'category' => $this->define_category_column(),
            'coursecount' => $this->define_coursecount_column(),
            'allocationcount' => $this->define_allocationcount_column(),
            'draft', 'archived', 'creategroups' => $this->create_column(
                $selector,
                new lang_string($selector, 'tool_muprog'),
                column::TYPE_BOOLEAN
            )
                ->add_field($selector, "$alias.$selector")
                ->set_sortable(true),
            'timecreated' => $this->create_column(
                'timecreated',
                new lang_string('timecreated', 'core_reportbuilder'),
                column::TYPE_TIMESTAMP
            )
                ->add_field('timecreated', "$alias.timecreated")
                ->set_sortable(true),
            default => null,
        };
        return $column ? [$column] : [];
    }

    #[\Override]
    public function get_filter_names(): array {
        return [
            'fullname', 'idnumber', 'id', 'category', 'draft', 'archived', 'creategroups', 'timecreated',
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
            'fullname' => new filter\text($alias, 'fullname', new lang_string('programname', 'tool_muprog'), "$alias.fullname"),
            'idnumber' => new filter\text($alias, 'idnumber', new lang_string('programidnumber', 'tool_muprog'), "$alias.idnumber"),
            'id' => (new filter\number($alias, 'id', new lang_string('programinternalid', 'tool_muprog'), "$alias.id"))
                ->set_multisearch(),
            'category' => (new filter\picker($alias, 'category', new lang_string('category'), "$alias.contextid"))
                ->set_integer_values()
                ->set_labels(fn(array $values): array => self::get_category_labels($values)),
            'draft', 'archived', 'creategroups' => new filter\boolean(
                $alias,
                $selector,
                new lang_string($selector, 'tool_muprog'),
                "$alias.$selector"
            ),
            'timecreated' => new filter\date(
                $alias,
                'timecreated',
                new lang_string('timecreated', 'core_reportbuilder'),
                "$alias.timecreated"
            ),
            default => null,
        };
        return $filter ? [$filter] : [];
    }

    /**
     * Program custom fields visible to managers are not checked in each row,
     * for reports with rows restricted to programs the user may view.
     *
     * @return self
     */
    public function set_customfields_managed(): self {
        $this->customfieldsmanaged = true;
        return $this;
    }

    /**
     * Returns program custom fields.
     *
     * @return customfields
     */
    public function get_customfields(): customfields {
        if ($this->customfields === null) {
            $this->customfields = (new customfields($this, 'tool_muprog', 'program'))
                ->set_unrestricted(fn(field_controller $field): bool => $field->get_configdata_property('visibilityeveryone')
                    || ($this->customfieldsmanaged && $field->get_configdata_property('visibilitymanagers'))
                    || has_capability('tool/muprog:edit', \core\context\system::instance()))
                ->set_cell_visibility(function (field_controller $field, row $row): bool {
                    $context = $row->get_context($this->alias);
                    if (!$context) {
                        return false;
                    }
                    if ($field->get_configdata_property('visibilitymanagers') && has_capability('tool/muprog:view', $context)) {
                        return true;
                    }
                    if (has_capability('tool/muprog:edit', $context)) {
                        return true;
                    }
                    if ($field->get_configdata_property('visibilityallocated')) {
                        return $this->customfields->get_handler()->can_view($field, (int)$row->get_entity_key($this->alias));
                    }
                    return false;
                });
        }
        return $this->customfields;
    }

    #[\Override]
    public function get_helper_join(string $helperalias): sql {
        return $this->get_customfields()->get_helper_join($helperalias) ?? parent::get_helper_join($helperalias);
    }

    /**
     * Define program name column, linked to program management.
     *
     * @return column
     */
    private function define_fullname_column(): column {
        $alias = $this->alias;
        $name = fn(stdClass $fields, bool $escape): string => format_string(
            $fields->fullname,
            true,
            ['context' => \core\context::instance_by_id($fields->contextid), 'escape' => $escape]
        );
        $url = fn(stdClass $fields): url => new url('/admin/tool/muprog/management/program.php', ['id' => $fields->id]);
        return $this->create_column('fullname', new lang_string('programname', 'tool_muprog'), column::TYPE_TEXT)
            ->add_field('fullname', "$alias.fullname")
            ->add_field('id', "$alias.id")
            ->add_field('contextid', "$alias.contextid")
            ->set_sortable(true)
            ->add_formatter(
                'name',
                fn(cell $cell): string => $name($cell->get_fields(), true),
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields(), false))
            )
            ->add_formatter(
                'link',
                fn(cell $cell): string => \html_writer::link($url($cell->get_fields()), $name($cell->get_fields(), true)),
                fn(cell $cell): export_value => export_value::link($url($cell->get_fields()), $name($cell->get_fields(), false))
            )
            ->set_default_formatter('link');
    }

    /**
     * Define program category column, linked to program management of the category.
     *
     * @return column
     */
    private function define_category_column(): column {
        $alias = $this->alias;
        $name = fn(stdClass $fields): string
            => \core\context::instance_by_id($fields->contextid)->get_context_name(false, false, false);
        $url = fn(stdClass $fields): url => new url('/admin/tool/muprog/management/index.php', ['contextid' => $fields->contextid]);
        return $this->create_column('category', new lang_string('category'), column::TYPE_TEXT)
            ->add_field('contextid', "$alias.contextid")
            ->add_formatter(
                'name',
                fn(cell $cell): string => s($name($cell->get_fields())),
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields()))
            )
            ->add_formatter(
                'link',
                function (cell $cell) use ($name, $url): string {
                    $fields = $cell->get_fields();
                    $context = \core\context::instance_by_id($fields->contextid);
                    if (!has_capability('tool/muprog:view', $context)) {
                        return s($name($fields));
                    }
                    return \html_writer::link($url($fields), s($name($fields)));
                },
                fn(cell $cell): export_value => export_value::text($name($cell->get_fields()))
            )
            ->set_default_formatter('link');
    }

    /**
     * Define number of courses column, linked to program content, missing courses are flagged.
     *
     * @return column
     */
    private function define_coursecount_column(): column {
        $alias = $this->alias;
        return $this->create_column('coursecount', new lang_string('courses'), column::TYPE_INTEGER)
            ->add_field('coursecount', "(SELECT COUNT('x')
                                           FROM {tool_muprog_item} ci
                                          WHERE ci.type = 'course' AND ci.programid = $alias.id)")
            ->add_field('missingcount', "(SELECT COUNT(DISTINCT mi.courseid)
                                            FROM {tool_muprog_item} mi
                                       LEFT JOIN {course} mc ON mc.id = mi.courseid
                                           WHERE mi.type = 'course' AND mc.id IS NULL AND mi.programid = $alias.id)")
            ->add_field('id', "$alias.id")
            ->set_sortable(true)
            ->add_formatter('link', function (cell $cell): string {
                $fields = $cell->get_fields();
                $url = new url('/admin/tool/muprog/management/program_content.php', ['id' => $fields->id]);
                $result = \html_writer::link($url, (int)$fields->coursecount);
                if ($fields->missingcount) {
                    $result .= ' <span class="badge bg-danger">'
                        . get_string('errorcoursesmissing', 'tool_muprog', (int)$fields->missingcount) . '</span>';
                }
                return $result;
            }, fn(cell $cell): export_value => export_value::number((int)$cell->get_value()))
            ->set_default_formatter('link');
    }

    /**
     * Define number of allocations column, linked to program allocations.
     *
     * @return column
     */
    private function define_allocationcount_column(): column {
        $alias = $this->alias;
        return $this->create_column('allocationcount', new lang_string('allocations', 'tool_muprog'), column::TYPE_INTEGER)
            ->add_field('allocationcount', "(SELECT COUNT('x')
                                               FROM {tool_muprog_allocation} aa
                                              WHERE aa.programid = $alias.id)")
            ->add_field('id', "$alias.id")
            ->set_sortable(true)
            ->add_formatter('link', function (cell $cell): string {
                $fields = $cell->get_fields();
                $url = new url('/admin/tool/muprog/management/program_allocation.php', ['id' => $fields->id]);
                return \html_writer::link($url, (int)$fields->allocationcount);
            }, fn(cell $cell): export_value => export_value::number((int)$cell->get_value()))
            ->set_default_formatter('link');
    }

    /**
     * Returns names of program contexts, the system context or course categories.
     *
     * @param string[] $contextids
     * @return array context id => plain text name
     */
    public static function get_category_labels(array $contextids): array {
        global $DB;
        if (!$contextids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal(array_map('intval', $contextids), SQL_PARAMS_NAMED, 'ctx');
        $params['catlevel'] = CONTEXT_COURSECAT;
        $sql = "SELECT ctx.id, ctx.contextlevel, cc.name
                  FROM {context} ctx
             LEFT JOIN {course_categories} cc ON cc.id = ctx.instanceid AND ctx.contextlevel = :catlevel
                 WHERE ctx.id $insql";
        $result = [];
        $syscontext = \core\context\system::instance();
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            if ((int)$record->contextlevel === CONTEXT_SYSTEM) {
                $result[$record->id] = $syscontext->get_context_name(false, false, false);
            } else if ($record->name !== null) {
                $result[$record->id] = format_string($record->name, true, ['context' => $syscontext, 'escape' => false]);
            }
        }
        return $result;
    }
}
