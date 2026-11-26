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

namespace tool_muprog\mureport\system_report;

use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity\user;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\join;
use tool_mureport\mureport\system_report;
use tool_muprog\mureport\entity\allocation;
use core\exception\invalid_parameter_exception;
use core\lang_string;
use core\url;

/**
 * Users allocated to a program, for program managers.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocations extends system_report {
    /**
     * Make report instance.
     *
     * @param int $programid
     * @return self
     */
    public static function make_instance(int $programid): self {
        return self::from_constraints(['programid' => $programid]);
    }

    #[\Override]
    public static function get_title(): string {
        return get_string('programallocations', 'tool_muprog');
    }

    #[\Override]
    public function get_context(): \core\context {
        $program = $this->get_constraint_record('tool_muprog_program', 'programid');
        if (!$program) {
            throw new invalid_parameter_exception('Invalid program');
        }
        return \core\context::instance_by_id($program->contextid);
    }

    #[\Override]
    protected function check_access(): void {
        $this->require_capability('tool/muprog:view');
    }

    #[\Override]
    protected function define(): void {
        $this->set_main_entity((new allocation('allocation'))->set_customfields_managed());
        $this->set_row_muted('allocation.archived = 1', ['allocation']);
        $this->add_entity(new user('auser'), new join('allocation', 'auser.id = allocation.userid', inner: true));

        $this->set_default_columns([
            'auser:fullname',
            'auser:identityfields',
            'allocation:timestart',
            'allocation:timedue',
            'allocation:timeend',
            'allocation:source',
            'allocation:archived',
            'allocation:progress',
            'allocation:status',
            'allocation:actions',
        ]);
        $this->set_default_filters([
            'auser:fullname' => ['multisearch' => true],
            'auser:firstname',
            'auser:lastname',
            'auser:email' => ['multisearch' => true],
            'auser:idnumber' => ['multisearch' => true],
            'auser:id' => ['multisearch' => true],
            'auser:suspended',
            'allocation:status',
            'allocation:archived',
            'allocation:source',
            'allocation:timestart',
            'allocation:timedue',
            'allocation:timeend',
            'allocation:timecompleted',
            'allocation:allcustomfields',
            'auser:allprofilefields',
        ]);
        $this->set_preset_filters(['allocation:archived' => ['value' => 0]]);
        $this->set_multisearch_label(new lang_string('searchusers', 'tool_mureport'));
        $this->set_default_sorting(['auser:fullname' => 'asc']);
    }

    #[\Override]
    protected function apply_constraints(): void {
        $this->add_constraint_restriction('programid', 'allocation', 'programid');
    }

    #[\Override]
    public function get_column_names(): array {
        return array_merge(parent::get_column_names(), ['auser:identityfields']);
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        return match ($selector) {
            'auser:fullname' => $this->define_fullname_columns(),
            'auser:identityfields' => $this->define_identity_columns(),
            default => parent::define_columns($selector),
        };
    }

    /**
     * Full name linked to the allocation.
     *
     * @return array id => column
     */
    private function define_fullname_columns(): array {
        $columns = parent::define_columns('auser:fullname');
        $fullname = $columns['auser:fullname'];
        $text = $fullname->get_formatters()['fullname'];
        $url = fn(cell $cell): url => new url(
            '/admin/tool/muprog/management/allocation.php',
            ['id' => $cell->get_fields()->allocationid]
        );
        $fullname
            ->add_alias('allocation')
            ->add_field('allocationid', 'allocation.id')
            ->add_formatter(
                'allocationlink',
                fn(cell $cell): string => \html_writer::link($url($cell), $text->display($cell)),
                fn(cell $cell): export_value => export_value::link($url($cell), fullname(
                    $cell->get_fields(),
                    has_capability('moodle/site:viewfullnames', $this->get_context())
                )),
                new lang_string('formatter_allocationlink', 'tool_muprog')
            )
            ->set_default_formatter('allocationlink');
        return $columns;
    }

    /**
     * User identity fields from site settings.
     *
     * @return array id => column
     */
    private function define_identity_columns(): array {
        if ($this->is_inspectmode()) {
            $title = new lang_string('identityfields', 'tool_mureport');
            return [new column('auser', 'identityfields', $title, column::TYPE_PLACEHOLDER)];
        }
        $columns = [];
        foreach ($this->get_entity('auser')->get_identity_column_ids($this->get_context()) as $id) {
            $columns += parent::define_columns($id);
        }
        return $columns;
    }
}
