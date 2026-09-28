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
use tool_mulib\muform\element\section;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_muprog\local\program;
use tool_muprog\local\util;

/**
 * Import allocation settings - confirmation step.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_allocation_import_confirmation extends form {
    #[\Override]
    protected function definition(): void {
        global $DB, $PAGE;

        $targetprogram = $this->get_extra_data()['targetprogram'];
        $fromprogram = $this->get_extra_data()['fromprogram'];
        $fromcontext = \context::instance_by_id($fromprogram->contextid);

        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        $a = new \stdClass();
        $a->fullname = format_string($fromprogram->fullname);
        $a->idnumber = s($fromprogram->idnumber);
        $a->category = $fromcontext->get_context_name(false);
        $message = get_string('importprogramallocationconfirmation', 'tool_muprog', $a);
        $message = markdown_to_html($message);
        $message = $renderer->notification($message, \core\notification::INFO);
        $this->add(new inforawhtml('confirmation', '', $message));

        $this->add(new section('allocationheading', get_string('allocations', 'tool_muprog')));
        $a = $fromprogram->timeallocationstart ? userdate($fromprogram->timeallocationstart) : get_string('notset', 'tool_muprog');
        $importallocationstart = new checkbox('importallocationstart', get_string('importallocationstart', 'tool_muprog', $a));
        $this->add($importallocationstart, 'allocationheading');
        $a = $fromprogram->timeallocationend ? userdate($fromprogram->timeallocationend) : get_string('notset', 'tool_muprog');
        $importallocationend = new checkbox('importallocationend', get_string('importallocationend', 'tool_muprog', $a));
        $this->add($importallocationend, 'allocationheading');

        $this->add(new section('schedulingheading', get_string('scheduling', 'tool_muprog')));
        $dates = [
            'importprogramstart' => [$fromprogram->startdatejson, program::get_program_startdate_types()],
            'importprogramdue' => [$fromprogram->duedatejson, program::get_program_duedate_types()],
            'importprogramend' => [$fromprogram->enddatejson, program::get_program_enddate_types()],
        ];
        foreach ($dates as $name => [$json, $types]) {
            $date = (object)json_decode($json);
            if ($date->type === 'date') {
                $text = userdate($date->date);
            } else if ($date->type === 'delay') {
                $text = $types[$date->type] . ' - ' . util::format_delay($date->delay);
            } else {
                $text = $types[$date->type];
            }
            $this->add(new checkbox($name, get_string($name, 'tool_muprog', (string)$text)), 'schedulingheading');
        }

        $this->add(new section('sourcesheading', get_string('allocationsources', 'tool_muprog')));
        /** @var \tool_muprog\local\source\base[] $sourceclasses */
        $sourceclasses = \tool_muprog\local\allocation::get_source_classes();
        foreach ($sourceclasses as $sourcetype => $sourceclass) {
            if (!$sourceclass::is_import_allowed($fromprogram, $targetprogram)) {
                continue;
            }

            $source = $DB->get_record('tool_muprog_source', ['type' => $sourcetype, 'programid' => $fromprogram->id]);
            if (!$source) {
                $source = null;
            }

            // Status details are HTML, labels are plain text.
            $status = trim(html_to_text($sourceclass::render_status_details($fromprogram, $source), 0, false));
            $label = $sourceclass::get_name() . ' (' . $status . ')';
            $this->add(new checkbox('importsource' . $sourcetype, $label), 'sourcesheading');
        }

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('importprogramallocation', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    #[\Override]
    protected function validation(array $data, array &$allerrors): void {
        $targetprogram = clone($this->get_extra_data()['targetprogram']);
        $fromprogram = $this->get_extra_data()['fromprogram'];

        // Make sure the new start and end dates are valid.
        if ($data['importallocationstart']) {
            $targetprogram->timeallocationstart = $fromprogram->timeallocationstart;
        }
        if ($data['importallocationend']) {
            $targetprogram->timeallocationend = $fromprogram->timeallocationend;
        }
        if (
            $targetprogram->timeallocationstart && $targetprogram->timeallocationend
            && $targetprogram->timeallocationstart >= $targetprogram->timeallocationend
        ) {
            if ($data['importallocationstart']) {
                $allerrors['importallocationstart'][] = get_string('error');
            }
            if ($data['importallocationend']) {
                $allerrors['importallocationend'][] = get_string('error');
            }
        }
    }
}
