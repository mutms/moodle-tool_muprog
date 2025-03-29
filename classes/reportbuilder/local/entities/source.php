<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\report\{column, filter};
use core_reportbuilder\local\filters\select;

/**
 * Program source entity.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source extends base {

    #[\Override]
    protected function get_default_tables(): array {
        return [
            'tool_muprog_source',
        ];
    }

    #[\Override]
    protected function get_default_entity_title(): lang_string {
        return new lang_string('source', 'tool_muprog');
    }

    #[\Override]
    public function initialise(): base {
        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        // All the filters defined by the entity can also be used as conditions.
        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $sourcealias = $this->get_table_alias('tool_muprog_source');

        $columns[] = (new column(
            'type',
            new lang_string('source', 'tool_muprog'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$sourcealias}.type")
            ->set_is_sortable(false)
            ->set_callback(static function(?string $value, \stdClass $row): string {
                if (!$value) {
                    return '';
                }
                return get_string('source_' . $value, 'tool_muprog');
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('tool_muprog_source');

        $filters = [];

        $filters[] = (new filter(
            select::class,
            'type',
            new lang_string('source', 'tool_muprog'),
            $this->get_entity_name(),
            "{$tablealias}.type"
        ))
            ->add_joins($this->get_joins())
            ->set_options(\tool_muprog\local\allocation::get_source_names());

        return $filters;
    }
}
