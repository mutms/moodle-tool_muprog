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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local;

use tool_muprog\local\content\course;
use tool_muprog\local\content\item;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;
use core\url, stdClass;
use tool_mulib\local\sql;
use tool_mulib\local\mulib;

/**
 * Program management helper.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class management {
    /**
     * Guess if user can access programs management UI.
     *
     * @return url|null
     */
    public static function get_management_url(): ?url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }

        // NOTE: this has to be very fast, do NOT loop all categories here!

        if (has_capability('tool/muprog:view', \context_system::instance())) {
            return new url('/admin/tool/muprog/management/index.php');
        } else if (mulib::is_mutenancy_active()) {
            $tenantid = \tool_mutenancy\local\tenancy::get_current_tenantid();
            if ($tenantid) {
                $tenant = \tool_mutenancy\local\tenant::fetch($tenantid);
                if ($tenant) {
                    $catcontext = \context_coursecat::instance($tenant->categoryid);
                    if (has_capability('tool/muprog:view', $catcontext)) {
                        return new url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * Returns program query data.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return sql
     */
    public static function get_program_search_query(?\context $context, string $search, string $tablealias = ''): sql {
        global $DB;

        if ($tablealias !== '' && !str_ends_with($tablealias, '.')) {
            $tablealias .= '.';
        }

        $conditions = [];
        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $fields = ['fullname', 'idnumber', 'description'];
            foreach ($fields as $field) {
                $conditions[] = new sql($DB->sql_like($tablealias . $field, '?', false), [$searchparam]);
            }
        }

        if ($conditions) {
            $sql = sql::join(' OR ', $conditions)->wrap('(', ')');
        } else {
            $sql = new sql('');
        }

        if ($context) {
            $contextselect = new sql($tablealias . 'contextid = ?', [$context->id]);
            $sql = sql::join(' AND ', [$sql, $contextselect]);
        }

        return $sql;
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param url $pageurl
     * @param \context $context
     * @return void
     */
    public static function setup_index_page(url $pageurl, \context $context): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('management', 'tool_muprog'));
        $PAGE->set_heading(get_string('programs', 'tool_muprog'));
        $PAGE->set_secondary_navigation(false);

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('programs', 'tool_muprog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/muprog:view', $parentcontext)) {
                $url = new url('/admin/tool/muprog/management/index.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param url $pageurl
     * @param \context $context
     * @param stdClass $program
     * @param string $secondarytab
     * @return void
     */
    public static function setup_program_page(url $pageurl, \context $context, stdClass $program, string $secondarytab): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);

        $programname = format_string($program->fullname);

        $PAGE->set_title($programname . \moodle_page::TITLE_SEPARATOR . get_string('management', 'tool_muprog'));
        $PAGE->set_heading($programname);

        self::add_program_operation_info($program);

        $secondarynav = new \tool_muprog\navigation\views\program_secondary($PAGE, $program);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $parentcontextids = $context->get_parent_context_ids(true);
        $parentcontextids = array_reverse($parentcontextids);
        foreach ($parentcontextids as $parentcontextid) {
            $parentcontext = \context::instance_by_id($parentcontextid);
            if ($parentcontext instanceof \context_system) {
                $name = get_string('programs', 'tool_muprog');
            } else {
                $name = $parentcontext->get_context_name(false);
            }
            $url = null;
            if (has_capability('tool/muprog:view', $parentcontext)) {
                $url = new url('/admin/tool/muprog/management/index.php', ['contextid' => $parentcontext->id]);
            }
            $PAGE->navbar->add($name, $url);
        }

        $url = new url('/admin/tool/muprog/management/program.php', ['id' => $program->id]);
        $PAGE->navbar->add($programname, $url);
    }

    /**
     * Is the program frozen by an unfinished operation?
     *
     * Program with running operation cannot be modified in any way,
     * program with failed operation can be deleted, or the failure can be dismissed
     * to fix the program manually.
     *
     * @param stdClass $program
     * @param bool $allowfailed true means program with failed operation is not considered to be frozen
     * @return bool
     */
    public static function is_program_frozen(stdClass $program, bool $allowfailed = false): bool {
        // Any pending operation freezes the program, its actual state matters only if failed is allowed.
        $operation = operation\base::get_pending_operation($program->id, $allowfailed);
        if (!$operation) {
            return false;
        }
        if ($allowfailed && $operation->timefailed) {
            return false;
        }
        return true;
    }

    /**
     * Stop if program is frozen by an unfinished operation.
     *
     * @param stdClass $program
     * @param bool $allowfailed true means program with failed operation is not considered to be frozen
     * @return void
     */
    public static function require_program_not_frozen(stdClass $program, bool $allowfailed = false): void {
        if (self::is_program_frozen($program, $allowfailed)) {
            throw new \core\exception\moodle_exception('errorprogramfrozen', 'tool_muprog');
        }
    }

    /**
     * Tell user about unfinished operation on program management pages.
     *
     * @param stdClass $program
     * @return void
     */
    protected static function add_program_operation_info(stdClass $program): void {
        global $DB;

        // The info tells if the operation is still running or if it failed.
        $operation = operation\base::get_pending_operation($program->id, true);
        if (!$operation) {
            return;
        }
        $state = operation\base::decode_state($operation);

        $a = new stdClass();
        $a->started = userdate($operation->timestarted);
        $a->error = s($state['error'] ?? '');
        $a->done = 0;
        $a->total = 0;
        $courses = [];
        foreach (($state['courses'] ?? []) as $course) {
            $a->total++;
            if ($course['status'] === 'done') {
                $a->done++;
            }
            if (empty($course['newid'])) {
                continue;
            }
            $record = $DB->get_record('course', ['id' => $course['newid']], 'id, fullname');
            if (!$record) {
                continue;
            }
            $url = new url('/course/view.php', ['id' => $record->id]);
            $courses[] = \core\output\html_writer::link($url, format_string($record->fullname));
        }

        if ($operation->timefailed) {
            $message = get_string('operation_failed', 'tool_muprog', $a);
            $type = \core\notification::ERROR;
        } else {
            $message = get_string('operation_running', 'tool_muprog', $a);
            $type = \core\notification::WARNING;
        }
        if ($courses) {
            $message .= ' ' . get_string('operation_courses', 'tool_muprog', implode(', ', $courses));
        }
        \core\notification::add($message, $type);
    }
}
