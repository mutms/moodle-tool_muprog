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

namespace tool_muprog\local\source;

use core_text;
use stdClass;
use tool_muprog\local\allocation;
use tool_muprog\local\util;

/**
 * Program allocation via external database table.
 *
 * @package     tool_muprog
 * @copyright   2025 e-Learning Team, Universiti Malaysia Terengganu
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class externaldb extends base {
    /** @var array<string, mixed>|null */
    private static ?array $configcache = null;
    /** @var bool */
    private static bool $debugbufferactive = false;

    /**
     * Return short type name of source.
     *
     * @return string
     */
    public static function get_type(): string {
        return 'externaldb';
    }

    /**
     * Can new sources be added to program?
     *
     * @param stdClass $program
     * @return bool
     */
    public static function is_new_allowed(stdClass $program): bool {
        return (bool)get_config('tool_muprog', 'source_externaldb_allownew');
    }

    /**
     * Decode custom configuration stored in datajson.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        $defaults = [
            'remotetable' => '',
            'programfield' => '',
            'programvalue' => '',
            'userfield' => '',
            'usermapping' => 'idnumber',
            'timeallocatedfield' => '',
            'timestartfield' => '',
            'timeduefield' => '',
            'timeendfield' => '',
            'sourceinstancefield' => '',
            'statusfield' => '',
            'statusvalue' => '',
            'syncdeletions' => 0,
        ];

        $data = [];
        if (!empty($source->datajson)) {
            $decoded = json_decode($source->datajson, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        foreach ($defaults as $key => $default) {
            $value = $data[$key] ?? $default;
            if ($key === 'syncdeletions') {
                $value = $value ? 1 : 0;
            } else if (is_string($value)) {
                $value = trim($value);
            }
            $source->$key = $value;
        }

        return $source;
    }

    /**
     * Encode configuration to be stored in datajson.
     *
     * @param stdClass $formdata
     * @return string
     */
    public static function encode_datajson(stdClass $formdata): string {
        $payload = [
            'remotetable' => trim($formdata->remotetable ?? ''),
            'programfield' => trim($formdata->programfield ?? ''),
            'programvalue' => trim($formdata->programvalue ?? ''),
            'userfield' => trim($formdata->userfield ?? ''),
            'usermapping' => $formdata->usermapping ?? 'idnumber',
            'timeallocatedfield' => trim($formdata->timeallocatedfield ?? ''),
            'timestartfield' => trim($formdata->timestartfield ?? ''),
            'timeduefield' => trim($formdata->timeduefield ?? ''),
            'timeendfield' => trim($formdata->timeendfield ?? ''),
            'sourceinstancefield' => trim($formdata->sourceinstancefield ?? ''),
            'statusfield' => trim($formdata->statusfield ?? ''),
            'statusvalue' => trim($formdata->statusvalue ?? ''),
            'syncdeletions' => empty($formdata->syncdeletions) ? 0 : 1,
        ];

        return util::json_encode($payload);
    }

    /**
     * Return custom edit form class name.
     *
     * @return string
     */
    public static function get_edit_form_class(): string {
        return 'tool_muprog\\local\\form\\source_externaldb_edit';
    }

    /**
     * Render status details.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        if (!$source) {
            return parent::render_status_details($program, $source);
        }

        $source = self::decode_datajson($source);
        if (!self::is_configured()) {
            return get_string('source_externaldb_settingsmissing', 'tool_muprog');
        }
        if (empty($source->remotetable)) {
            return get_string('source_externaldb_notconfigured', 'tool_muprog');
        }

        $a = (object)['table' => $source->remotetable];
        return get_string('source_externaldb_statusconfigured', 'tool_muprog', $a);
    }

    /**
     * Callback after form submission.
     *
     * @param stdClass|null $oldsource
     * @param stdClass $data
     * @param stdClass|null $source
     * @return void
     */
    public static function after_update(?stdClass $oldsource, stdClass $data, ?stdClass $source): void {
        if ($source && !defined('AJAX_SCRIPT') && !empty($data->enable)) {
            self::fix_allocations($source->programid, null);
            allocation::fix_enrol_instances($source->programid);
            allocation::fix_user_enrolments($source->programid, null);
        }
    }

    /**
     * Check if connection is configured.
     *
     * @return bool
     */
    public static function is_configured(): bool {
        $config = self::get_connection_config();
        if (empty($config['externaldbtype'])) {
            return false;
        }
        if (empty($config['externaldbname'])) {
            return false;
        }
        return true;
    }

    /**
     * Synchronise allocations with external database.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return bool
     */
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        global $CFG, $DB;

        if (!self::is_configured()) {
            return false;
        }

        $params = ['type' => self::get_type()];
        if ($programid) {
            $params['programid'] = $programid;
        }
        $sources = $DB->get_records('tool_muprog_source', $params, 'programid ASC');
        if (!$sources) {
            return false;
        }

        require_once($CFG->libdir . '/adodb/adodb.inc.php');

        $updated = false;

        foreach ($sources as $record) {
            $source = clone($record);
            $source = self::decode_datajson($source);

            if (empty($source->remotetable) || empty($source->programfield) || empty($source->userfield)) {
                continue;
            }

            $program = $DB->get_record('tool_muprog_program', ['id' => $record->programid], '*', MUST_EXIST);
            if ($program->archived) {
                continue;
            }

            $programvalue = $source->programvalue;
            if ($programvalue === '') {
                $programvalue = $program->idnumber ?: '';
            }
            if ($programvalue === '') {
                continue;
            }

            $connection = self::db_init();
            if (!$connection) {
                continue;
            }

            $conditions = [$source->programfield => $programvalue];
            if (!empty($source->statusfield) && $source->statusvalue !== '') {
                $conditions[$source->statusfield] = $source->statusvalue;
            }

            $sql = self::db_get_sql($source->remotetable, $conditions);
            $rs = $connection->Execute($sql);
            if (!$rs) {
                $connection->Close();
                continue;
            }

            $userfieldkey = core_text::strtolower($source->userfield);
            $timeallocatedkey = core_text::strtolower($source->timeallocatedfield);
            $timestartkey = core_text::strtolower($source->timestartfield);
            $timeduekey = core_text::strtolower($source->timeduefield);
            $timeendkey = core_text::strtolower($source->timeendfield);
            $sourceinstancekey = core_text::strtolower($source->sourceinstancefield);

            $remoteusers = [];

            $existing = $DB->get_records(
                'tool_muprog_allocation',
                ['programid' => $program->id, 'sourceid' => $record->id],
                '',
                'id, userid, archived, timeallocated, timestart, timedue, timeend, sourceinstanceid'
            );
            $existingbyuser = [];
            foreach ($existing as $alloc) {
                $existingbyuser[$alloc->userid] = $alloc;
            }

            while (!$rs->EOF) {
                $row = $rs->FetchRow();
                if (!$row) {
                    $rs->MoveNext();
                    continue;
                }
                $row = array_change_key_case($row, CASE_LOWER);
                $row = self::db_decode($row);

                if (!array_key_exists($userfieldkey, $row)) {
                    $rs->MoveNext();
                    continue;
                }

                $remotevalue = trim((string)$row[$userfieldkey]);
                if ($remotevalue === '') {
                    $rs->MoveNext();
                    continue;
                }

                $useridsqlfield = $source->usermapping ?? 'idnumber';
                if (!in_array($useridsqlfield, ['username', 'email', 'idnumber'], true)) {
                    $useridsqlfield = 'idnumber';
                }

                $userrecords = $DB->get_records('user', [
                    $useridsqlfield => $remotevalue,
                    'deleted' => 0,
                    'confirmed' => 1,
                ], 'id ASC', 'id');

                if (count($userrecords) !== 1) {
                    $rs->MoveNext();
                    continue;
                }

                $user = reset($userrecords);
                if (isguestuser($user->id)) {
                    $rs->MoveNext();
                    continue;
                }

                if ($userid && (int)$user->id !== $userid) {
                    $rs->MoveNext();
                    continue;
                }

                $remoteusers[$user->id] = true;

                $dateoverrides = [];
                if ($timeallocatedkey && isset($row[$timeallocatedkey])) {
                    $parsed = self::parse_remote_time($row[$timeallocatedkey]);
                    if ($parsed) {
                        $dateoverrides['timeallocated'] = $parsed;
                    }
                }
                if ($timestartkey && isset($row[$timestartkey])) {
                    $parsed = self::parse_remote_time($row[$timestartkey]);
                    if ($parsed) {
                        $dateoverrides['timestart'] = $parsed;
                    }
                }
                if ($timeduekey && isset($row[$timeduekey])) {
                    $parsed = self::parse_remote_time($row[$timeduekey]);
                    if ($parsed) {
                        $dateoverrides['timedue'] = $parsed;
                    }
                }
                if ($timeendkey && isset($row[$timeendkey])) {
                    $parsed = self::parse_remote_time($row[$timeendkey]);
                    if ($parsed) {
                        $dateoverrides['timeend'] = $parsed;
                    }
                }

                if (!base::is_valid_dateoverrides($program, $dateoverrides)) {
                    $rs->MoveNext();
                    continue;
                }

                $sourceinstanceid = null;
                if ($sourceinstancekey && isset($row[$sourceinstancekey])) {
                    $value = trim((string)$row[$sourceinstancekey]);
                    if ($value !== '') {
                        if (is_numeric($value)) {
                            $sourceinstanceid = (int)$value;
                        } else {
                            $sourceinstanceid = abs(crc32($value));
                        }
                    }
                }

                $allocation = $existingbyuser[$user->id] ?? null;

                if (!$allocation) {
                    $allocation = self::allocation_create($program, $record, $user->id, [], $dateoverrides, $sourceinstanceid);
                    $existingbyuser[$user->id] = $allocation;
                    $updated = true;
                } else {
                    $needsupdate = [];
                    if ($allocation->archived) {
                        $needsupdate['archived'] = 0;
                    }
                    if ($sourceinstanceid && $allocation->sourceinstanceid != $sourceinstanceid) {
                        $needsupdate['sourceinstanceid'] = $sourceinstanceid;
                    }
                    if (!empty($dateoverrides['timeallocated']) && $allocation->timeallocated != $dateoverrides['timeallocated']) {
                        $needsupdate['timeallocated'] = $dateoverrides['timeallocated'];
                    }
                    if (!empty($dateoverrides['timestart']) && $allocation->timestart != $dateoverrides['timestart']) {
                        $needsupdate['timestart'] = $dateoverrides['timestart'];
                    }
                    if (array_key_exists('timedue', $dateoverrides) && (int)$allocation->timedue !== (int)($dateoverrides['timedue'] ?? null)) {
                        $needsupdate['timedue'] = $dateoverrides['timedue'] ?? null;
                    }
                    if (array_key_exists('timeend', $dateoverrides) && (int)$allocation->timeend !== (int)($dateoverrides['timeend'] ?? null)) {
                        $needsupdate['timeend'] = $dateoverrides['timeend'] ?? null;
                    }

                    if ($needsupdate) {
                        $needsupdate['id'] = $allocation->id;
                        $DB->update_record('tool_muprog_allocation', (object)$needsupdate);
                        $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);
                        $existingbyuser[$user->id] = $allocation;
                        \tool_muprog\local\calendar::fix_allocation_events($allocation, $program);
                        $updated = true;
                    }
                }

                $rs->MoveNext();
            }
            $rs->Close();
            $connection->Close();
            self::end_debug_buffer();

            if (!empty($source->syncdeletions)) {
                foreach ($existing as $alloc) {
                    if ($userid && $alloc->userid != $userid) {
                        continue;
                    }
                    if (!empty($remoteusers[$alloc->userid])) {
                        continue;
                    }
                    if ($alloc->archived) {
                        continue;
                    }
                    $DB->set_field('tool_muprog_allocation', 'archived', 1, ['id' => $alloc->id]);
                    \tool_muprog\local\calendar::delete_allocation_events($alloc->id);
                    $updated = true;
                }
            }
        }

        return $updated;
    }

    /**
     * Establish connection with external database.
     *
     * @return \ADOConnection|null
     */
    private static function db_init(): ?\ADOConnection {
        $config = self::get_connection_config();
        if (empty($config['externaldbtype'])) {
            return null;
        }

        $connection = \ADONewConnection($config['externaldbtype']);
        if (!empty($config['externaldbdebug'])) {
            $connection->debug = true;
            ob_start();
            self::$debugbufferactive = true;
        }

        $result = @$connection->Connect(
            $config['externaldbhost'] ?? 'localhost',
            $config['externaldbuser'] ?? '',
            $config['externaldbpass'] ?? '',
            $config['externaldbname'] ?? '',
            true
        );
        if (!$result) {
            $connection->Close();
            self::end_debug_buffer();
            return null;
        }

        $connection->SetFetchMode(ADODB_FETCH_ASSOC);
        if (!empty($config['externaldbsetupsql'])) {
            $connection->Execute($config['externaldbsetupsql']);
        }

        return $connection;
    }

    /**
     * Stop debugging buffer if started.
     *
     * @return void
     */
    private static function end_debug_buffer(): void {
        if (self::$debugbufferactive && ob_get_level() > 0) {
            ob_end_clean();
        }
        self::$debugbufferactive = false;
    }

    /**
     * Get cached plugin configuration.
     *
     * @return array<string, mixed>
     */
    private static function get_connection_config(): array {
        if (self::$configcache !== null) {
            return self::$configcache;
        }
        $config = get_config('tool_muprog');
        if (!$config) {
            self::$configcache = [];
            return self::$configcache;
        }
        self::$configcache = (array)$config;
        return self::$configcache;
    }

    /**
     * Reset cached configuration during automated tests.
     *
     * @return void
     */
    public static function reset_config_cache_for_tests(): void {
        $istest = (defined('PHPUNIT_TEST') && PHPUNIT_TEST) || defined('BEHAT_SITE_RUNNING');
        if (!$istest) {
            throw new \coding_exception('reset_config_cache_for_tests() is intended for automated tests only');
        }
        self::$configcache = null;
        self::end_debug_buffer();
    }

    /**
     * Generate SQL statement for external database.
     *
     * @param string $table
     * @param array $conditions
     * @return string
     */
    private static function db_get_sql(string $table, array $conditions): string {
        $where = [];
        foreach ($conditions as $key => $value) {
            $value = self::db_encode(self::db_addslashes($value));
            $where[] = "$key = '$value'";
        }
        $whereclause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return "SELECT * FROM $table $whereclause";
    }

    /**
     * Encode value for remote database.
     *
     * @param string $text
     * @return string
     */
    private static function db_encode(string $text): string {
        $config = self::get_connection_config();
        $encoding = $config['externaldbencoding'] ?? 'utf-8';
        if ($encoding === '' || core_text::strtolower($encoding) === 'utf-8') {
            return $text;
        }
        return core_text::convert($text, 'utf-8', $encoding);
    }

    /**
     * Decode value received from remote database.
     *
     * @param mixed $value
     * @return mixed
     */
    private static function db_decode($value) {
        $config = self::get_connection_config();
        $encoding = $config['externaldbencoding'] ?? 'utf-8';
        if ($encoding === '' || core_text::strtolower($encoding) === 'utf-8') {
            return $value;
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::db_decode($v);
            }
            return $value;
        }
        if (!is_string($value)) {
            return $value;
        }
        return core_text::convert($value, $encoding, 'utf-8');
    }

    /**
     * Escape value for remote database.
     *
     * @param string $text
     * @return string
     */
    private static function db_addslashes(string $text): string {
        $config = self::get_connection_config();
        if (!empty($config['externaldbsybasequoting'])) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(["'", '"', "\0"], ['\\\'', '\\"', '\\0'], $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    /**
     * Convert remote date/time value to timestamp.
     *
     * @param mixed $value
     * @return int|null
     */
    private static function parse_remote_time($value): ?int {
        if ($value === null) {
            return null;
        }
        if (is_int($value) || ctype_digit((string)$value)) {
            $timestamp = (int)$value;
            if ($timestamp > 0) {
                return $timestamp;
            }
            return null;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }
        return $timestamp;
    }
}
