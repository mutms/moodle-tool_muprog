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

namespace tool_muprog\local\source;

use stdClass;

/**
 * Upload of manual program allocations from CSV data.
 *
 * The wizard parses the uploaded or pasted text once, the page keeps the rows, the column
 * meanings and the options in the muform wizard state; this class only validates and
 * transforms that data and runs the upload.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manual_upload {
    /** @var int rows of the dry run preview table */
    public const PREVIEW_ROWS = 10;

    /** @var int rows of the data preview shown with the column meanings */
    public const DATA_PREVIEW_ROWS = 4;

    /** @var string column is not used */
    public const COLUMN_IGNORE = '';

    /** @var string row will be allocated */
    public const RESULT_ALLOCATED = 'allocated';

    /** @var string row was skipped */
    public const RESULT_SKIPPED = 'skipped';

    /** @var string row cannot be allocated */
    public const RESULT_ERROR = 'error';

    /** @var array columns that identify users */
    private const USER_COLUMNS = ['username', 'email', 'idnumber'];

    /** @var array columns with allocation dates */
    private const DATE_COLUMNS = ['timestart', 'timedue', 'timeend'];

    /**
     * Column meanings.
     *
     * @return array column value => label
     */
    public static function get_column_menu(): array {
        return [
            self::COLUMN_IGNORE => get_string('source_manual_upload_column_ignore', 'tool_muprog'),
            'username' => get_string('username'),
            'email' => get_string('email'),
            'idnumber' => get_string('idnumber'),
            'timestart' => get_string('programstart', 'tool_muprog'),
            'timedue' => get_string('programdue', 'tool_muprog'),
            'timeend' => get_string('programend', 'tool_muprog'),
        ];
    }

    /**
     * Skip options, each one turns a class of problem into a skipped row.
     *
     * @return array name => label
     */
    public static function get_skip_menu(): array {
        return [
            'skipmissing' => get_string('source_manual_upload_skip_missing', 'tool_muprog'),
            'skipambiguous' => get_string('source_manual_upload_skip_ambiguous', 'tool_muprog'),
            'skipallocated' => get_string('source_manual_upload_skip_allocated', 'tool_muprog'),
            'skipinvalid' => get_string('source_manual_upload_skip_invalid', 'tool_muprog'),
        ];
    }

    /**
     * Keep only the sections that can be used.
     *
     * @param array $stored decoded document
     * @return stdClass keys: rows, columns, options
     */
    public static function validate(array $stored): stdClass {
        $csvdata = (object)['rows' => [], 'columns' => [], 'options' => []];

        $rows = $stored['rows'] ?? null;
        if (is_array($rows) && !self::check_rows($rows)) {
            $csvdata->rows = fix_utf8(array_values(array_map('array_values', $rows)));
        }
        if (!$csvdata->rows) {
            return $csvdata;
        }

        $columns = $stored['columns'] ?? null;
        if (is_array($columns)) {
            $map = [];
            foreach ($csvdata->rows[0] as $index => $unused) {
                $value = $columns['map'][$index] ?? null;
                $map[$index] = is_string($value) ? $value : self::COLUMN_IGNORE;
            }
            $clean = ['headers' => $columns['headers'] ?? null, 'map' => $map];
            if (!self::check_columns($csvdata->rows, $clean)) {
                $csvdata->columns = $clean;
            }
        }

        $options = $stored['options'] ?? null;
        if (is_array($options)) {
            $clean = [];
            foreach (self::get_option_names() as $name) {
                if (isset($options[$name])) {
                    $clean[$name] = (int)(bool)$options[$name];
                }
            }
            if (isset($options['preview']) && is_bool($options['preview'])) {
                // False means the options were confirmed and the upload may run.
                $clean['preview'] = $options['preview'];
            }
            $csvdata->options = $clean;
        }

        return $csvdata;
    }

    /**
     * Problem with the data itself, null when it can be used.
     *
     * @param array $rows first row may hold the column headers
     * @return string|null string identifier of the problem
     */
    public static function check_rows(array $rows): ?string {
        if (count($rows) < 1) {
            return 'source_manual_upload_error_empty';
        }

        $width = null;
        foreach ($rows as $row) {
            if (!is_array($row) || !$row) {
                return 'source_manual_upload_error_rowsize';
            }
            foreach ($row as $value) {
                if (!is_scalar($value)) {
                    return 'source_manual_upload_error_rowsize';
                }
            }
            if ($width !== null && count($row) !== $width) {
                return 'source_manual_upload_error_rowsize';
            }
            $width = count($row);
        }

        return null;
    }

    /**
     * Problems with the columns section, empty when it can be used.
     *
     * @param array $rows all rows of the data
     * @param array $columns section holding the headers flag and the column map
     * @return array string identifier of the problem, keyed by the name of the form element
     */
    public static function check_columns(array $rows, array $columns): array {
        $errors = [];

        $headers = $columns['headers'] ?? null;
        if (!is_bool($headers)) {
            $errors['headers'] = 'source_manual_upload_error_headers';
        } else if ($headers && count($rows) < 2) {
            $errors['headers'] = 'source_manual_upload_error_nodatarows';
        }

        $map = $columns['map'] ?? null;
        if (!is_array($map)) {
            $errors['column_0'] = 'source_manual_upload_error_columnunknown';
            return $errors;
        }

        $menu = self::get_column_menu();
        $used = [];
        foreach (array_keys($rows[0] ?? []) as $index) {
            $value = $map[$index] ?? self::COLUMN_IGNORE;
            if (!is_string($value) || !array_key_exists($value, $menu)) {
                $errors['column_' . $index] = 'source_manual_upload_error_columnunknown';
                continue;
            }
            if ($value === self::COLUMN_IGNORE) {
                continue;
            }
            if (isset($used[$value])) {
                $errors['column_' . $index] = 'source_manual_upload_error_columntwice';
            }
            $used[$value] = true;
        }

        if (!array_intersect(self::USER_COLUMNS, array_keys($used))) {
            $errors['column_0'] = 'source_manual_upload_error_nouser';
        }

        return $errors;
    }

    /**
     * Rows that hold data, the column names are not one of them.
     *
     * Only for data that passed the columns stage.
     *
     * @param stdClass $csvdata
     * @return array
     */
    public static function get_data_rows(stdClass $csvdata): array {
        return array_slice($csvdata->rows, empty($csvdata->columns['headers']) ? 0 : 1);
    }

    /**
     * Names of all upload options, the options section holds each of them.
     *
     * @return array
     */
    public static function get_option_names(): array {
        return array_keys(self::get_skip_menu());
    }

    /**
     * Is the wizard still waiting for the data itself?
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_source_stage(stdClass $csvdata): bool {
        return self::check_rows($csvdata->rows) !== null;
    }

    /**
     * Is the wizard waiting for the meaning of the columns?
     *
     * Only for data that passed the source stage.
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_columns_stage(stdClass $csvdata): bool {
        return !$csvdata->columns || (bool)self::check_columns($csvdata->rows, $csvdata->columns);
    }

    /**
     * Is the wizard waiting for the upload options?
     *
     * The wizard moves on only when the options were confirmed, which is what the preview flag records.
     * Only for data that passed the columns stage.
     *
     * @param stdClass $csvdata
     * @return bool
     */
    public static function is_options_stage(stdClass $csvdata): bool {
        if (($csvdata->options['preview'] ?? true) !== false) {
            return true;
        }
        foreach (self::get_option_names() as $name) {
            if (!array_key_exists($name, $csvdata->options)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Data of the source stage, pasted or uploaded CSV, the later stages start over.
     *
     * @param string $content CSV text
     * @param string $encoding
     * @param string $delimitername separator name, or "auto" to detect it
     * @return stdClass keys: rows, columns, options
     */
    public static function save_source(string $content, string $encoding, string $delimitername): stdClass {
        $rows = \tool_mulib\local\import_util::parse($content, $encoding, $delimitername);
        return self::validate(['rows' => $rows]);
    }

    /**
     * Add the meaning of each column, the options have to be confirmed again.
     *
     * @param stdClass $csvdata
     * @param stdClass $formdata data of the columns form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_columns(stdClass $csvdata, stdClass $formdata): stdClass {
        $map = [];
        foreach ($csvdata->rows[0] as $index => $unused) {
            $map[$index] = $formdata->{'column_' . $index} ?? self::COLUMN_IGNORE;
        }
        $csvdata->columns = ['headers' => !empty($formdata->headers), 'map' => $map];
        if ($csvdata->options) {
            // The chosen options stay as defaults, the dry run has to be seen again.
            $csvdata->options['preview'] = true;
        }
        return $csvdata;
    }

    /**
     * Add the confirmed upload options, the wizard has nothing left to ask afterwards.
     *
     * @param stdClass $csvdata
     * @param stdClass $formdata data of the options form
     * @return stdClass keys: rows, columns, options
     */
    public static function save_options(stdClass $csvdata, stdClass $formdata): stdClass {
        $options = [];
        foreach (self::get_option_names() as $name) {
            $options[$name] = (int)(bool)($formdata->$name ?? 0);
        }
        $options['preview'] = false;
        $csvdata->options = $options;
        return $csvdata;
    }

    /**
     * Check one row and work out what would happen to it.
     *
     * @param stdClass $program
     * @param array $row raw values
     * @param array $columns column index => column value
     * @param stdClass $options skip flags
     * @param array $seen ids of users found in earlier rows, updated
     * @return stdClass keys: result, message, identifier, userid, dateoverrides
     */
    public static function check_row(stdClass $program, array $row, array $columns, stdClass $options, array &$seen): stdClass {
        global $DB, $CFG;

        $outcome = (object)[
            'result' => self::RESULT_ERROR,
            'message' => '',
            'identifier' => '',
            'userid' => null,
            'dateoverrides' => [],
        ];

        $values = [];
        foreach ($columns as $index => $column) {
            if ($column === self::COLUMN_IGNORE) {
                continue;
            }
            $values[$column] = isset($row[$index]) ? trim((string)$row[$index]) : '';
        }

        $identifiers = array_filter(array_intersect_key($values, array_flip(self::USER_COLUMNS)), fn($v) => $v !== '');
        $outcome->identifier = implode(', ', $identifiers);
        if (!$identifiers) {
            return self::outcome($outcome, !empty($options->skipinvalid), 'source_manual_upload_error_nouservalue');
        }

        // Every given user column must point at the same single user.
        $found = null;
        foreach ($identifiers as $field => $value) {
            $select = $DB->sql_equal($field, ':value', $field !== 'email');
            $select .= ' AND deleted = 0 AND confirmed = 1 AND id <> :guestid';
            $params = ['value' => $value, 'guestid' => $CFG->siteguest];
            if ($field === 'username') {
                $select .= ' AND mnethostid = :mnethostid';
                $params['mnethostid'] = $CFG->mnet_localhost_id;
                $params['value'] = \core_text::strtolower($value);
            }
            $users = $DB->get_records_select('user', $select, $params, 'id ASC', 'id', 0, 2);
            if (!$users) {
                return self::outcome($outcome, !empty($options->skipmissing), 'source_manual_upload_error_missing', $value);
            }
            if (count($users) > 1) {
                return self::outcome($outcome, !empty($options->skipambiguous), 'source_manual_upload_error_ambiguous', $value);
            }
            $user = reset($users);
            if ($found && $found->id != $user->id) {
                return self::outcome($outcome, !empty($options->skipambiguous), 'source_manual_upload_error_ambiguous', $value);
            }
            $found = $user;
        }
        $outcome->userid = (int)$found->id;

        if (isset($seen[$outcome->userid])) {
            return self::outcome($outcome, !empty($options->skipallocated), 'source_manual_upload_error_duplicate');
        }
        $seen[$outcome->userid] = true;

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $outcome->userid])) {
            return self::outcome($outcome, !empty($options->skipallocated), 'source_manual_upload_error_allocated');
        }

        foreach (self::DATE_COLUMNS as $column) {
            if (!isset($values[$column]) || $values[$column] === '') {
                continue;
            }
            $time = strtotime($values[$column]);
            if ($time === false) {
                return self::outcome($outcome, !empty($options->skipinvalid), 'source_manual_upload_error_date', $values[$column]);
            }
            $outcome->dateoverrides[$column] = $time;
        }
        if (!base::is_valid_dateoverrides($program, $outcome->dateoverrides)) {
            return self::outcome($outcome, !empty($options->skipinvalid), 'source_manual_upload_error_dates');
        }

        $outcome->result = self::RESULT_ALLOCATED;
        return $outcome;
    }

    /**
     * Check all rows without changing anything.
     *
     * @param stdClass $program
     * @param stdClass $csvdata rows and columns of the upload
     * @param stdClass $options
     * @return array list of outcomes, one per data row
     */
    public static function check(stdClass $program, stdClass $csvdata, stdClass $options): array {
        $outcomes = [];
        $seen = [];
        foreach (self::get_data_rows($csvdata) as $row) {
            $outcomes[] = self::check_row($program, $row, $csvdata->columns['map'], $options, $seen);
        }
        return $outcomes;
    }

    /**
     * Allocate the users of rows that passed the check.
     *
     * Nothing is written when any row failed, the manager has to resolve it or skip it.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $csvdata rows and columns of the upload
     * @param stdClass $options
     * @return array counts keyed by result
     */
    public static function import(stdClass $program, stdClass $source, stdClass $csvdata, stdClass $options): array {
        $outcomes = self::check($program, $csvdata, $options);
        $counts = [self::RESULT_ALLOCATED => 0, self::RESULT_SKIPPED => 0, self::RESULT_ERROR => 0];
        foreach ($outcomes as $outcome) {
            $counts[$outcome->result]++;
        }
        if ($counts[self::RESULT_ERROR]) {
            return $counts;
        }

        $allocations = [];
        foreach ($outcomes as $outcome) {
            if ($outcome->result === self::RESULT_ALLOCATED) {
                $allocations[$outcome->userid] = $outcome->dateoverrides;
            }
        }
        if (!$allocations) {
            return $counts;
        }

        manual::allocate_uploaded_users($program, $source, $allocations);

        return $counts;
    }

    /**
     * Mark the outcome as skipped or failed.
     *
     * @param stdClass $outcome
     * @param bool $skip
     * @param string $errorcode
     * @param string|null $a
     * @return stdClass
     */
    private static function outcome(stdClass $outcome, bool $skip, string $errorcode, ?string $a = null): stdClass {
        $outcome->result = $skip ? self::RESULT_SKIPPED : self::RESULT_ERROR;
        $outcome->message = get_string($errorcode, 'tool_muprog', $a);
        return $outcome;
    }

    /**
     * Does the first row look like column names rather than data?
     *
     * @param array $row first row of the data
     * @return bool
     */
    public static function guess_headers(array $row): bool {
        foreach ($row as $header) {
            if (self::guess_column((string)$header) !== self::COLUMN_IGNORE) {
                return true;
            }
        }
        return false;
    }

    /**
     * Guess the meaning of each column.
     *
     * Headers name the columns, only the first column that identifies users is used.
     * Without headers a column of email addresses identifies users, otherwise the first column.
     *
     * @param array $rows all rows of the data
     * @param bool $headers true if the first row holds column names
     * @return array column index => column value
     */
    public static function guess_columns(array $rows, bool $headers): array {
        $first = $rows[0];
        $guesses = array_fill_keys(array_keys($first), self::COLUMN_IGNORE);

        if ($headers) {
            $used = [];
            $useridentified = false;
            foreach ($first as $index => $header) {
                $guess = self::guess_column((string)$header);
                if ($guess === self::COLUMN_IGNORE || isset($used[$guess])) {
                    continue;
                }
                if (in_array($guess, self::USER_COLUMNS, true)) {
                    if ($useridentified) {
                        continue;
                    }
                    $useridentified = true;
                }
                $used[$guess] = true;
                $guesses[$index] = $guess;
            }
            return $guesses;
        }

        foreach (array_keys($first) as $index) {
            $values = array_filter(array_column($rows, $index), fn($v) => trim((string)$v) !== '');
            if ($values && count(array_filter($values, fn($v) => str_contains((string)$v, '@'))) === count($values)) {
                $guesses[$index] = 'email';
                return $guesses;
            }
        }
        $guesses[array_key_first($first)] = 'username';
        return $guesses;
    }

    /**
     * Guess the meaning of one column from its header.
     *
     * @param string $header
     * @return string column value
     */
    private static function guess_column(string $header): string {
        return match (\tool_mulib\local\import_util::normalise_header($header)) {
            'username', 'login' => 'username',
            'email', 'emailaddress', 'mail' => 'email',
            'idnumber' => 'idnumber',
            'timestart', 'startdate', 'start', 'programstart' => 'timestart',
            'timedue', 'duedate', 'due', 'programdue' => 'timedue',
            'timeend', 'enddate', 'end', 'programend' => 'timeend',
            default => self::COLUMN_IGNORE,
        };
    }
}
