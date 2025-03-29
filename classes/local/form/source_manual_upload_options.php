<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

/**
 * Allocate users via file upload.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_manual_upload_options extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $source = $this->_customdata['source'];
        $context = $this->_customdata['context'];
        $csvfile = $this->_customdata['csvfile'];
        $filedata = $this->_customdata['filedata'];

        $preview = new \html_table();
        $preview->data = [];
        $i = 0;
        foreach ($filedata as $row) {
            $i++;
            if ($i > 5) {
                $preview->data[] = array_fill(0, count($row), '...');
                break;
            }
            $preview->data[] = array_map('s', $row);
        }
        $mform->addElement('static', 'preview', get_string('preview'), \html_writer::table($preview));

        $fileoptions = reset($filedata);
        $mform->addElement('select', 'usercolumn', get_string('source_manual_usercolumn', 'tool_muprog'), $fileoptions);
        $firstcolumn = reset($fileoptions);

        $options = [
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
        ];
        $mform->addElement('select', 'usermapping', get_string('source_manual_usermapping', 'tool_muprog'), $options);
        if (isset($options[$firstcolumn])) {
            $mform->setDefault('usermapping', $firstcolumn);
        }

        $mform->addElement('advcheckbox', 'hasheaders', get_string('source_manual_hasheaders', 'tool_muprog'));
        if (isset($options[$filedata[0][0]])) {
            $mform->setDefault('hasheaders', 1);
        }

        $options = [-1 => get_string('choose')] + $fileoptions;
        $mform->addElement('select', 'timestartcolumn', get_string('source_manual_timestartcolumn', 'tool_muprog'), $options);
        $mform->addElement('select', 'timeduecolumn', get_string('source_manual_timeduecolumn', 'tool_muprog'), $options);
        $mform->addElement('select', 'timeendcolumn', get_string('source_manual_timeendcolumn', 'tool_muprog'), $options);

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $mform->addElement('hidden', 'csvfile');
        $mform->setType('csvfile', PARAM_INT);
        $mform->setDefault('csvfile', $csvfile);

        $this->add_action_buttons(true, get_string('source_manual_uploadusers', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $usedfields = [];

        $columns = ['timestartcolumn', 'timeduecolumn', 'timeendcolumn', 'usermapping'];
        foreach ($columns as $column) {
            if ($data[$column] != -1 && in_array($data[$column], $usedfields)) {
                $errors[$column] = get_string('columnusedalready', 'tool_muprog');
            } else {
                $usedfields[] = $data[$column];
            }
        }

        return $errors;
    }
}
