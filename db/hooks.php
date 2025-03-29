<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Programs hook callbacks.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \customfield_mutrain\hook\framework_usage::class,
        'callback' => [\tool_muprog\callback\customfield_mutrain::class, 'framework_usage'],
    ],
    [
        'hook' => \customfield_mutrain\hook\completion_updated::class,
        'callback' => [\tool_muprog\callback\customfield_mutrain::class, 'completion_updated'],
    ],
];
