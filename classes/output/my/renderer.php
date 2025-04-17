<?php
// This file is part of Programs for Moodle™.
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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\output\my;

use tool_muprog\local\allocation;
use tool_muprog\local\program;
use tool_muprog\local\util;
use tool_muprog\local\content\item,
    tool_muprog\local\content\top,
    tool_muprog\local\content\set,
    tool_muprog\local\content\course,
    tool_muprog\local\content\training;
use stdClass, moodle_url;

/**
 * Program catalogue renderer.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render program.
     *
     * @param stdClass $program
     * @return string
     */
    public function render_program(\stdClass $program): string {
        global $CFG;

        $context = \context::instance_by_id($program->contextid);
        $fullname = format_string($program->fullname);
        $programicon = $this->output->pix_icon('program', '', 'tool_muprog');

        $description = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php', $context->id, 'tool_muprog', 'description', $program->id);
        $description = format_text($description, $program->descriptionformat, ['context' => $context]);

        $tagsdiv = '';
        if ($CFG->usetags) {
            $tags = \core_tag_tag::get_item_tags('tool_muprog', 'program', $program->id);
            if ($tags) {
                $tagsdiv = $this->output->tag_list($tags, '', 'program-tags');
            }
        }

        $programimage = '';
        $presentation = (array)json_decode($program->presentationjson);
        if (!empty($presentation['image'])) {
            $imageurl = \moodle_url::make_file_url("$CFG->wwwroot/pluginfile.php",
                '/' . $context->id . '/tool_muprog/image/' . $program->id . '/'. $presentation['image'], false);
            $programimage = '<div class="float-end programimage">' . \html_writer::img($imageurl, '') . '</div>';
        }

        $result = '';
        $result .= <<<EOT
<div class="programbox clearfix" data-programid="$program->id">
  $programimage
  <div class="info">
  <div class="info">
    <h2 class="programname">{$programicon}{$fullname}</h2>
  </div>$tagsdiv
  <div class="content">
    <div class="summary">$description</div>
  </div>
</div>
EOT;

        return $result;
    }

    /**
     * Render allocation.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return string
     */
    public function render_user_allocation(stdClass $program, stdClass $source, stdClass $allocation): string {
        $strnotset = get_string('notset', 'tool_muprog');
        $sourceclass = allocation::get_source_classname($source->type);

        $details = [];

        $details[] = ['property' => get_string('programstatus', 'tool_muprog'),
            'value' => allocation::get_completion_status_html($program, $allocation)];
        $details[] = ['property' => get_string('source', 'tool_muprog'),
            'value' => $sourceclass::render_allocation_source($program, $source, $allocation)];
        $details[] = ['property' => get_string('allocationdate', 'tool_muprog'),
            'value' => userdate($allocation->timeallocated)];
        $details[] = ['property' => get_string('programstart', 'tool_muprog'),
            'value' => userdate($allocation->timestart)];
        $details[] = ['property' => get_string('programdue', 'tool_muprog'),
            'value' => (isset($allocation->timedue) ? userdate($allocation->timedue) : $strnotset)];
        $details[] = ['property' => get_string('programend', 'tool_muprog'),
            'value' => (isset($allocation->timeend) ? userdate($allocation->timeend) : $strnotset)];
        $details[] = ['property' => get_string('completiondate', 'tool_muprog'),
            'value' => (isset($allocation->timecompleted) ? userdate($allocation->timecompleted) : $strnotset)];
        $handler = \tool_muprog\customfield\fields_handler::create();
        foreach ($handler->get_instance_data($program->id) as $data) {
            $details[] = ['property' => $data->get_field()->get('name'), 'value' => $data->export_value()];
        }

        return $this->output->render_from_template('tool_mulib/entity_details', ['details' => $details]);
    }

    /**
     * Render progress.
     *
     * @param stdClass $program
     * @param stdClass $allocation
     * @return string
     */
    public function render_user_progress(stdClass $program, stdClass $allocation): string {
        global $DB;

        $top = program::load_content($program->id);

        $rows = [];
        $renderercolumns = function(item $item, $itemdepth) use (&$renderercolumns, &$rows, $allocation, &$DB): void {
            $fullname = $item->get_fullname();
            $id = $item->get_id();
            $padding = str_repeat('&nbsp;', $itemdepth * 6);

            if ($item instanceof set) {
                $completiontype = $item->get_sequencetype_info();
            } else if ($item instanceof training) {
                $completiontype = $item->get_training_progress($allocation);
            } else {
                $completiontype = '';
            }
            if ($completiondelay = $item->get_completiondelay()) {
                if ($completiontype !== '') {
                    $completiontype .= '<br />';
                }
                $completiontype .= '<small>' . get_string('completiondelay', 'tool_muprog') . ': ' . util::format_duration($completiondelay) . '</small>';
            }

            if ($item instanceof course) {
                $courseid = $item->get_courseid();
                $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
                if ($coursecontext) {
                    $canaccesscourse = false;
                    if (has_capability('moodle/course:view', $coursecontext)) {
                        $canaccesscourse = true;
                    } else {
                        $course = get_course($courseid);
                        if ($course && can_access_course($course, null, '', true)) {
                            $canaccesscourse = true;
                        }
                    }
                    if ($canaccesscourse) {
                        $detailurl = new \moodle_url('/course/view.php', ['id' => $courseid]);
                        $fullname = \html_writer::link($detailurl, $fullname);
                    }
                } else {
                    $fullname .= ' <span class="badge badge-danger">' . get_string('errorcoursemissing', 'tool_muprog') . '</span>';
                }
            }

            if ($item instanceof top) {
                $itemname = $this->output->pix_icon('itemtop', get_string('program', 'tool_muprog'), 'tool_muprog') . '&nbsp;' . $fullname;
            } else if ($item instanceof course) {
                $itemname = $padding . $this->output->pix_icon('itemcourse', get_string('course'), 'tool_muprog') . $fullname;
            } else if ($item instanceof training) {
                $itemname = $padding . $this->output->pix_icon('itemtraining', get_string('training', 'tool_muprog'), 'tool_muprog') . $fullname;
            } else {
                $itemname = $padding . $this->output->pix_icon('itemset', get_string('set', 'tool_muprog'), 'tool_muprog') . $fullname;
            }

            if ($item instanceof top) {
                $points = '';
            } else {
                $points = $item->get_points();
            }

            $completioninfo = '';
            $completion = $DB->get_record('tool_muprog_completion', ['itemid' => $item->get_id(), 'allocationid' => $allocation->id]);
            if ($completion) {
                $completioninfo = userdate($completion->timecompleted, get_string('strftimedatetimeshort'));
            }

            $row = [$itemname, $points, $completiontype, $completioninfo];

            $rows[] = $row;

            foreach ($item->get_children() as $child) {
                $renderercolumns($child, $itemdepth + 1);
            }
        };
        $renderercolumns($top, 0);

        $table = new \html_table();
        $table->head = [
            get_string('item', 'tool_muprog'),
            get_string('itempoints', 'tool_muprog'),
            get_string('sequencetype', 'tool_muprog'),
            get_string('completiondate', 'tool_muprog'),
        ];
        $table->id = 'program_content';
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $rows;

        $result = $this->output->heading(get_string('tabcontent', 'tool_muprog'), 3);
        $result .= \html_writer::table($table);

        return $result;
    }

    /**
     * Returns body of My programs block.
     *
     * @return string
     */
    public function render_block_content(): string {
        global $DB;

        $allocations = allocation::get_my_allocations();
        if (!$allocations) {
            return '<em>' . get_string('errornomyprograms', 'tool_muprog') . '</em>';
        }

        $programicon = $this->output->pix_icon('program', '', 'tool_muprog');
        $strnotset = get_string('notset', 'tool_muprog');
        $dateformat = get_string('strftimedatetimeshort');

        foreach ($allocations as $allocation) {
            $row = [];

            $program = $DB->get_record('tool_muprog_program', ['id' => $allocation->programid]);
            $fullname = $programicon . format_string($program->fullname);
            $detailurl = new moodle_url('/admin/tool/muprog/catalogue/program.php', ['id' => $program->id]);
            $fullname = \html_writer::link($detailurl, $fullname);
            $row[] = $fullname;

            $row[] = \tool_muprog\local\allocation::get_completion_status_html($program, $allocation);

            $row[] = userdate($allocation->timestart, $dateformat);

            $row[] = (isset($allocation->timedue) ? userdate($allocation->timedue, $dateformat) : $strnotset);

            $row[] = (isset($allocation->timeend) ? userdate($allocation->timeend, $dateformat) : $strnotset);

            $data[] = $row;
        }

        $table = new \html_table();
        $table->head = [get_string('programname', 'tool_muprog'), get_string('programstatus', 'tool_muprog'),
            get_string('programstart', 'tool_muprog'), get_string('programdue', 'tool_muprog'),
            get_string('programend', 'tool_muprog')];
        $table->attributes['class'] = 'admintable generaltable';
        $table->data = $data;
        return \html_writer::table($table);
    }

    /**
     * Returns footer of My programs block.
     *
     * @return string
     */
    public function render_block_footer(): string {
        $url = \tool_muprog\local\catalogue::get_catalogue_url();
        if ($url) {
            return '<div class="float-end">' . \html_writer::link($url, get_string('catalogue', 'tool_muprog')) . '</div>';
        }
        return '';
    }
}
