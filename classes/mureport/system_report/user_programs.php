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
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\join;
use tool_mureport\mureport\system_report;
use tool_muprog\mureport\entity\allocation;
use tool_muprog\mureport\entity\program;
use core\exception\invalid_parameter_exception;
use core\lang_string;
use core\url;

/**
 * Current programs of a user, allocations that are not archived in programs that are not archived.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_programs extends system_report {
    /**
     * Make report instance.
     *
     * @param int $userid
     * @return self
     */
    public static function make_instance(int $userid): self {
        return self::from_constraints(['userid' => $userid]);
    }

    #[\Override]
    public static function get_title(): string {
        return get_string('userprograms', 'tool_muprog');
    }

    #[\Override]
    public static function is_available(): bool {
        return \tool_mulib\local\mulib::is_muprog_available();
    }

    #[\Override]
    public function get_context(): \core\context {
        $user = $this->get_constraint_record('user', 'userid');
        if (!$user || $user->deleted || isguestuser($user)) {
            throw new invalid_parameter_exception('Invalid user');
        }
        return \core\context\user::instance($user->id);
    }

    #[\Override]
    protected function check_access(): void {
        global $USER;
        if ($this->get_context()->instanceid != $USER->id) {
            $this->require_capability('tool/muprog:viewuserprograms');
        }
    }

    #[\Override]
    protected function define(): void {
        $this->set_main_entity(new allocation('allocation'));
        $this->add_entity(new program('program'), new join('allocation', 'program.id = allocation.programid', inner: true));
        $this->add_restriction('allocation.archived = 0 AND program.archived = 0', ['allocation', 'program']);

        $this->set_default_columns([
            'program:fullname',
            'program:idnumber',
            'allocation:timestart',
            'allocation:timedue',
            'allocation:timeend',
            'allocation:source',
            'allocation:progress',
            'allocation:status',
        ]);
        $this->set_default_filters([
            'program:fullname' => ['multisearch' => true],
            'program:idnumber' => ['multisearch' => true],
            'allocation:status',
            'allocation:timestart',
            'allocation:timedue',
            'allocation:timeend',
        ]);
        $this->remove_columns(['program:allocationcount', 'program:coursecount', 'program:archived', 'allocation:archived']);
        $this->remove_filters(['program:archived', 'allocation:archived']);
        $this->set_multisearch_label(new lang_string('programsearch', 'tool_muprog'));
        $this->set_export_allowed(false);
        $this->set_default_sorting(['program:fullname' => 'asc']);
    }

    #[\Override]
    protected function apply_constraints(): void {
        $this->add_constraint_restriction('userid', 'allocation', 'userid');
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        $columns = parent::define_columns($selector);
        if ($selector === 'program:fullname') {
            $columns['program:fullname']
                ->add_alias('allocation')
                ->add_field('userid', 'allocation.userid')
                ->add_formatter('mylink', function (cell $cell): string {
                    $fields = $cell->get_fields();
                    return \html_writer::link(self::get_program_url($fields), format_string($fields->fullname, true, [
                        'context' => \core\context::instance_by_id($fields->contextid),
                    ]));
                }, fn(cell $cell): export_value => export_value::link(
                    self::get_program_url($cell->get_fields()),
                    format_string($cell->get_fields()->fullname, true, [
                        'context' => \core\context::instance_by_id($cell->get_fields()->contextid),
                        'escape' => false,
                    ])
                ))
                ->set_default_formatter('mylink');
        }
        return $columns;
    }

    /**
     * Returns URL of program details of the allocated user.
     *
     * @param \stdClass $fields
     * @return url
     */
    private static function get_program_url(\stdClass $fields): url {
        return new url('/admin/tool/muprog/my/program.php', ['id' => $fields->id, 'userid' => $fields->userid]);
    }
}
