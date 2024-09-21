<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace enrol_programs\hook\extra_menu;

/**
 * Extra menu in user allocation tab for program.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('openlms')]
#[\core\attribute\label('Additions to "Users actions" extra menu in Users tab in program.')]
final class management_program_users extends \local_openlms\hook\extra_menu {
    /** @var \stdClass program record */
    protected $program;

    /**
     * Create hook for extra menu.
     *
     * @param \stdClass $program
     */
    public function __construct(\stdClass $program) {
        $dropdown = new \local_openlms\output\extra_menu\dropdown(
            get_string('extra_menu_management_program_users', 'enrol_programs'));
        parent::__construct($dropdown);
        $this->program = $program;
    }

    /**
     * Returns program of user allocation page.
     *
     * @return \stdClass program record
     */
    public function get_program(): \stdClass {
        return $this->program;
    }
}
