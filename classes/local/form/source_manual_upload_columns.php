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

use tool_muprog\local\source\manual_upload;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\reload;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Allocation upload, stage two: what is in each column.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_upload_columns extends form {
    #[\Override]
    protected function definition(): void {
        $rows = $this->get_extra_data()['rows'];
        $first = $rows[0];

        $this->add(new info('info', '', get_string('source_manual_upload_columns_info', 'tool_muprog', count($rows))));
        $this->add(new inforawhtml('datapreview', '', $this->render_data($rows)));

        // Guesses are only defaults, coming back to this stage shows what was chosen before.
        $headers = (new checkbox('headers', get_string('source_manual_upload_columns_headers', 'tool_muprog')))
            ->set_default((int)manual_upload::guess_headers($first));
        $this->add($headers);

        $menu = manual_upload::get_column_menu();
        $guesses = manual_upload::guess_columns($rows, (bool)$this->get_element('headers')->get_value());
        foreach (array_keys($first) as $index) {
            $label = get_string('source_manual_upload_column_number', 'tool_muprog', $index + 1);
            $column = (new select('column_' . $index, $label, $menu))
                ->set_default($guesses[$index] ?? manual_upload::COLUMN_IGNORE);
            $this->add($column);
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new reload('back', get_string('muform_back', 'tool_mulib')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * First rows of the data as they were read.
     *
     * @param array $rows
     * @return string html
     */
    private function render_data(array $rows): string {
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mb-0';
        foreach (array_keys($rows[0]) as $index) {
            $table->head[] = get_string('source_manual_upload_column_number', 'tool_muprog', $index + 1);
        }
        $shown = array_slice($rows, 0, manual_upload::DATA_PREVIEW_ROWS);
        foreach ($shown as $row) {
            $table->data[] = array_map('s', $row);
        }
        $html = \core\output\html_writer::table($table);
        if (count($rows) > count($shown)) {
            $more = count($rows) - count($shown);
            $html .= \core\output\html_writer::div(
                get_string('source_manual_upload_more_rows', 'tool_muprog', $more),
                'text-muted'
            );
        }
        return $html;
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $rows = $this->get_extra_data()['rows'];
        $map = [];
        foreach (array_keys($rows[0]) as $index) {
            $map[$index] = $data['column_' . $index] ?? manual_upload::COLUMN_IGNORE;
        }
        $columns = ['headers' => !empty($data['headers']), 'map' => $map];
        foreach (manual_upload::check_columns($rows, $columns) as $name => $errorcode) {
            $allerrors[$name][] = get_string($errorcode, 'tool_muprog');
        }
    }
}
