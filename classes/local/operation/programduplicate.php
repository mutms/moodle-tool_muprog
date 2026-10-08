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
// phpcs:disable moodle.Files.LineLength.MaxExceeded

namespace tool_muprog\local\operation;

use stdClass;
use tool_mulib\local\mulib;
use tool_muprog\local\allocation;
use tool_muprog\local\program;

/**
 * Duplicate program including fresh copies of all its courses.
 *
 * The result is a new draft program, courses are copied without any user data.
 * This is intended to be executed from CLI only, the copying of courses may take hours.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class programduplicate extends base {
    /** @var string new courses are created in categories of original courses */
    public const COURSECATEGORY_SAME = 'same';
    /** @var string new courses are created in category of the new program */
    public const COURSECATEGORY_PROGRAM = 'program';

    /** @var stdClass program that is duplicated */
    private stdClass $sourceprogram;
    /** @var string text to be replaced in names and idnumbers, may be empty */
    private string $search;
    /** @var string replacement text, it is appended if search text is not present */
    private string $replace;
    /** @var \context context of the new program */
    private \context $programcontext;
    /** @var string|int one of COURSECATEGORY_ constants or course category id */
    private string|int $coursecategory;
    /** @var int[] ids of roles to be kept in new courses */
    private array $keeproleids;
    /** @var string[]|null types of allocation sources to copy, null means all that can be imported */
    private ?array $sourcetypes;
    /** @var bool release the new program when everything is copied */
    private bool $release;
    /** @var stdClass|null cached plan */
    private ?stdClass $plan = null;
    /** @var string[] problems that did not stop the duplication */
    private array $problems = [];

    /**
     * Constructor.
     *
     * @param stdClass $sourceprogram
     * @param string $search
     * @param string $replace
     * @param \context|null $programcontext null means the same as source program
     * @param string|int $coursecategory
     * @param int[] $keeproleids
     * @param string[]|null $sourcetypes types of allocation sources to copy, null means all that can be imported,
     *      empty array means none
     * @param bool $release release the new program at the end if there were no problems, it is not reviewed then
     */
    public function __construct(
        stdClass $sourceprogram,
        string $search,
        string $replace,
        ?\context $programcontext = null,
        string|int $coursecategory = self::COURSECATEGORY_SAME,
        array $keeproleids = [],
        ?array $sourcetypes = null,
        bool $release = false
    ) {
        $this->sourceprogram = $sourceprogram;
        $this->search = $search;
        $this->replace = $replace;
        $this->programcontext = $programcontext ?? \context::instance_by_id($sourceprogram->contextid);
        $this->coursecategory = $coursecategory;
        $this->keeproleids = array_values(array_map('intval', $keeproleids));
        $this->sourcetypes = ($sourcetypes === null) ? null : array_values(array_unique($sourcetypes));
        $this->release = $release;
    }

    /**
     * Create new name or idnumber.
     *
     * Only the first occurrence of the search text is replaced.
     *
     * @param string $value original value
     * @param string $search text to be replaced, may be empty
     * @param string $replace replacement
     * @param string $separator used when appending the replacement because search text is not present
     * @return string
     */
    public static function rename(string $value, string $search, string $replace, string $separator): string {
        if ($search !== '') {
            $position = \core_text::strpos($value, $search);
            if ($position !== false) {
                return \core_text::substr($value, 0, $position)
                    . $replace
                    . \core_text::substr($value, $position + \core_text::strlen($search));
            }
        }
        return $value . $separator . $replace;
    }

    /**
     * Returns what is going to be created together with errors that prevent the duplication.
     *
     * @return stdClass with properties fullname, idnumber, contextid, courses, sources, release, warnings and errors
     */
    public function get_plan(): stdClass {
        global $DB;

        if ($this->plan) {
            return $this->plan;
        }

        $plan = new stdClass();
        $plan->fullname = $this->sourceprogram->fullname;
        $plan->idnumber = $this->sourceprogram->idnumber;
        $plan->contextid = (int)$this->programcontext->id;
        $plan->courses = [];
        $plan->sources = [];
        $plan->uniquefields = [];
        $plan->release = false;
        $plan->warnings = [];
        $plan->errors = [];

        if (!mb_check_encoding($this->search, 'UTF-8') || !mb_check_encoding($this->replace, 'UTF-8')) {
            // Nothing can be planned, invalid texts must not get into new names or database queries.
            $plan->errors[] = 'Search and replacement texts must be valid UTF-8.';
            $this->plan = $plan;
            return $plan;
        }

        $plan->fullname = self::rename($this->sourceprogram->fullname, $this->search, $this->replace, ' ');
        $plan->idnumber = self::rename($this->sourceprogram->idnumber, $this->search, $this->replace, '-');

        if (trim($this->replace) === '') {
            $plan->errors[] = 'Replacement text is required.';
        }

        if (!($this->programcontext instanceof \context_system) && !($this->programcontext instanceof \context_coursecat)) {
            $plan->errors[] = 'New program must be in system or course category context.';
        }
        if (\core_text::strlen($plan->fullname) > 254) {
            $plan->errors[] = "New program name is too long: {$plan->fullname}";
        }
        if (\core_text::strlen($plan->idnumber) > 254) {
            $plan->errors[] = "New program idnumber is too long: {$plan->idnumber}";
        }
        if ($DB->record_exists_select('tool_muprog_program', "LOWER(idnumber) = LOWER(?)", [$plan->idnumber])) {
            $plan->errors[] = "Program idnumber is already used: {$plan->idnumber}";
        }

        $fixedcategoryid = null;
        if ($this->coursecategory === self::COURSECATEGORY_PROGRAM) {
            if ($this->programcontext instanceof \context_coursecat) {
                $fixedcategoryid = (int)$this->programcontext->instanceid;
            } else {
                $plan->errors[] = 'New courses cannot be created in program category, the new program is not in a course category.';
            }
        } else if ($this->coursecategory !== self::COURSECATEGORY_SAME) {
            $fixedcategoryid = (int)$this->coursecategory;
            if (!$DB->record_exists('course_categories', ['id' => $fixedcategoryid])) {
                $plan->errors[] = "Course category does not exist: {$this->coursecategory}";
                $fixedcategoryid = null;
            }
        }

        foreach ($this->keeproleids as $roleid) {
            if (!$DB->record_exists('role', ['id' => $roleid])) {
                $plan->errors[] = "Role does not exist: {$roleid}";
            }
        }

        // Allocation sources are copied the same way as when importing allocation settings,
        // each source decides if its settings can be imported into the new program.
        /** @var class-string<\tool_muprog\local\source\base>[] $sourceclasses */
        $sourceclasses = allocation::get_source_classes();
        $existing = $DB->get_fieldset_select('tool_muprog_source', 'type', "programid = ?", [$this->sourceprogram->id]);
        $newprogram = clone($this->sourceprogram);
        $newprogram->id = 0;
        $newprogram->contextid = $plan->contextid;
        $importable = [];
        foreach ($existing as $type) {
            if (isset($sourceclasses[$type]) && $sourceclasses[$type]::is_import_allowed($this->sourceprogram, $newprogram)) {
                $importable[] = $type;
            }
        }
        sort($importable);
        if ($this->sourcetypes === null) {
            $plan->sources = $importable;
        } else {
            $plan->sources = [];
            foreach ($this->sourcetypes as $type) {
                if (!isset($sourceclasses[$type])) {
                    $plan->errors[] = "Allocation source does not exist: {$type}";
                } else if (!in_array($type, $existing)) {
                    $plan->errors[] = "Allocation source is not enabled in the original program: {$type}";
                } else if (!in_array($type, $importable)) {
                    $plan->errors[] = "Allocation source cannot be imported: {$type}";
                } else {
                    $plan->sources[] = $type;
                }
            }
            sort($plan->sources);
        }

        // Values of custom fields that must be unique cannot be copied, somebody has to set them manually.
        $plan->uniquefields = self::get_unique_customfields($this->sourceprogram);
        foreach ($plan->uniquefields as $name) {
            $plan->warnings[] = "Custom field '{$name}' must be unique, its value is not copied.";
        }

        $plan->release = $this->release;
        if ($this->release && $plan->uniquefields) {
            $plan->release = false;
            $plan->warnings[] = 'The new program is NOT going to be released, it stays draft until values of unique custom fields are set.';
        } else if ($this->release) {
            $plan->warnings[] = 'The new program is going to be released without any review, make sure that the copied allocation sources, dates and courses do not need any changes.';
        }

        $sql = "SELECT DISTINCT pi.courseid
                  FROM {tool_muprog_item} pi
                 WHERE pi.programid = :programid AND pi.courseid IS NOT NULL
              ORDER BY pi.courseid ASC";
        $courseids = $DB->get_fieldset_sql($sql, ['programid' => $this->sourceprogram->id]);

        $shortnames = [];
        $idnumbers = [];
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid], 'id, category, fullname, shortname, idnumber');
            if (!$course) {
                $plan->warnings[] = "Course with id {$courseid} does not exist any more, it is skipped.";
                continue;
            }

            $new = new stdClass();
            $new->sourceid = (int)$course->id;
            $new->sourcefullname = $course->fullname;
            $new->sourceshortname = $course->shortname;
            $new->fullname = self::rename($course->fullname, $this->search, $this->replace, ' ');
            $new->shortname = self::rename($course->shortname, $this->search, $this->replace, '-');
            $new->idnumber = '';
            if ($course->idnumber !== null && $course->idnumber !== '') {
                $new->idnumber = self::rename($course->idnumber, $this->search, $this->replace, '-');
            }
            $new->categoryid = $fixedcategoryid ?? (int)$course->category;
            $plan->courses[$new->sourceid] = $new;

            if (\core_text::strlen($new->fullname) > 1333) {
                $plan->errors[] = "New course full name is too long: {$new->fullname}";
            }
            if (\core_text::strlen($new->shortname) > 255) {
                $plan->errors[] = "New course short name is too long: {$new->shortname}";
            }
            if (\core_text::strlen($new->idnumber) > 100) {
                $plan->errors[] = "New course idnumber is too long: {$new->idnumber}";
            }

            $key = \core_text::strtolower($new->shortname);
            if (isset($shortnames[$key]) || $DB->record_exists('course', ['shortname' => $new->shortname])) {
                $plan->errors[] = "Course short name is already used: {$new->shortname}";
            }
            $shortnames[$key] = true;

            if ($new->idnumber !== '') {
                $key = \core_text::strtolower($new->idnumber);
                if (isset($idnumbers[$key]) || $DB->record_exists('course', ['idnumber' => $new->idnumber])) {
                    $plan->errors[] = "Course idnumber is already used: {$new->idnumber}";
                }
                $idnumbers[$key] = true;
            }
        }

        if (mulib::is_mutenancy_active()) {
            $sourcecontext = \context::instance_by_id($this->sourceprogram->contextid);
            $tenantid = $sourcecontext->tenantid;
            if ($this->programcontext->tenantid != $tenantid) {
                $plan->errors[] = 'New program must be in the same tenant as the original program.';
            }
            $categoryids = array_unique(array_map(fn($course) => $course->categoryid, $plan->courses));
            foreach ($categoryids as $categoryid) {
                $catcontext = \context_coursecat::instance($categoryid, IGNORE_MISSING);
                if ($catcontext && $catcontext->tenantid && $catcontext->tenantid != $tenantid) {
                    $plan->errors[] = "Course category {$categoryid} belongs to a different tenant.";
                }
            }
        }

        $this->plan = $plan;
        return $plan;
    }

    /**
     * Returns problems that did not stop the duplication, such as courses that could not be copied.
     *
     * If there are any then the operation failed, the new program is frozen and it was not released.
     *
     * @return string[]
     */
    public function get_problems(): array {
        return $this->problems;
    }

    /**
     * Duplicate the program and its courses.
     *
     * NOTE: this may take a very long time, it must not be used from web pages.
     *
     * A course that cannot be copied does not stop the duplication, the remaining courses
     * are still copied and program content is created without the missing course.
     * All such problems are available via get_problems() at the end, the operation is
     * then marked as failed and the new program is not released.
     *
     * @param callable|null $progress callback for progress reporting with one string parameter
     * @return stdClass the new program
     */
    public function execute(?callable $progress = null): stdClass {
        global $DB;

        if (!CLI_SCRIPT && !PHPUNIT_TEST) {
            throw new \core\exception\coding_exception('Programs can be duplicated from CLI only');
        }
        if ($DB->is_transaction_started()) {
            throw new \core\exception\coding_exception('Programs cannot be duplicated inside a transaction');
        }
        if (!is_siteadmin()) {
            // Course backup and restore silently skips things the user is not allowed to do.
            throw new \core\exception\coding_exception('Programs can be duplicated by site administrators only');
        }

        $plan = $this->get_plan();
        if ($plan->errors) {
            throw new \core\exception\invalid_parameter_exception('Program cannot be duplicated: ' . implode(' ', $plan->errors));
        }
        $progress = $progress ?? function (string $message): void {
        };

        $program = $this->create_program($plan);

        $state = [
            'sourceprogramid' => (int)$this->sourceprogram->id,
            'search' => $this->search,
            'replace' => $this->replace,
            'courses' => [],
            'error' => null,
        ];
        foreach ($plan->courses as $course) {
            $state['courses'][] = [
                'sourceid' => $course->sourceid,
                'newid' => null,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname,
                'status' => 'todo',
            ];
        }
        $this->start($program->id, $state);
        $progress("Draft program '{$program->fullname}' created with id {$program->id}.");

        try {
            $this->copy_settings($program, $plan);
            $progress('Program settings copied.');

            $coursemap = [];
            $count = count($plan->courses);
            $i = 0;
            foreach ($plan->courses as $course) {
                $i++;
                $started = time();
                $progress("Course {$i} of {$count}: copying '{$course->sourcefullname}' to '{$course->fullname}' ...");

                $state['courses'][$i - 1]['status'] = 'running';
                $this->save_state($state);

                try {
                    $newcourseid = $this->duplicate_course($course, function (int $newcourseid) use (&$state, $i): void {
                        // Remember the new course as soon as it exists, it must be listed even if the copying fails.
                        $state['courses'][$i - 1]['newid'] = $newcourseid;
                        $this->save_state($state);
                    });
                } catch (\Throwable $ex) {
                    // Keep going, the other courses can still be copied.
                    $error = self::describe_exception($ex);
                    $state['courses'][$i - 1]['status'] = 'failed';
                    $state['courses'][$i - 1]['error'] = $error;
                    $this->save_state($state);
                    $this->problems[] = "Course '{$course->sourcefullname}' was not copied: {$error}";
                    $progress("Course {$i} of {$count}: FAILED, {$error}");
                    continue;
                }
                $coursemap[$course->sourceid] = $newcourseid;

                $state['courses'][$i - 1]['status'] = 'done';
                $this->save_state($state);

                $duration = format_time(max(1, time() - $started));
                $progress("Course {$i} of {$count}: done, new course id {$newcourseid} ({$duration}).");
            }

            $top = program::load_content($program->id);
            $top->content_import((object)['id' => $program->id, 'fromprogram' => $this->sourceprogram->id], $coursemap);
            $progress('Program content created.');
        } catch (\Throwable $ex) {
            $this->fail(self::describe_exception($ex));
            throw $ex;
        }

        if ($this->problems) {
            // Everything that could be done is done, somebody has to fix the rest manually.
            $this->fail(implode(' ', $this->problems));
            if ($plan->release) {
                $progress('Program is NOT released because there were problems.');
            }
            return $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);
        }

        $this->resolve();

        if ($plan->release) {
            program::release($program->id);
            $progress('Program released.');
        }

        return $DB->get_record('tool_muprog_program', ['id' => $program->id], '*', MUST_EXIST);
    }

    /**
     * Returns list of things an administrator should review before releasing a duplicated program.
     *
     * All settings are copied as they are, some of them most likely need to be changed
     * for the new program: allocation sources would allocate the same users, fixed dates are in the past, etc.
     *
     * NOTE: this is intended for CLI output, the notes are not localised.
     *
     * @param stdClass $program duplicated program, or the original to see what would be copied
     * @param stdClass|null $original the original program if $program is its duplicate
     * @param string[]|null $copiedtypes types of sources that are going to be copied, used only when
     *      $program is the original, see sources in get_plan()
     * @return string[]
     */
    public static function get_review_notes(stdClass $program, ?stdClass $original = null, ?array $copiedtypes = null): array {
        global $DB;

        /** @var class-string<\tool_muprog\local\source\base>[] $sourceclasses */
        $sourceclasses = allocation::get_source_classes();
        $notcopied = function (string $type) use ($sourceclasses): string {
            $name = isset($sourceclasses[$type]) ? $sourceclasses[$type]::get_name() : $type;
            return "Allocation, {$name}: NOT COPIED. Enable and set it up in the new program if it is needed.";
        };

        $notes = [];
        $now = time();
        $date = function (int $timestamp) use ($now): string {
            return userdate($timestamp, get_string('strftimedatetimeshort', 'langconfig')) . ($timestamp < $now ? ' - IN THE PAST' : '');
        };

        // Allocation sources.
        $sources = $DB->get_records('tool_muprog_source', ['programid' => $program->id], 'type ASC', 'type, id, datajson');
        if (!$sources || ($copiedtypes !== null && !$copiedtypes && !$original)) {
            $notes[] = 'Allocation: no allocation source is enabled in the new program, nobody can be allocated.';
        }
        foreach ($sources as $source) {
            if (!$original && $copiedtypes !== null && !in_array($source->type, $copiedtypes)) {
                // This is the original and the source is not going to be copied.
                $notes[] = $notcopied($source->type);
                continue;
            }
            $data = (object)json_decode((string)$source->datajson);
            switch ($source->type) {
                case 'manual':
                    $notes[] = 'Allocation, manual: enabled.';
                    break;
                case 'selfallocation':
                    $details = [];
                    $details[] = (!isset($data->allowsignup) || $data->allowsignup) ? 'sign up is allowed' : 'sign up is not allowed';
                    if (isset($data->key) && $data->key !== '') {
                        $details[] = 'the same sign up key is used';
                    }
                    if (isset($data->maxusers) && $data->maxusers !== '') {
                        $details[] = "limit {$data->maxusers} users";
                    }
                    $notes[] = 'Allocation, self allocation: ' . implode(', ', $details) . '.';
                    break;
                case 'approval':
                    $allowed = (!isset($data->allowrequest) || $data->allowrequest) ? 'requests are allowed' : 'requests are not allowed';
                    $notes[] = "Allocation, requests with approval: {$allowed}.";
                    break;
                case 'cohort':
                    $sql = "SELECT c.name
                              FROM {tool_muprog_src_cohort} sc
                              JOIN {cohort} c ON c.id = sc.cohortid
                             WHERE sc.sourceid = ?
                          ORDER BY c.name ASC";
                    $cohorts = $DB->get_fieldset_sql($sql, [$source->id]);
                    $cohorts = $cohorts ? implode(', ', $cohorts) : 'no cohorts selected';
                    $notes[] = "Allocation, cohorts: {$cohorts} - THE SAME cohort members are allocated automatically after release.";
                    break;
                default:
                    $notes[] = "Allocation, {$source->type}: enabled.";
            }
        }
        if ($original) {
            $types = $DB->get_fieldset_select('tool_muprog_source', 'type', "programid = ?", [$original->id]);
            sort($types);
            foreach ($types as $type) {
                if (!isset($sources[$type])) {
                    $notes[] = $notcopied($type);
                }
            }
        }

        // Dates.
        if ($program->timeallocationstart) {
            $notes[] = 'Allocation start: ' . $date((int)$program->timeallocationstart) . '.';
        }
        if ($program->timeallocationend) {
            $notes[] = 'Allocation end: ' . $date((int)$program->timeallocationend) . '.';
        }
        foreach (['start' => 'startdatejson', 'due' => 'duedatejson', 'end' => 'enddatejson'] as $name => $field) {
            $setting = (array)json_decode((string)$program->{$field}, true);
            if (($setting['type'] ?? '') === 'date' && !empty($setting['date'])) {
                $notes[] = "Program {$name} date is fixed: " . $date((int)$setting['date']) . '.';
            }
        }

        // Other copied settings.
        $notifications = $DB->count_records('tool_mulib_notification', [
            'component' => 'tool_muprog', 'instanceid' => $program->id, 'enabled' => 1,
        ]);
        if ($notifications) {
            $notes[] = "Notifications: {$notifications} enabled, including any custom texts of the original.";
        }
        if ($DB->record_exists('tool_muprog_cert', ['programid' => $program->id])) {
            $notes[] = 'Certificate: the same certificate template and expiration as the original.';
        }

        // Things that are never copied.
        foreach (self::get_unique_customfields($original ?? $program) as $name) {
            $notes[] = "Custom field '{$name}': NOT COPIED, the value must be unique. Set it in the new program.";
        }
        $notes[] = 'Catalogue: the new program is not in any catalogue section, add it after release if needed.';
        $notes[] = 'Certifications: no certification uses the new program.';
        if ($DB->record_exists_select('tool_muprog_item', "programid = ? AND courseid IS NOT NULL", [$program->id])) {
            $notes[] = 'Courses: copied without any user data. Only manual enrolment method is copied, check course dates, visibility and teachers.';
        }

        return $notes;
    }

    /**
     * Returns custom fields of program that must have unique values and that are not empty.
     *
     * @param stdClass $program
     * @return string[] names of fields indexed with field shortname
     */
    private static function get_unique_customfields(stdClass $program): array {
        $result = [];
        $handler = \tool_muprog\customfield\program_handler::create();
        foreach ($handler->get_instance_data($program->id, true) as $data) {
            $field = $data->get_field();
            if ($field->get_configdata_property('uniquevalues') != 1) {
                continue;
            }
            $value = $data->get_value();
            if ($value === null || $value === '') {
                continue;
            }
            $result[$field->get('shortname')] = $field->get_formatted_name(false);
        }
        return $result;
    }

    /**
     * Returns error message for exception.
     *
     * @param \Throwable $ex
     * @return string
     */
    private static function describe_exception(\Throwable $ex): string {
        $error = $ex->getMessage();
        if ($ex instanceof \core\exception\moodle_exception && $ex->debuginfo) {
            $error .= ' (' . $ex->debuginfo . ')';
        }
        return $error;
    }

    /**
     * Create new draft program with general settings of the original.
     *
     * @param stdClass $plan
     * @return stdClass program record
     */
    private function create_program(stdClass $plan): stdClass {
        global $CFG;

        $source = $this->sourceprogram;

        $data = new stdClass();
        $data->contextid = $plan->contextid;
        $data->fullname = $plan->fullname;
        $data->idnumber = $plan->idnumber;
        $data->description = $source->description;
        $data->descriptionformat = $source->descriptionformat;
        $data->draft = 1;
        $data->archived = 0;
        $data->creategroups = $source->creategroups;
        $data->timeallocationstart = $source->timeallocationstart;
        $data->timeallocationend = $source->timeallocationend;
        $data->startdate = (array)json_decode($source->startdatejson, true);
        $data->duedate = (array)json_decode($source->duedatejson, true);
        $data->enddate = (array)json_decode($source->enddatejson, true);

        if ($CFG->usetags) {
            $data->tags = array_values(\core_tag_tag::get_item_tags_array(
                'tool_muprog',
                'tool_muprog_program',
                $source->id,
                \core_tag_tag::BOTH_STANDARD_AND_NOT,
                0,
                false
            ));
        }

        // Use the same data format as program forms to copy custom fields,
        // fields that must be unique are not copied.
        $handler = \tool_muprog\customfield\program_handler::create();
        $instance = new stdClass();
        $instance->id = $source->id;
        $handler->instance_form_before_set_data($instance);
        foreach ($handler->get_fields() as $field) {
            if ($field->get_configdata_property('uniquevalues') == 1) {
                continue;
            }
            $prefix = 'customfield_' . $field->get('shortname');
            foreach ((array)$instance as $name => $value) {
                // Some fields use more properties, such as text areas with editor.
                if ($name === $prefix || str_starts_with($name, $prefix . '_')) {
                    $data->{$name} = $value;
                }
            }
        }

        return program::create($data);
    }

    /**
     * Copy files and settings that are not part of program creation.
     *
     * @param stdClass $program the new program
     * @param stdClass $plan
     * @return void
     */
    private function copy_settings(stdClass $program, stdClass $plan): void {
        global $DB;

        $source = $this->sourceprogram;
        $syscontext = \context_system::instance();

        $fs = get_file_storage();
        foreach (['description', 'image'] as $filearea) {
            $files = $fs->get_area_files($syscontext->id, 'tool_muprog', $filearea, $source->id, 'id ASC', false);
            foreach ($files as $file) {
                $fs->create_file_from_storedfile(['itemid' => $program->id], $file);
            }
        }
        $DB->set_field('tool_muprog_program', 'presentationjson', $source->presentationjson, ['id' => $program->id]);

        /** @var class-string<\tool_muprog\local\source\base>[] $sourceclasses */
        $sourceclasses = allocation::get_source_classes();
        foreach ($plan->sources as $type) {
            // Each source decides if and what can be copied, the same as when importing allocation settings.
            if (!$sourceclasses[$type]::is_import_allowed($source, $program)) {
                throw new \core\exception\coding_exception('Cannot import source ' . $type);
            }
            $sourceclasses[$type]::import_source_data($source->id, $program->id);
        }

        $notificationids = $DB->get_fieldset_select(
            'tool_mulib_notification',
            'id',
            "component = 'tool_muprog' AND instanceid = ?",
            [$source->id]
        );
        if ($notificationids) {
            \tool_mulib\local\notification\util::notification_import(
                (object)['component' => 'tool_muprog', 'instanceid' => $program->id, 'frominstance' => $source->id],
                $notificationids
            );
        }

        $cert = $DB->get_record('tool_muprog_cert', ['programid' => $source->id]);
        if ($cert) {
            unset($cert->id);
            $cert->programid = $program->id;
            $DB->insert_record('tool_muprog_cert', $cert);
        }
    }

    /**
     * Create new course as a copy of existing course without any user data.
     *
     * @param stdClass $course planned course, see get_plan()
     * @param callable $created called with id of the new course right after it is created
     * @return int id of the new course
     */
    private function duplicate_course(stdClass $course, callable $created): int {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $sourcecontext = \context_course::instance($course->sourceid);

        // Developer debugging makes backup and restore log every step, keep errors only.
        $loglevels = [];
        foreach (['error_log', 'output_indented', 'file', 'database'] as $logger) {
            $name = "backup_{$logger}_logger_level";
            $loglevels[$name] = $CFG->{$name} ?? null;
            $CFG->{$name} = \backup::LOG_ERROR;
        }

        try {
            return $this->backup_and_restore_course($course, $created, $sourcecontext);
        } finally {
            foreach ($loglevels as $name => $value) {
                if ($value === null) {
                    unset($CFG->{$name});
                } else {
                    $CFG->{$name} = $value;
                }
            }
        }
    }

    /**
     * Backup course and restore it as a new course.
     *
     * @param stdClass $course planned course, see get_plan()
     * @param callable $created called with id of the new course right after it is created
     * @param \context_course $sourcecontext
     * @return int id of the new course
     */
    private function backup_and_restore_course(stdClass $course, callable $created, \context_course $sourcecontext): int {
        global $CFG, $DB, $USER;

        // The current user is site administrator, see execute().
        $userid = $USER->id;

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->sourceid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_SAMESITE,
            $userid
        );
        // No user data ever, the same site mode includes it by default.
        foreach (['users', 'role_assignments', 'comments', 'userscompletion', 'logs', 'grade_histories', 'xapistate'] as $name) {
            if ($bc->get_plan()->setting_exists($name)) {
                $setting = $bc->get_plan()->get_setting($name);
                if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                    $setting->set_value(0);
                }
            }
        }
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $backupfile = null;

        // The backup directory is modified and then used for restore, it is deleted at the end here.
        $keeptempdirectories = $CFG->keeptempdirectoriesonbackup ?? null;
        $CFG->keeptempdirectoriesonbackup = true;

        try {
            $bc->execute_plan();
            $results = $bc->get_results();
            $backupfile = $results['backup_destination'] ?? null;
            $bc->destroy();

            $this->strip_backup($backupbasepath, $course->sourceid);

            $newcourseid = (int)\restore_dbops::create_new_course($course->fullname, $course->shortname, $course->categoryid);
            $created($newcourseid);

            $rc = new \restore_controller(
                $backupid,
                $newcourseid,
                \backup::INTERACTIVE_NO,
                \backup::MODE_SAMESITE,
                $userid,
                \backup::TARGET_NEW_COURSE
            );
            $settings = [
                'course_fullname' => $course->fullname,
                'course_shortname' => $course->shortname,
                // Enrolment methods are restored as they are, only manual one is left in the backup.
                'enrolments' => \backup::ENROL_ALWAYS,
            ];
            foreach ($settings as $name => $value) {
                if ($rc->get_plan()->setting_exists($name)) {
                    $setting = $rc->get_plan()->get_setting($name);
                    if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                        $setting->set_value($value);
                    }
                }
            }
            if (!$rc->execute_precheck()) {
                $precheck = $rc->get_precheck_results();
                if (is_array($precheck) && !empty($precheck['errors'])) {
                    $rc->destroy();
                    throw new \core\exception\moodle_exception(
                        'error',
                        '',
                        '',
                        null,
                        'Course restore precheck failed: ' . implode(' ', $precheck['errors'])
                    );
                }
            }
            $rc->execute_plan();
            $rc->destroy();
        } finally {
            $CFG->keeptempdirectoriesonbackup = $keeptempdirectories;
            fulldelete($backupbasepath);
            if ($backupfile) {
                $backupfile->delete();
            }
        }

        // Names are forced because they are part of the plan, the restore may alter them.
        $record = new stdClass();
        $record->id = $newcourseid;
        $record->fullname = $course->fullname;
        $record->shortname = $course->shortname;
        $record->idnumber = $course->idnumber;
        $DB->update_record('course', $record);

        $this->keep_roles($sourcecontext, $newcourseid);

        rebuild_course_cache($newcourseid, true);

        return $newcourseid;
    }

    /**
     * Remove data that must not be copied from extracted course backup.
     *
     * - Questions from question banks outside of the course: without them the restored
     *   activities keep using the original shared questions, with them the restore
     *   creates copies of all used questions and then tries to delete them again.
     * - Course groups created for programs: new group is created when the new program is released.
     * - Enrolment methods other than manual: the new course must not start enrolling users on its own,
     *   for example via cohort sync or self enrolment, program enrolment is added when the new program is released.
     *
     * @param string $backupbasepath directory with extracted backup
     * @param int $courseid the original course
     * @return void
     */
    private function strip_backup(string $backupbasepath, int $courseid): void {
        global $DB;

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                 WHERE ctx.contextlevel = :modlevel AND cm.course = :courseid";
        $contextids = $DB->get_fieldset_sql($sql, ['modlevel' => CONTEXT_MODULE, 'courseid' => $courseid]);
        $contextids[] = \context_course::instance($courseid)->id;
        $contextids = array_flip($contextids);

        self::strip_xml(
            $backupbasepath . '/questions.xml',
            '/question_categories/question_category',
            function (\DOMElement $category, \DOMXPath $xpath) use ($contextids): bool {
                $contextid = $xpath->evaluate('string(contextid)', $category);
                return !isset($contextids[$contextid]);
            }
        );

        self::strip_xml(
            $backupbasepath . '/course/enrolments.xml',
            '/enrolments/enrols/enrol',
            function (\DOMElement $enrol, \DOMXPath $xpath): bool {
                return $xpath->evaluate('string(enrol)', $enrol) !== 'manual';
            }
        );

        $groupids = $DB->get_fieldset_select('tool_muprog_group', 'groupid', "courseid = ?", [$courseid]);
        if ($groupids) {
            $groupids = array_flip($groupids);
            self::strip_xml(
                $backupbasepath . '/groups.xml',
                '/groups/group',
                function (\DOMElement $group, \DOMXPath $xpath) use ($groupids): bool {
                    return isset($groupids[$group->getAttribute('id')]);
                }
            );
            self::strip_xml(
                $backupbasepath . '/groups.xml',
                '/groups/groupings/grouping/grouping_groups/grouping_group',
                function (\DOMElement $groupinggroup, \DOMXPath $xpath) use ($groupids): bool {
                    $groupid = $xpath->evaluate('string(groupid)', $groupinggroup);
                    return isset($groupids[$groupid]);
                }
            );
        }
    }

    /**
     * Remove selected elements from XML file.
     *
     * @param string $file
     * @param string $query xpath of candidate elements
     * @param callable $remove returns true if given element should be removed, parameters are DOMElement and DOMXPath
     * @return void
     */
    private static function strip_xml(string $file, string $query, callable $remove): void {
        if (!is_readable($file)) {
            return;
        }
        $document = new \DOMDocument();
        if (!$document->load($file, LIBXML_PARSEHUGE)) {
            throw new \core\exception\coding_exception('Cannot parse backup file ' . basename($file));
        }
        $xpath = new \DOMXPath($document);
        $changed = false;
        foreach (iterator_to_array($xpath->query($query)) as $element) {
            if ($remove($element, $xpath)) {
                $element->parentNode->removeChild($element);
                $changed = true;
            }
        }
        if ($changed) {
            $document->save($file);
        }
    }

    /**
     * Enrol users with given roles in original course into the new course.
     *
     * @param \context_course $sourcecontext
     * @param int $newcourseid
     * @return void
     */
    private function keep_roles(\context_course $sourcecontext, int $newcourseid): void {
        global $DB;

        if (!$this->keeproleids) {
            return;
        }
        $enrol = enrol_get_plugin('manual');
        if (!$enrol) {
            return;
        }
        $instance = $DB->get_record('enrol', ['courseid' => $newcourseid, 'enrol' => 'manual'], '*', IGNORE_MULTIPLE);
        if (!$instance) {
            $newcourse = $DB->get_record('course', ['id' => $newcourseid], '*', MUST_EXIST);
            $instanceid = $enrol->add_default_instance($newcourse);
            if (!$instanceid) {
                $instanceid = $enrol->add_instance($newcourse);
            }
            $instance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        }
        foreach ($this->keeproleids as $roleid) {
            $users = get_role_users($roleid, $sourcecontext, false, 'u.id, u.deleted, u.suspended', 'u.id ASC');
            foreach ($users as $user) {
                if ($user->deleted || $user->suspended) {
                    continue;
                }
                $enrol->enrol_user($instance, $user->id, $roleid);
            }
        }
    }
}
