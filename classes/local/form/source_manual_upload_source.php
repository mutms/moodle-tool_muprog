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
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Allocation upload, stage one: where the CSV data comes from.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_upload_source extends form {
    use \tool_mulib\muform\util\import_source_trait;

    #[\Override]
    protected function definition(): void {
        $this->add(new info('info', '', get_string('source_manual_upload_source_info', 'tool_muprog')));
        $this->add_import_source_elements();

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('continue')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Uploaded file content or the pasted text, the file wins.
     *
     * @return string
     */
    public function get_source_content(): string {
        return $this->get_import_source_content();
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        // Only a check, the page stores what the upload needs.
        $rows = $this->validate_import_source($data, $allerrors);
        if ($rows === null) {
            return;
        }
        if ($errorcode = manual_upload::check_rows($rows)) {
            $allerrors['sourcefile'][] = get_string($errorcode, 'tool_muprog');
        }
    }
}
