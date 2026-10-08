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

/**
 * Duplicate program including fresh copies of all its courses.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_muprog\local\operation\programduplicate;

define('CLI_SCRIPT', true);

/** @var moodle_database $DB */
/** @var stdClass $CFG */

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'id' => null,
        'idnumber' => null,
        'search' => '',
        'replace' => null,
        'category' => null,
        'coursecategory' => programduplicate::COURSECATEGORY_SAME,
        'keep-roles' => '',
        'sources' => null,
        'release' => false,
        'execute' => false,
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || ($options['id'] === null && $options['idnumber'] === null)) {
    $help = <<<EOT
Duplicate program including fresh copies of all its courses.

The result is a new draft program. It uses new courses that are copies of the
courses of the original program without any user data. Users cannot be
allocated to a draft program, release it in program management when it is ready.

Without --execute the script only prints what would be created.

Copying of courses may take a very long time. Do not modify the content of the
original program or its courses until this script finishes.
A course that cannot be copied does not stop the script: the other courses are
still copied and all problems are listed at the end. The new draft program is
then frozen. Either delete it and the courses it created and run the script
again, or dismiss the failed operation in program management and fix the
program manually.

Options:
--id=INT                 Id of program to duplicate
--idnumber=STRING        Idnumber of program to duplicate, alternative to --id
--replace=STRING         Required. Text put in place of the search text in the names and
                         idnumbers of the program and its courses. Where the search text
                         is not found the text is added to the end: after a space
                         in full names, after a dash in short names and idnumbers
--search=STRING          Text to be replaced, such as last year
--category=INT           Id of course category for the new program, 0 means system;
                         defaults to the category of the original program
--coursecategory=VALUE   Where to create new courses:
                           same      in the category of each original course (default)
                           program   in the category of the new program
                           INT       in the course category with this id
--keep-roles=LIST        Comma separated short names of roles, for example
                         editingteacher,teacher; users with these roles in an original
                         course are enrolled with the same role in its copy
--sources=LIST           Comma separated types of allocation sources to copy, for example
                         manual,cohort; use 'none' to copy no allocation sources. By default
                         all sources that support importing of settings are copied
--release                Release the new program when everything is copied. The draft is
                         not reviewed then: use this only if you are sure that the copied
                         allocation sources, dates and courses do not need any changes.
                         The program is not released if there were any problems
--execute                Duplicate the program, nothing is created without this option
-h, --help               Print out this help

Examples:
\$ php admin/tool/muprog/cli/program_duplicate.php --idnumber=ONB2026 --search=2026 --replace=2027
\$ php admin/tool/muprog/cli/program_duplicate.php --idnumber=ONB2026 --search=2026 --replace=2027 --execute

EOT;
    echo $help;
    exit($options['help'] ? 0 : 1);
}

// An option given without a value is true, it must not be used as value "1".
foreach (['id', 'idnumber', 'search', 'replace', 'category', 'coursecategory', 'keep-roles', 'sources'] as $name) {
    if ($options[$name] === true) {
        cli_error("Option --{$name} needs a value.");
    }
}

if ($options['replace'] === null || trim((string)$options['replace']) === '') {
    cli_error('Option --replace is required.');
}

if ($options['id'] !== null && $options['idnumber'] !== null) {
    cli_error('Use either --id or --idnumber, not both.');
}
if ($options['id'] !== null) {
    // An option without value is true, it must not be treated as id 1.
    if (!is_string($options['id']) || !preg_match('/^[1-9][0-9]*$/D', $options['id'])) {
        cli_error('Option --id needs a program id.');
    }
    $program = $DB->get_record('tool_muprog_program', ['id' => (int)$options['id']]);
} else {
    // Never look for a program with empty idnumber.
    if (!is_string($options['idnumber']) || trim($options['idnumber']) === '') {
        cli_error('Option --idnumber needs a program idnumber.');
    }
    $program = $DB->get_record('tool_muprog_program', ['idnumber' => $options['idnumber']]);
}
if (!$program) {
    cli_error('Program not found.');
}

$programcontext = null;
if ($options['category'] !== null) {
    if ((string)$options['category'] === '0') {
        $programcontext = context_system::instance();
    } else {
        $programcontext = context_coursecat::instance((int)$options['category'], IGNORE_MISSING);
        if (!$programcontext) {
            cli_error('Course category for the new program not found.');
        }
    }
}

$coursecategory = (string)$options['coursecategory'];
if ($coursecategory !== programduplicate::COURSECATEGORY_SAME && $coursecategory !== programduplicate::COURSECATEGORY_PROGRAM) {
    if (!preg_match('/^[1-9][0-9]*$/D', $coursecategory)) {
        cli_error('Invalid --coursecategory value.');
    }
    $coursecategory = (int)$coursecategory;
}

$keeproleids = [];
foreach (explode(',', (string)$options['keep-roles']) as $shortname) {
    $shortname = trim($shortname);
    if ($shortname === '') {
        continue;
    }
    $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
    if (!$roleid) {
        cli_error("Role '{$shortname}' not found.");
    }
    $keeproleids[] = (int)$roleid;
}

