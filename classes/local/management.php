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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local;

use tool_muprog\local\content\course;
use tool_muprog\local\content\item;
use tool_muprog\local\content\set;
use tool_muprog\local\content\top;
use moodle_url, stdClass;

/**
 * Programs management helper.
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
     * @return moodle_url|null
     */
    public static function get_management_url(): ?moodle_url {
        if (isguestuser() || !isloggedin()) {
            return null;
        }
        if (has_capability('tool/muprog:view', \context_system::instance())) {
            return new moodle_url('/admin/tool/muprog/management/index.php');
        } else {
            // This is not very fast, but we need to let users somehow access program
            // management if they can do so in course category only.
            $categories = \core_course_category::make_categories_list('tool/muprog:view');
            // NOTE: Add some better logic here looking for categories with programs or remember which one was accessed before.
            if ($categories) {
                foreach ($categories as $cid => $unusedname) {
                    $catcontext = \context_coursecat::instance($cid, IGNORE_MISSING);
                    if ($catcontext) {
                        return new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $catcontext->id]);
                    }
                }
            }
        }
        return null;
    }

    /**
     * Fetch list of programs.
     *
     * @param \context|null $context null means all contexts
     * @param bool $archived
     * @param string $search search string
     * @param int $page
     * @param int $perpage
     * @param string $orderby
     * @return array ['programs' => array, 'totalcount' => int]
     */
    public static function fetch_programs(?\context $context, bool $archived, string $search, int $page, int $perpage, string $orderby = 'fullname ASC'): array {
        global $DB;

        list($select, $params) = self::get_program_search_query($context, $search, '');

        $select .= ' AND archived = :archived';
        $params['archived'] = (int)$archived;

        $programs = $DB->get_records_select('tool_muprog_program', $select, $params, $orderby, '*', $page * $perpage, $perpage);
        $totalcount = $DB->count_records_select('tool_muprog_program', $select, $params);

        return ['programs' => $programs, 'totalcount' => $totalcount];
    }

    /**
     * Fetch list contexts with programs that users may access.
     *
     * @param \context $context current management context, added if no program present yet
     * @return array
     */
    public static function get_used_contexts_menu(\context $context): array {
        global $DB;

        $syscontext = \context_system::instance();

        $result = [];

        if (has_capability('tool/muprog:view', $syscontext)) {
            $allcount = $DB->count_records('tool_muprog_program', []);
            $result[0] = get_string('allprograms', 'tool_muprog') . ' (' . $allcount . ')';

            $syscount = $DB->count_records('tool_muprog_program', ['contextid' => $syscontext->id]);
            $result[$syscontext->id] = $syscontext->get_context_name() . ' (' . $syscount .')';
        }

        $categories = \core_course_category::make_categories_list('tool/muprog:view');
        if (!$categories) {
            return $result;
        }

        $sql = "SELECT cat.id, COUNT(p.id)
                  FROM {course_categories} cat
                  JOIN {context} ctx ON ctx.instanceid = cat.id AND ctx.contextlevel = 40
                  JOIN {tool_muprog_program} p ON p.contextid = ctx.id
              GROUP BY cat.id
                HAVING COUNT(p.id) > 0";
        $programcounts = $DB->get_records_sql_menu($sql);

        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid, IGNORE_MISSING);
            if (!$catcontext) {
                continue;
            }
            if (!isset($programcounts[$catid])) {
                if ($catcontext->id == $context->id) {
                    $result[$catcontext->id] = $categoryname;
                }
                continue;
            }
            $result[$catcontext->id] = $categoryname . ' (' . $programcounts[$catid] . ')';
        }

        if (!isset($result[$context->id])) {
            $result[$context->id] = $context->get_context_name();
        }

        return $result;
    }

    /**
     * Returns program query data.
     *
     * @param \context|null $context
     * @param string $search
     * @param string $tablealias
     * @return array
     */
    public static function get_program_search_query(?\context $context, string $search, string $tablealias = ''): array {
        global $DB;

        if ($tablealias !== '' && substr($tablealias, -1) !== '.') {
            $tablealias .= '.';
        }

        $conditions = [];
        $params = [];

        if ($context) {
            $contextselect = 'AND ' . $tablealias . 'contextid = :prgcontextid';
            $params['prgcontextid'] = $context->id;
        } else {
            $contextselect = '';
        }

        if (trim($search) !== '') {
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $fields = ['fullname', 'idnumber', 'description'];
            $cnt = 0;
            foreach ($fields as $field) {
                $conditions[] = $DB->sql_like($tablealias . $field, ':prgsearch' . $cnt, false);
                $params['prgsearch' . $cnt] = $searchparam;
                $cnt++;
            }
        }

        if ($conditions) {
            $sql = '(' . implode(' OR ', $conditions) . ') ' . $contextselect;
            return [$sql, $params];
        } else {
            return ['1=1 ' . $contextselect, $params];
        }
    }

    /**
     * Fetch cohorts that allow program visibility.
     *
     * @param int $programid
     * @return array
     */
    public static function fetch_current_cohorts_menu(int $programid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {tool_muprog_cohort} pc ON c.id = pc.cohortid
                 WHERE pc.programid = :programid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['programid' => $programid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @return void
     */
    public static function setup_index_page(\moodle_url $pageurl, \context $context): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('programs', 'tool_muprog'));
        $PAGE->set_heading(get_string('programs', 'tool_muprog'));
        $PAGE->set_secondary_navigation(false);

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/muprog:view', $context)) {
                $url = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
    }

    /**
     * Set up $PAGE for programs management UI.
     *
     * @param moodle_url $pageurl
     * @param \context $context
     * @param stdClass $program
     * @param string $secondarytab
     * @return void
     */
    public static function setup_program_page(moodle_url $pageurl, \context $context, stdClass $program, string $secondarytab): void {
        global $PAGE;

        $PAGE->set_pagelayout('admin');
        $PAGE->set_context($context);
        $PAGE->set_url($pageurl);
        $PAGE->set_title(get_string('programs', 'tool_muprog'));
        $PAGE->set_heading(format_string($program->fullname));

        $secondarynav = new \tool_muprog\navigation\views\program_secondary($PAGE, $program);
        $PAGE->set_secondarynav($secondarynav);
        $PAGE->set_secondary_active_tab($secondarytab);
        $secondarynav->initialise();

        $contexts = [];
        while (true) {
            $contexts[] = $context;
            $parent = $context->get_parent_context();
            if (!$parent) {
                break;
            }
            $context = $parent;
        }

        $contexts = array_reverse($contexts);

        /** @var \context $context */
        foreach ($contexts as $context) {
            $url = null;
            if (has_capability('tool/muprog:view', $context)) {
                $url = new moodle_url('/admin/tool/muprog/management/index.php', ['contextid' => $context->id]);
            }
            $PAGE->navbar->add($context->get_context_name(false), $url);
        }
        $PAGE->navbar->add(format_string($program->fullname));
    }
}
