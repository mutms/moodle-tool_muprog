<?php
// This file is part of Programs for Moodle™.
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

    return true;
}
