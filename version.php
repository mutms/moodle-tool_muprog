<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Programs plugin version.
 *
 * @package     tool_muprog
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var stdClass $plugin */
$plugin->component = 'tool_muprog';
$plugin->version   = 2025040900;
$plugin->requires  = 2024100700;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->supported = [405, 405];
$plugin->release   = 'mu-4.5.3-04';

$plugin->dependencies = [
    'tool_mulib' => 2025040900,
    'enrol_muprog' => 2025040900,
];
