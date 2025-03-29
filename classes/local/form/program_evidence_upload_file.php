<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

use tool_muprog\local\source\manual;

/**
 * Upload user program completions.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_evidence_upload_file extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $context = $this->_customdata['context'];

        $mform->addElement('filepicker', 'csvfile', get_string('evidenceupload_csvfile', 'tool_muprog'));
        $mform->addRule('csvfile', null, 'required');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') === ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $program->id);

        $this->add_action_buttons(true, get_string('continue'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $USER;
        $errors = parent::validation($data, $files);

        // File validation is bad in mforms, so work around it here.
        if (empty($data['csvfile'])) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }
        $draftid = $data['csvfile'];
        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);
        $files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false);
        if (!$files) {
            $errors['csvfile'] = get_string('required');
            return $errors;
        }
        $file = reset($files);
        $content = $file->get_content();
        $content = trim($content);
        if (!$content) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        $iid = \csv_import_reader::get_new_iid('programuploadotherevidence');
        $cir = new \csv_import_reader($iid, 'programuploadotherevidence');

        $readcount = $cir->load_csv_content($content, $data['encoding'], $data['delimiter_name']);
        $columns = $cir->get_columns();
        $csvloaderror = $cir->get_error();
        unset($content);

        if (!is_null($csvloaderror)) {
            $errors['csvfile'] = $csvloaderror;
            return $errors;
        } else if (!$readcount || !$columns) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        if ($errors) {
            return $errors;
        }

        $cir = new \csv_import_reader($iid, 'programuploadotherevidence');
        $cir->init();
        $filedata = [];
        $filedata[] = array_map('trim', $columns);
        while ($line = $cir->next()) {
            $filedata[] = array_map('trim', $line);
        }
        $cir->close();

        if (!$filedata) {
            $errors['csvfile'] = get_string('error');
            return $errors;
        }

        $cir->cleanup(true);

        \tool_muprog\local\util::store_uploaded_data($data['csvfile'], $filedata);

        return $errors;
    }
}
