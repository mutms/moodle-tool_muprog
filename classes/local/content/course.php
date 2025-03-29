<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\content;

use tool_muprog\local\util;

/**
 * Program course item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course extends item {
    /** @var int */
    protected $courseid;

    /** @var ?item Previous item needs to be completed in order to allow course access */
    protected $previous;

    /**
     * Get course id.
     *
     * @return int
     */
    public function get_courseid(): int {
        return $this->courseid;
    }

    /**
     * Is this item deletable?
     *
     * @return bool
     */
    public function is_deletable(): bool {
        if (!$this->id) {
            return false;
        }
        return true;
    }

    /**
     * Return item that must be completed before allowing access to this course.
     *
     * @return item|null
     */
    public function get_previous(): ?item {
        return $this->previous;
    }

    /**
     * Set previous item to new value.
     *
     * @param item|null $previous new previous item
     * @return void
     */
    protected function fix_previous(?item $previous): void {
        $this->previous = $previous;
    }

    /**
     * Factory method.
     *
     * @param \stdClass $record
     * @param item|null $previous
     * @param array $unusedrecords
     * @param array $prerequisites
     * @return course
     */
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->topitem || !$record->courseid || $record->trainingid !== null) {
            throw new \coding_exception('Invalid course item');
        }
        $item = new course();
        $item->id = $record->id;
        $item->programid = $record->programid;
        $item->courseid = $record->courseid;
        $item->previous = $previous;
        if ($previous) {
            if ($previous->id == $record->id) {
                $item->previous = null;
                $item->problemdetected = true;
            } else if ($record->previtemid != $previous->id) {
                $item->problemdetected = true;
            }
        } else {
            if ($record->previtemid) {
                $item->problemdetected = true;
            }
        }
        $item->fullname = $record->fullname;
        $item->points = $record->points;
        $item->completiondelay = $record->completiondelay;

        if ($record->minprerequisites !== null) {
            $item->problemdetected = true;
        }
        if ($record->minpoints !== null) {
            $item->problemdetected = true;
        }

        // NOTE: Prerequisites are verified in set that contains this course.

        return $item;
    }

    /**
     * Fix item prerequisites if necessary.
     *
     * @param array $prerequisites
     * @return bool true if fix applied
     */
    protected function fix_prerequisites(array &$prerequisites): bool {
        // Nothing to do, parent is defining the prerequisites.
        return false;
    }

    /**
     * Returns expected item record data.
     *
     * @return array
     */
    protected function get_record(): array {
        global $DB;

        $fullname = $DB->get_field('course', 'fullname', ['id' => $this->courseid]);
        if ($fullname === false) {
            $fullname = $this->fullname;
        }

        return [
            'id' => (empty($this->id) ? null : (string)$this->id),
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => (string)$this->courseid,
            'trainingid' => null,
            'previtemid' => (isset($this->previous) ? (string)$this->previous->id : null),
            'fullname' => $fullname,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => (string)$this->points,
            'minpoints' => null,
            'completiondelay' => (string)$this->completiondelay,
        ];
    }
}

