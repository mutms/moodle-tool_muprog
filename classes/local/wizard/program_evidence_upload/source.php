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

namespace tool_muprog\local\wizard\program_evidence_upload;

use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\wizardbuttons;
use tool_mulib\muform\element\wizardcontinue;
use tool_mulib\muform\form;
use tool_muprog\local\evidence_upload;

/**
 * Completion evidence upload, stage one: where the CSV data comes from.
 *
 * The stage is locked once completed, extra data 'rows' then shows the read data instead.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source extends form {
    use \tool_mulib\muform\util\import_source_trait;

    #[\Override]
    protected function definition(): void {
        $rows = $this->get_extra_data()['rows'] ?? null;
        if ($rows !== null) {
            $this->add(new info('info', '', get_string('import_source_locked', 'tool_mulib', count($rows))));
            $this->add(new inforawhtml('datapreview', '', columns::render_data($rows)));
        } else {
            $this->add(new info('info', '', get_string('evidenceupload_source_info', 'tool_muprog')));
            $this->add_import_source_elements();
        }

        $this->add(new wizardbuttons());
        $this->add(new wizardcontinue(), wizardbuttons::NAME);
        $this->add(new cancel(), wizardbuttons::NAME);
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
        if (isset($this->get_extra_data()['rows'])) {
            // Locked, nothing is submitted.
            return;
        }
        // Only a check, the page stores what the upload needs.
        $rows = $this->validate_import_source($data, $allerrors);
        if ($rows === null) {
            return;
        }
        if ($errorcode = evidence_upload::check_rows($rows)) {
            $allerrors['sourcefile'][] = get_string($errorcode, 'tool_muprog');
        }
    }
}
