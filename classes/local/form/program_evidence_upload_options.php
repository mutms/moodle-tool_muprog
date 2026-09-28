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
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\element\textarea;
use tool_mulib\muform\form;

/**
 * Allocate users via file upload.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_evidence_upload_options extends form {
    #[\Override]
    protected function definition(): void {
        $filedata = $this->get_extra_data()['filedata'];

        $preview = new \html_table();
        $preview->data = [];
        foreach (array_values($filedata) as $i => $row) {
            if ($i >= 5) {
                $preview->data[] = array_fill(0, count($row), '...');
                break;
            }
            $preview->data[] = array_map('s', $row);
        }
        $this->add(new inforawhtml('preview', get_string('preview'), \html_writer::table($preview)));

        $fileoptions = array_map('strval', reset($filedata));
        $this->add(new select('usercolumn', get_string('source_manual_usercolumn', 'tool_muprog'), $fileoptions));

        $mappings = [
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
        ];
        $usermapping = new select('usermapping', get_string('source_manual_usermapping', 'tool_muprog'), $mappings);
        $firstcolumn = reset($fileoptions);
        if (isset($mappings[$firstcolumn])) {
            $usermapping->set_default($firstcolumn);
        }
        $this->add($usermapping);

        $hasheaders = new checkbox('hasheaders', get_string('source_manual_hasheaders', 'tool_muprog'));
        $hasheaders->set_default(isset($mappings[$firstcolumn]) ? 1 : 0);
        $this->add($hasheaders);

        $timecompletedcolumn = new select(
            'timecompletedcolumn',
            get_string('completiondate', 'tool_muprog'),
            ['' => get_string('choose')] + $fileoptions
        );
        $timecompletedcolumn->set_required(true);
        $this->add($timecompletedcolumn);

        $options = [-1 => get_string('choose')] + $fileoptions;
        $detailscolumn = new select('detailscolumn', get_string('evidence_details', 'tool_muprog'), $options);
        $detailscolumn->set_default(-1);
        $this->add($detailscolumn);

        $this->add(new textarea('details', get_string('evidence_detailsdefault', 'tool_muprog')));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('evidenceupload', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $usedfields = [];
        $columns = ['usercolumn', 'timecompletedcolumn', 'detailscolumn'];
        foreach ($columns as $column) {
            if ($data[$column] != -1 && in_array($data[$column], $usedfields)) {
                $allerrors[$column][] = get_string('columnusedalready', 'tool_muprog');
            } else {
                $usedfields[] = $data[$column];
            }
        }

        if ($data['detailscolumn'] == -1 && trim($data['details']) === '') {
            $allerrors['details'][] = get_string('required');
        }
    }
}
