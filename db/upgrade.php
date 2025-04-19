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

/**
 * Program enrolment uninstallation.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade programs.
 *
 * @param mixed $oldversion
 * @return true
 */
function xmldb_tool_muprog_upgrade($oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025041504) {
        $table = new xmldb_table('tool_muprog_prg_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $table = new xmldb_table('tool_muprog_usr_snapshot');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2025041504, 'tool', 'muprog');
    }

    return true;
}
