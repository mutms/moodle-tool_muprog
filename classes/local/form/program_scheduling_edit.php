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

namespace tool_muprog\local\form;

use stdClass;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\dateinterval;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;
use tool_muprog\local\program;

/**
 * Edit program scheduling settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_scheduling_edit extends form {
    /** @var string[] names of program dates */
    public const DATES = ['start', 'due', 'end'];

    #[\Override]
    protected function definition(): void {
        foreach (self::DATES as $name) {
            $this->add_program_date($name);
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('updatescheduling', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        foreach (self::DATES as $name) {
            $this->validate_program_date($name, $data, $allerrors);
        }
    }

    /**
     * Add program date.
     *
     * @param string $name
     * @return void
     */
    protected function add_program_date(string $name): void {
        $dm = $this->get_display_manager();

        $datetypes = array_map('strval', program::{'get_program_' . $name . 'date_types'}());
        $type = new select('program' . $name . '_type', get_string('program' . $name, 'tool_muprog'), $datetypes);
        $type->set_required(true);
        $type->add_help_button('program' . $name, 'tool_muprog');
        $this->add($type);

        $date = new datetime('program' . $name . '_date', get_string('program' . $name . '_date', 'tool_muprog'));
        $date->set_required_marker(true);
        $date->add_validator(new required_if_visible());
        $this->add($date);
        $dm->hide_if('program' . $name . '_date', 'program' . $name . '_type', 'neq', 'date');

        $delay = new dateinterval('program' . $name . '_delay', get_string('program' . $name . '_delay', 'tool_muprog'), ['m', 'd', 'h']);
        $delay->set_required_marker(true);
        $delay->add_validator(new required_if_visible());
        $this->add($delay);
        $dm->hide_if('program' . $name . '_delay', 'program' . $name . '_type', 'neq', 'delay');
    }

    /**
     * Validate date.
     *
     * @param string $name
     * @param array $data
     * @param array $allerrors
     * @return void
     */
    protected function validate_program_date(string $name, array $data, array &$allerrors): void {
        if ($data['program' . $name . '_type'] === 'delay') {
            $delay = $data['program' . $name . '_delay'];
            // Program scheduling supports one time unit only.
            if ($delay !== null && !preg_match('/^(P[1-9][0-9]*[MD]|PT[1-9][0-9]*H)$/D', $delay)) {
                $allerrors['program' . $name . '_delay'][] = get_string('delay_oneunit', 'tool_muprog');
            }
        }
        if ($name === 'start') {
            return;
        }
        if ($data['program' . $name . '_type'] === 'date' && $data['programstart_type'] === 'date') {
            if (
                $data['programstart_date'] && $data['program' . $name . '_date']
                && $data['programstart_date'] >= $data['program' . $name . '_date']
            ) {
                $allerrors['program' . $name . '_date'][] = get_string('error');
            }
        }
        if ($name === 'end' && $data['programdue_type'] === 'date' && $data['programend_type'] === 'date') {
            if (
                $data['programdue_date'] && $data['programend_date']
                && $data['programdue_date'] > $data['programend_date']
            ) {
                $allerrors['programend_date'][] = get_string('error');
            }
        }
    }

    /**
     * Current data of program date elements.
     *
     * @param stdClass $program
     * @param string $name start, due or end
     * @return array
     */
    public static function get_date_current_data(stdClass $program, string $name): array {
        $current = [];
        if (!$program->{$name . 'datejson'}) {
            return $current;
        }
        $json = (array)json_decode($program->{$name . 'datejson'});
        if (isset($json['type'])) {
            $current['program' . $name . '_type'] = $json['type'];
        }
        if (isset($json['date'])) {
            $current['program' . $name . '_date'] = $json['date'];
        }
        if (isset($json['delay'])) {
            $current['program' . $name . '_delay'] = $json['delay'];
        }
        return $current;
    }

    /**
     * Convert submitted delay interval to the format expected by program::update_scheduling().
     *
     * @param stdClass $data form data, modified
     * @param string $name start, due or end
     */
    public static function apply_delay(stdClass $data, string $name): void {
        $key = 'program' . $name . '_delay';
        if ($data->{'program' . $name . '_type'} !== 'delay') {
            unset($data->$key);
            return;
        }
        $di = new \DateInterval($data->$key);
        if ($di->m) {
            $data->$key = ['type' => 'months', 'value' => $di->m];
        } else if ($di->d) {
            $data->$key = ['type' => 'days', 'value' => $di->d];
        } else {
            $data->$key = ['type' => 'hours', 'value' => $di->h];
        }
    }
}
