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

namespace tool_muprog\local\form;

use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\autocompletemany;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\download;
use tool_mulib\muform\element\select;
use tool_mulib\muform\form;
use tool_muprog\muform\autocomplete\export_contextid;
use tool_muprog\muform\autocompletemany\export_programids;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');

/**
 * Export programs.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class export extends form {
    #[\Override]
    protected function definition(): void {
        $program = $this->get_extra_data()['program'];
        $context = $this->get_extra_data()['context'];

        if ($program) {
            // Export was started from a program details page, let them add other programs.
            $source = new export_programids($context->id);
            $programids = new autocompletemany('programids', get_string('programs', 'tool_muprog'), $source);
            $programids->set_required(true);
            $this->add($programids);
        } else {
            // Export started from category, let them select other categories or system.
            $contextid = new autocomplete('contextid', get_string('category'), new export_contextid($context->id));
            $contextid->set_required(true);
            $this->add($contextid);

            $this->add(new checkbox('includesubcontexts', get_string('includesubcontexts', 'tool_muprog')));

            $this->add(new checkbox('archived', get_string('archived', 'tool_muprog')));
        }

        $choices = [
            'json' => get_string('exportformat_json', 'tool_muprog'),
            'csv' => get_string('exportformat_csv', 'tool_muprog'),
        ];
        $format = new select('format', get_string('exportformat', 'tool_muprog'), $choices);
        $format->set_required(true);
        $this->add($format);

        $choices = \csv_import_reader::get_delimiter_list();
        unset($choices['colon']); // This collides with formatted dates, better not use it at all.
        $delimiter = new select('delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        $delimiter->set_required(true);
        $this->add($delimiter);
        $this->get_display_manager()->hide_if('delimiter_name', 'format', 'neq', 'csv');

        $encoding = new select('encoding', get_string('encoding', 'tool_uploaduser'), \core_text::get_encodings());
        $encoding->set_required(true);
        $this->add($encoding);
        $this->get_display_manager()->hide_if('encoding', 'format', 'neq', 'csv');

        // The file is downloaded in a new window, the form stays open, "Back" returns to the programs.
        $this->add(new buttons('buttons'));
        $this->add(new download('submit', get_string('export', 'tool_muprog')), 'buttons');
        $this->add(new cancel('cancel', get_string('back')), 'buttons');
    }

    /**
     * Default delimiter for CSV exports.
     *
     * @return string
     */
    public static function get_default_delimiter(): string {
        $choices = \csv_import_reader::get_delimiter_list();
        if (array_key_exists('cfg', $choices)) {
            return 'cfg';
        } else if (get_string('listsep', 'langconfig') === ';') {
            return 'semicolon';
        } else {
            return 'comma';
        }
    }
}