$sourcetypes = null;
if ($options['sources'] !== null) {
    $sourcetypes = [];
    if (trim((string)$options['sources']) !== 'none') {
        foreach (explode(',', (string)$options['sources']) as $type) {
            $type = trim($type);
            if ($type !== '') {
                $sourcetypes[] = $type;
            }
        }
        if (!$sourcetypes) {
            cli_error("Option --sources needs a list of allocation source types or 'none'.");
        }
    }
}

// Copying of large courses needs a lot of memory and time, PHP CLI may be configured with a limit.
raise_memory_limit(MEMORY_UNLIMITED);
core_php_time_limit::raise();

// Backup and restore need a real user, the courses are copied on behalf of admin.
\core\session\manager::set_user(get_admin());

$operation = new programduplicate(
    $program,
    (string)$options['search'],
    (string)$options['replace'],
    $programcontext,
    $coursecategory,
    $keeproleids,
    $sourcetypes,
    (bool)$options['release']
);
$plan = $operation->get_plan();

$context = context::instance_by_id($plan->contextid);
cli_writeln("Original program: {$program->fullname} [{$program->idnumber}] (id {$program->id})");
$state = $plan->release ? 'released at the end' : 'draft';
cli_writeln("New program:      {$plan->fullname} [{$plan->idnumber}] in " . $context->get_context_name(false) . ', ' . $state);
cli_writeln('Allocation sources to copy: ' . ($plan->sources ? implode(', ', $plan->sources) : 'none'));
cli_writeln('');
cli_writeln('Courses to copy: ' . count($plan->courses));
foreach ($plan->courses as $course) {
    $category = core_course_category::get($course->categoryid, IGNORE_MISSING, true);
    $categoryname = $category ? $category->get_formatted_name() : "category {$course->categoryid}";
    $idnumber = ($course->idnumber === '' ? '' : ", idnumber {$course->idnumber}");
    cli_writeln("  {$course->sourcefullname} [{$course->sourceshortname}] (id {$course->sourceid})");
    cli_writeln("    -> {$course->fullname} [{$course->shortname}]{$idnumber} in {$categoryname}");
}
foreach ($plan->warnings as $warning) {
    cli_writeln("WARNING: {$warning}");
}
cli_writeln('');

if ($plan->errors) {
    foreach ($plan->errors as $error) {
        cli_writeln("ERROR: {$error}");
    }
    cli_error('Program cannot be duplicated.');
}

if (!$options['execute']) {
    cli_writeln('Settings that would be copied as they are and should be reviewed in the new program:');
    foreach (programduplicate::get_review_notes($program, null, $plan->sources) as $note) {
        cli_writeln("  * {$note}");
    }
    cli_writeln('');
    if ($plan->courses) {
        cli_writeln('WARNING: do not modify the content of the original program or its courses while the program is being duplicated.');
        cli_writeln('');
    }
    cli_writeln('Nothing was created, add --execute to duplicate the program.');
    exit(0);
}

if ($plan->courses) {
    cli_writeln('WARNING: do not modify the content of the original program or its courses until this script finishes.');
    cli_writeln('');
}

$progress = function (string $message): void {
    cli_writeln(userdate(time(), '%H:%M:%S') . ' ' . $message);
};

try {
    $newprogram = $operation->execute($progress);
} catch (Throwable $ex) {
    cli_writeln('');
    cli_writeln('ERROR: ' . $ex->getMessage());
    $record = $DB->get_record('tool_muprog_operation', ['id' => $operation->get_operation_id()]);
    if ($record) {
        $state = \tool_muprog\local\operation\base::decode_state($record);
        cli_writeln("The new draft program with id {$record->programid} is frozen. In program management either delete it, or dismiss the failed operation and fix the program manually.");
        foreach (($state['courses'] ?? []) as $course) {
            if (!empty($course['newid'])) {
                cli_writeln("Delete course: {$course['fullname']} [{$course['shortname']}] (id {$course['newid']}), status {$course['status']}");
            }
        }
    }
    exit(1);
}

$url = new core\url('/admin/tool/muprog/management/program.php', ['id' => $newprogram->id]);
cli_writeln('');

$problems = $operation->get_problems();
if ($problems) {
    cli_writeln("FAILED. New draft program {$newprogram->fullname} [{$newprogram->idnumber}] (id {$newprogram->id}) is incomplete and frozen:");
    foreach ($problems as $problem) {
        cli_writeln("  * {$problem}");
    }
    cli_writeln('In program management either delete the program together with the courses created for it, or dismiss the failed operation and fix the program manually.');
    cli_writeln($url->out(false));
    exit(1);
}

$state = $newprogram->draft ? 'draft' : 'released';
cli_writeln("Done. New {$state} program: {$newprogram->fullname} [{$newprogram->idnumber}] (id {$newprogram->id})");
cli_writeln($url->out(false));
cli_writeln('');
if ($newprogram->draft) {
    cli_writeln('Settings were copied as they are in the original. Review before releasing the program:');
} else {
    cli_writeln('Settings were copied as they are in the original. The program is released, review it now:');
}
foreach (programduplicate::get_review_notes($newprogram, $program) as $note) {
    cli_writeln("  * {$note}");
}
exit(0);
