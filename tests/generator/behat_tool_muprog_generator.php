<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Program behat generators.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_tool_muprog_generator extends behat_generator_base {

    /**
     * Get a list of the entities that Behat can create using the generator step.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'programs' => [
                'singular' => 'program',
                'datagenerator' => 'program',
                'required' => [],
            ],
            'program_items' => [
                'singular' => 'program_item',
                'datagenerator' => 'program_item',
                'required' => [],
            ],
            'program_allocations' => [
                'singular' => 'program_allocation',
                'datagenerator' => 'program_allocation',
                'required' => [],
            ],
        ];
    }
}
