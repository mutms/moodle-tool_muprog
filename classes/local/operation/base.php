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

namespace tool_muprog\local\operation;

use stdClass;
use tool_muprog\local\util;

/**
 * Base class for lengthy program operations.
 *
 * Only one operation per program is allowed at a time, the operation record
 * is deleted when the operation finishes successfully.
 *
 * Program with operation is frozen in management UI, it is expected to be
 * draft which prevents any use outside of programs.
 *
 * Operation is running while its process holds the operation lock,
 * operation without the lock is considered to be aborted and is marked as failed automatically.
 * Failed operation has to be dismissed before the program can be fixed manually and used,
 * alternatively the program may be deleted.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /** @var stdClass|null operation record, null if operation was not started yet or if it finished successfully */
    private ?stdClass $record = null;

    /** @var \core\lock\lock[] locks of operations executed by this process indexed with operation id */
    private static array $locks = [];

    /**
     * Operation type, it matches the class name.
     *
     * @return string
     */
    final public static function get_type(): string {
        $parts = explode('\\', static::class);
        return end($parts);
    }

    /**
     * Start new operation for given program, the operation lock is kept until
     * the operation is resolved or failed, or until this process ends.
     *
     * @param int $programid
     * @param array $state
     * @return void
     */
    final protected function start(int $programid, array $state): void {
        global $DB, $USER;

        if ($this->record) {
            throw new \core\exception\coding_exception('Operation was started already');
        }
        if ($DB->record_exists('tool_muprog_operation', ['programid' => $programid])) {
            throw new \core\exception\coding_exception('Program has an unfinished operation already');
        }

        $record = new stdClass();
        $record->programid = $programid;
        $record->type = static::get_type();
        $record->timestarted = time();
        $record->timefailed = null;
        $record->userid = $USER->id;
        $record->statejson = util::json_encode($state);
        $record->id = $DB->insert_record('tool_muprog_operation', $record);

        $lock = self::get_lock_factory()->get_lock(self::get_lock_resource($record->id), 0, WEEKSECS);
        if (!$lock) {
            throw new \core\exception\coding_exception('Cannot obtain lock for new program operation');
        }
        self::$locks[$record->id] = $lock;

        $this->record = $DB->get_record('tool_muprog_operation', ['id' => $record->id], '*', MUST_EXIST);
    }

    /**
     * Returns id of operation that was started and did not finish successfully.
     *
     * @return int|null null if the operation was not started yet or if it finished successfully
     */
    final public function get_operation_id(): ?int {
        return $this->record ? (int)$this->record->id : null;
    }

    /**
     * Returns operation record.
     *
     * NOTE: the record exists only from the start of operation until it finishes successfully,
     *       failed operation keeps its record.
     *
     * @return stdClass
     */
    final public function get_record(): stdClass {
        if (!$this->record) {
            throw new \core\exception\coding_exception('Operation record does not exist, operation was not started or it is finished');
        }
        return $this->record;
    }

    /**
     * Returns current state of operation.
     *
     * @return array
     */
    final public function get_state(): array {
        return self::decode_state($this->get_record());
    }

    /**
     * Store current state of operation.
     *
     * @param array $state
     * @return void
     */
    final protected function save_state(array $state): void {
        global $DB;

        $record = $this->get_record();
        $record->statejson = util::json_encode($state);
        $DB->set_field('tool_muprog_operation', 'statejson', $record->statejson, ['id' => $record->id]);
    }

    /**
     * Mark operation as failed, the program stays frozen.
     *
     * @param string $error
     * @return void
     */
    final protected function fail(string $error): void {
        global $DB;

        $record = $this->get_record();
        $state = $this->get_state();
        $state['error'] = $error;
        $record->statejson = util::json_encode($state);
        $record->timefailed = time();
        $DB->update_record('tool_muprog_operation', $record);

        self::release_lock($record->id);
    }

    /**
     * Operation finished successfully, the record is deleted and the program is not frozen any more.
     *
     * NOTE: the operation record and state cannot be used afterwards.
     *
     * @return void
     */
    final protected function resolve(): void {
        global $DB;

        $record = $this->get_record();
        $DB->delete_records('tool_muprog_operation', ['id' => $record->id]);

        self::release_lock($record->id);

        // The operation does not exist any more, any later use of the record is a bug.
        $this->record = null;
    }

    /**
     * Decode state of operation.
     *
     * @param stdClass $operation
     * @return array
     */
    final public static function decode_state(stdClass $operation): array {
        if ($operation->statejson === null || $operation->statejson === '') {
            return [];
        }
        return (array)json_decode($operation->statejson, true);
    }

    /**
     * Mark operation as failed if it was aborted.
     *
     * Operation that did not fail and that is not locked by its process any more was aborted.
     *
     * @param stdClass $operation operation record
     * @return stdClass operation record, updated if the operation was aborted
     */
    private static function check_operation_aborted(stdClass $operation): stdClass {
        global $DB;

        if ($operation->timefailed) {
            return $operation;
        }
        if (isset(self::$locks[$operation->id])) {
            // This process is running the operation.
            return $operation;
        }

        $lock = self::get_lock_factory()->get_lock(self::get_lock_resource($operation->id), 0, MINSECS);
        if (!$lock) {
            // Some other process is running the operation.
            return $operation;
        }

        // The process that started the operation is gone.
        $state = self::decode_state($operation);
        $state['error'] = get_string('operation_aborted', 'tool_muprog');
        $operation->statejson = util::json_encode($state);
        $operation->timefailed = time();
        $DB->update_record('tool_muprog_operation', $operation);
        $lock->release();

        return $operation;
    }

    /**
     * Did the operation of program fail?
     *
     * NOTE: aborted operation is marked as failed here.
     *
     * @param int $programid
     * @return bool false if there is no operation or if it is still running
     */
    final public static function did_operation_fail(int $programid): bool {
        global $DB;

        $operation = $DB->get_record('tool_muprog_operation', ['programid' => $programid]);
        if (!$operation) {
            return false;
        }
        $operation = self::check_operation_aborted($operation);

        return (bool)$operation->timefailed;
    }

    /**
     * Returns operation of program that is not finished yet,
     * program with pending operation is frozen.
     *
     * The operation is either still running, or it failed, see timefailed.
     *
     * NOTE: operation that was aborted is not marked as failed until somebody checks,
     *       use $checkifaborted if it matters whether the operation is running or failed.
     *
     * @param int $programid
     * @param bool $checkifaborted true means mark aborted operation as failed first
     * @return stdClass|null operation record, null if there is no operation
     */
    final public static function get_pending_operation(int $programid, bool $checkifaborted = false): ?stdClass {
        global $DB;

        $operation = $DB->get_record('tool_muprog_operation', ['programid' => $programid]);
        if (!$operation) {
            return null;
        }
        if ($checkifaborted) {
            $operation = self::check_operation_aborted($operation);
        }

        return $operation;
    }

    /**
     * Dismiss failed operation of program, the program is not frozen any more.
     *
     * NOTE: whatever the operation did not finish has to be fixed manually.
     *
     * @param int $programid
     * @return void
     */
    final public static function dismiss_failed(int $programid): void {
        global $DB;

        if (!self::did_operation_fail($programid)) {
            if ($DB->record_exists('tool_muprog_operation', ['programid' => $programid])) {
                throw new \core\exception\coding_exception('Running operation cannot be dismissed');
            }
            return;
        }
        $DB->delete_records('tool_muprog_operation', ['programid' => $programid]);
    }

    /**
     * Returns lock factory for operations.
     *
     * @return \core\lock\lock_factory
     */
    private static function get_lock_factory(): \core\lock\lock_factory {
        return \core\lock\lock_config::get_lock_factory('tool_muprog_operation');
    }

    /**
     * Returns name of operation lock.
     *
     * @param int $operationid
     * @return string
     */
    private static function get_lock_resource(int $operationid): string {
        return 'operation_' . $operationid;
    }

    /**
     * Release lock of operation executed by this process.
     *
     * @param int $operationid
     * @return void
     */
    private static function release_lock(int $operationid): void {
        if (isset(self::$locks[$operationid])) {
            self::$locks[$operationid]->release();
            unset(self::$locks[$operationid]);
        }
    }
}
