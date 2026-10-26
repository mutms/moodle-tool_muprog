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
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Allocation upload, stage three: options and the dry run result.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_upload_options extends form {
    #[\Override]
    protected function definition(): void {
        $extra = $this->get_extra_data();
        $program = $extra['program'];
        $csvdata = $extra['csvdata'];

        $this->add(new info('skipinfo', '', get_string('source_manual_upload_skip_info', 'tool_muprog')));
        foreach (manual_upload::get_skip_menu() as $name => $label) {
            $skip = (new checkbox($name, $label))
                ->set_default(1);
            $this->add($skip);
        }

        // The preview always shows what the options in the form would do right now.
        $options = (object)[];
        foreach (manual_upload::get_option_names() as $name) {
            $options->$name = (int)$this->get_element($name)->get_value();
        }
        $outcomes = manual_upload::check($program, $csvdata, $options);

        $this->add(new inforawhtml('preview', '', $this->render_preview($outcomes)));

        // The upload button comes first so that Enter never skips the dry run of other options.
        $this->add(new buttons('buttons'));
        if (self::is_importable($outcomes)) {
            $this->add(new submit('submit', get_string('source_manual_uploadusers', 'tool_muprog')), 'buttons');
        }
        $this->add(new reload('refresh', get_string('source_manual_upload_refresh', 'tool_muprog')), 'buttons');
        $this->add(new reload('back', get_string('muform_back', 'tool_mulib')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * May the upload run with the outcomes of the dry run?
     *
     * @param array $outcomes
     * @return bool
     */
    private static function is_importable(array $outcomes): bool {
        $allocated = 0;
        foreach ($outcomes as $outcome) {
            if ($outcome->result === manual_upload::RESULT_ERROR) {
                return false;
            }
            if ($outcome->result === manual_upload::RESULT_ALLOCATED) {
                $allocated++;
            }
        }
        return (bool)$allocated;
    }

    /**
     * Counts of the dry run and the first rows of its result.
     *
     * @param array $outcomes
     * @return string html
     */
    private function render_preview(array $outcomes): string {
        global $OUTPUT;

        $counts = [
            manual_upload::RESULT_ALLOCATED => 0,
            manual_upload::RESULT_SKIPPED => 0,
            manual_upload::RESULT_ERROR => 0,
        ];
        foreach ($outcomes as $outcome) {
            $counts[$outcome->result]++;
        }

        $message = get_string('source_manual_upload_counts', 'tool_muprog', (object)$counts);
        $style = $counts[manual_upload::RESULT_ERROR] ? 'error' : 'info';
        $html = $OUTPUT->notification($message, $style, false);

        $labels = [
            manual_upload::RESULT_ALLOCATED => get_string('source_manual_upload_result_allocated', 'tool_muprog'),
            manual_upload::RESULT_SKIPPED => get_string('source_manual_upload_result_skipped', 'tool_muprog'),
            manual_upload::RESULT_ERROR => get_string('source_manual_upload_result_error', 'tool_muprog'),
        ];
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable mb-0';
        $table->head = [
            get_string('user'),
            get_string('status'),
            get_string('description'),
        ];

        // Problems come first, long uploads are not read row by row, the counts above say the rest.
        $ready = fn($outcome) => $outcome->result === manual_upload::RESULT_ALLOCATED;
        usort($outcomes, fn($a, $b) => $ready($a) <=> $ready($b));
        $shown = array_slice($outcomes, 0, manual_upload::PREVIEW_ROWS);
        foreach ($shown as $outcome) {
            $class = $outcome->result === manual_upload::RESULT_ERROR ? 'text-danger' : '';
            $table->data[] = [
                s($outcome->identifier),
                \core\output\html_writer::span($labels[$outcome->result], $class),
                s($outcome->message),
            ];
        }
        $html .= \core\output\html_writer::table($table);
        if (count($outcomes) > count($shown)) {
            $more = count($outcomes) - count($shown);
            $html .= \core\output\html_writer::div(
                get_string('source_manual_upload_more_rows', 'tool_muprog', $more),
                'text-muted'
            );
        }

        return $html;
    }
}
