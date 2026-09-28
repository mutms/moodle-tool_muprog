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

namespace tool_muprog\muform\autocompletemany;

use tool_mulib\local\search_util;
use tool_mulib\local\context_map;
use tool_mulib\local\mulib;
use tool_mulib\local\sql;

/**
 * Courses that may be added to a program.
 *
 * @package     tool_muprog
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_create_course_courseids extends \tool_mulib\muform\autocompletemany\base {
    /** @var string capability required in the course context */
    private const string CAPABILITY = 'tool/muprog:addcourse';

    /** @var \context program context */
    private \context $context;

    /**
     * Constructor.
     *
     * @param int $programid
     */
    public function __construct(
        /** @var int program id */
        private readonly int $programid
    ) {
        global $DB;
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($program->contextid);
        require_capability('tool/muprog:edit', $this->context);
    }

    #[\Override]
    public function get_args(): array {
        return [$this->programid];
    }

    #[\Override]
    public function search(string $query, int $maxitems, array $exclude): ?array {
        global $DB, $USER;

        $capjoin = context_map::get_contexts_by_capability_join(self::CAPABILITY, $USER->id, 'ctx');

        $sql = (
            new sql(
                "SELECT c.id, c.fullname
                   FROM {course} c
                   JOIN {context} ctx ON ctx.contextlevel = :courselevel AND ctx.instanceid = c.id
                   /* capjoin */
              LEFT JOIN {tool_muprog_item} pi ON pi.programid = :programid AND pi.courseid = c.id
                  WHERE c.category <> 0 AND pi.id IS NULL
                        /* capwhere */ /* searchsql */ /* tenantwhere */ /* exclude */
               GROUP BY c.id, c.fullname
               ORDER BY c.fullname ASC, c.id ASC",
                ['courselevel' => CONTEXT_COURSE, 'programid' => $this->programid]
            )
        )
            ->replace_comment('capjoin', $capjoin['join'])
            ->replace_comment('capwhere', $capjoin['where']->wrap("AND ", ""))
            ->replace_comment(
                'searchsql',
                search_util::get_search_query(trim($query), ['fullname', 'shortname', 'idnumber'], 'c')->wrap("AND ", "")
            );

        if (mulib::is_mutenancy_active() && $this->context->tenantid) {
            $sql = $sql->replace_comment(
                'tenantwhere',
                new sql("AND (ctx.tenantid = ? OR ctx.tenantid IS NULL)", [$this->context->tenantid])
            );
        }

        $exclude = array_values(array_filter($exclude, fn($id) => is_string($id) && preg_match('/^\d+$/D', $id)));
        if ($exclude) {
            [$notin, $params] = $DB->get_in_or_equal($exclude, SQL_PARAMS_NAMED, 'cex', false);
            $sql = $sql->replace_comment('exclude', new sql("AND c.id $notin", $params));
        }

        $courses = $DB->get_records_sql($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($courses) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($courses as $course) {
            $coursecontext = \context_course::instance($course->id);
            $result[(string)$course->id] = clean_text(format_string($course->fullname, true, ['context' => $coursecontext]));
        }
        return $result;
    }

    #[\Override]
    public function labels(array $values): array {
        global $DB;

        $result = [];
        foreach ($this->get_courses($values) as $id => $course) {
            if ($DB->record_exists('tool_muprog_item', ['programid' => $this->programid, 'courseid' => $course->id])) {
                continue;
            }
            $coursecontext = \context_course::instance($course->id);
            if (!has_capability(self::CAPABILITY, $coursecontext)) {
                continue;
            }
            $result[$id] = clean_text(format_string($course->fullname, true, ['context' => $coursecontext]));
        }
        return $result;
    }

    #[\Override]
    public function validate(array $values): array {
        $result = [];
        if (!mulib::is_mutenancy_active() || !$this->context->tenantid) {
            return $result;
        }
        foreach ($this->get_courses($values) as $id => $course) {
            $coursecontext = \context_course::instance($course->id);
            if ($coursecontext->tenantid && $coursecontext->tenantid != $this->context->tenantid) {
                $result[$id] = get_string('errordifferenttenant', 'tool_muprog');
            }
        }
        return $result;
    }

    /**
     * Fetch courses in categories.
     *
     * @param string[] $ids
     * @return \stdClass[] indexed by string id
     */
    private function get_courses(array $ids): array {
        global $DB;
        $ids = array_values(array_filter($ids, fn($id) => is_string($id) && preg_match('/^[1-9][0-9]*$/D', $id)));
        if (!$ids) {
            return [];
        }
        $result = [];
        foreach ($DB->get_records_list('course', 'id', $ids, 'id ASC', 'id, category, fullname') as $course) {
            if (!$course->category) {
                continue;
            }
            $result[(string)$course->id] = $course;
        }
        return $result;
    }
}
