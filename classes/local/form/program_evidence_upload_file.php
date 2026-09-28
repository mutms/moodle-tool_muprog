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

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\filemanager;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Upload user program completions.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_evidence_upload_file extends form {
    #[\Override]
    protected function definition(): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $csvfile = new filemanager('csvfile', get_string('evidenceupload_csvfile', 'tool_muprog'), 1);
        $csvfile->set_required(true);
        $this->add($csvfile);

        $choices = \csv_import_reader::get_delimiter_list();
        $delimiter = new select('delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $delimiter->set_default('cfg');
        } else if (get_string('listsep', 'langconfig') === ';') {
            $delimiter->set_default('semicolon');
        } else {
            $delimiter->set_default('comma');
        }
        $this->add($delimiter);

        $encoding = new select('encoding', get_string('encoding', 'tool_uploaduser'), \core_text::get_encodings());
        $encoding->set_default('UTF-8');
        $this->add($encoding);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $files = $this->get_element('csvfile')->get_files();
        if (!$files) {
            return;
        }
        $file = reset($files);
        $content = trim($file->get_content());
        if ($content === '') {
            $allerrors['csvfile'][] = get_string('error');
            return;
        }

        $iid = \csv_import_reader::get_new_iid('programuploadotherevidence');
        $cir = new \csv_import_reader($iid, 'programuploadotherevidence');
        $readcount = $cir->load_csv_content($content, $data['encoding'], $data['delimiter_name']);
        $columns = $cir->get_columns();
        $csvloaderror = $cir->get_error();
        unset($content);

        if ($csvloaderror !== null) {
            $allerrors['csvfile'][] = $csvloaderror;
            $cir->cleanup(true);
            return;
        }
        if (!$readcount || !$columns) {
            $allerrors['csvfile'][] = get_string('error');
            $cir->cleanup(true);
            return;
        }

        $filedata = [array_map('trim', $columns)];
        $cir->init();
        while ($line = $cir->next()) {
            $filedata[] = array_map('trim', $line);
        }
        $cir->close();
        $cir->cleanup(true);

        // Rows are stored in the user's upload area keyed by the draft item id, the next step reads them from there.
        \tool_muprog\local\util::store_uploaded_data((int)$data['csvfile'], $filedata);
    }
}
