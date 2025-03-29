<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Program enrolment plugin events.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => \core\event\course_updated::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_updated',
    ],
    [
        'eventname'   => \core\event\course_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_deleted',
    ],
    [
        'eventname'   => \core\event\course_category_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_category_deleted',
    ],
    [
        'eventname'   => \core\event\user_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::user_deleted',
    ],
    [
        'eventname'   => \core\event\cohort_member_added::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::cohort_member_added',
    ],
    [
        'eventname'   => \core\event\cohort_member_removed::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::cohort_member_removed',
    ],
    [
        'eventname'   => \core\event\course_completed::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::course_completed',
    ],
    [
        'eventname'   => \core\event\group_deleted::class,
        'callback'    => \tool_muprog\local\event_observer::class . '::group_deleted',
    ],
    [
        'eventname' => \tool_certificate\event\template_deleted::class,
        'callback' => \tool_muprog\local\certificate::class . '::template_deleted',
    ],
];
