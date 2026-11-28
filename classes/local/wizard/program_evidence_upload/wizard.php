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

use core\url;
use stdClass;
use tool_mulib\muform\form;
use tool_muprog\local\evidence_upload;
use tool_muprog\local\management;

/**
 * Upload or copy/paste program completion evidence via CSV format.
 *
 * Three stages: source, column meaning, options with a dry run. The parsed rows and the answers
 * live in the wizard state, the source stage is locked once the data were read.
 *
 * @package    tool_muprog
 * @copyright  2026 Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wizard extends \tool_mulib\muform\wizard_page {
    /** @var stdClass program */
    private stdClass $program;
    /** @var \context program context */
    private \context $context;
    /** @var stdClass stored state validated again on every request */
    private stdClass $csvdata;

    /**
     * Load the program, check access.
     *
     * @param int $programid
     */
    public function __construct(int $programid) {
        global $DB;

        $this->program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $this->context = \context::instance_by_id($this->program->contextid);
        require_capability('tool/muprog:manageevidence', $this->context);
    }

    #[\Override]
    protected function get_name(): string {
        // The state belongs to this program, it cannot be replayed against another one.
        return 'tool_muprog_evidence_upload:' . $this->program->id;
    }

    #[\Override]
    protected function get_stages(): array {
        return [
            'source' => get_string('evidenceupload_source', 'tool_muprog'),
            'columns' => get_string('evidenceupload_columns', 'tool_muprog'),
            'options' => get_string('evidenceupload_options', 'tool_muprog'),
        ];
    }

    #[\Override]
    protected function get_locked_stages(): array {
        return ['source'];
    }

    #[\Override]
    protected function is_reset_allowed(): bool {
        return true;
    }

    #[\Override]
    protected function get_page_url(): url {
        return new url('/admin/tool/muprog/management/program_evidence_upload.php', ['programid' => $this->program->id]);
    }

    #[\Override]
    protected function get_return_url(): url {
        return new url('/admin/tool/muprog/management/program_users.php', ['id' => $this->program->id]);
    }

    #[\Override]
    protected function setup_page(): void {
        global $PAGE;

        management::setup_program_page($this->get_page_url(), $this->context, $this->program, 'program_users');
        $PAGE->navbar->add($this->get_heading());
    }

    #[\Override]
    protected function get_heading(): string {
        return get_string('evidenceupload', 'tool_muprog');
    }

    #[\Override]
    protected function get_unavailable_reason(): ?string {
        if ($this->program->archived) {
            return get_string('muform_wizard_unavailable', 'tool_mulib');
        }
        return null;
    }

    #[\Override]
    protected function check_state(): void {
        // Each stage fills one part of the stored state.
        $this->csvdata = evidence_upload::validate($this->state);
    }

    #[\Override]
    protected function is_stage_valid(string $stage): bool {
        return match ($stage) {
            'source' => evidence_upload::is_source_valid($this->csvdata),
            'columns' => evidence_upload::is_columns_valid($this->csvdata),
            'options' => evidence_upload::is_options_valid($this->csvdata),
        };
    }

    #[\Override]
    protected function create_form(string $stage, url $formurl): form {
        if ($stage === 'source') {
            $extra = $this->is_stage_locked($stage) ? ['rows' => $this->csvdata->rows] : [];
            return new source($formurl, [], $extra);
        }
        if ($stage === 'columns') {
            $current = [];
            if ($this->csvdata->columns) {
                $current['headers'] = (int)$this->csvdata->columns['headers'];
                foreach ($this->csvdata->columns['map'] as $index => $value) {
                    $current['column_' . $index] = $value;
                }
            }
            return new columns($formurl, $current, ['rows' => $this->csvdata->rows]);
        }
        // Options of an earlier confirmation are only defaults of the form.
        $extra = ['program' => $this->program, 'csvdata' => $this->csvdata];
        return new options($formurl, $this->csvdata->options, $extra);
    }

    #[\Override]
    protected function submit_stage(string $stage, stdClass $data, form $form): ?url {
        if ($stage === 'source') {
            $csvdata = evidence_upload::save_source($form->get_source_content(), $data->encoding, $data->delimiter_name);
        } else if ($stage === 'columns') {
            $csvdata = evidence_upload::save_columns($this->csvdata, $data);
        } else {
            $csvdata = evidence_upload::save_options($this->csvdata, $data);
        }
        $this->state = (array)$csvdata;
        return null;
    }

    #[\Override]
    protected function finish(): ?url {
        // Everything was answered, update the evidence.
        $counts = evidence_upload::import($this->program, $this->csvdata, (object)$this->csvdata->options);
        $this->wizard->delete();
        if ($counts[evidence_upload::RESULT_ERROR]) {
            \core\notification::error(get_string('evidenceupload_failed', 'tool_muprog', (object)$counts));
        } else {
            \core\notification::success(get_string('evidenceupload_done', 'tool_muprog', (object)$counts));
        }
        return $this->get_return_url();
    }
}
