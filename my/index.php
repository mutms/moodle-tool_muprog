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

/**
 * My programs.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $USER */

require('../../../../config.php');

$sort = optional_param('sort', 'fullname', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'ASC', PARAM_ALPHA);

require_login();

$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);

if (!enrol_is_enabled('muprog')) {
    redirect(new moodle_url('/'));
}
if (isguestuser()) {
    redirect(new moodle_url('/admin/tool/muprog/catalogue/index.php'));
}

$pageparams = [];
if ($sort !== 'fullname') {
    $pageparams['sort'] = $sort;
}
if ($dir !== 'ASC') {
    $pageparams['dir'] = $dir;
}

$currenturl = new moodle_url('/admin/tool/muprog/my/index.php', $pageparams);

$title = get_string('myprograms', 'tool_muprog');
$PAGE->navigation->extend_for_user($USER);
$PAGE->set_title($title);
$PAGE->set_url($currenturl);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $USER->id]));
$PAGE->navbar->add($title);

$buttons = [];
$manageurl = \tool_muprog\local\management::get_management_url();
if ($manageurl) {
    $buttons[] = html_writer::link($manageurl, get_string('management', 'tool_muprog'), ['class' => 'btn btn-secondary']);
}
$catalogueurl = \tool_muprog\local\catalogue::get_catalogue_url();
if ($catalogueurl) {
    $buttons[] = html_writer::link($catalogueurl, get_string('catalogue', 'tool_muprog'), ['class' => 'btn btn-secondary']);
}
$buttons = implode('&nbsp;', $buttons);
$PAGE->set_button($buttons . $PAGE->button);

echo $OUTPUT->header();

if ($sort === 'idnumber') {
    $orderby = 'idnumber';
} else {
    $orderby = 'fullname';
}
if ($dir === 'ASC') {
    $orderby .= ' ASC';
} else {
    $orderby .= ' DESC';
}

$sql = "SELECT p.*
          FROM {tool_muprog_program} p
          JOIN {tool_muprog_allocation} pa ON pa.programid = p.id
         WHERE p.archived = 0 AND pa.archived = 0
               AND pa.userid = :userid
      ORDER BY $orderby";
$params = ['userid' => $USER->id];
$programs = $DB->get_records_sql($sql, $params);

if (!$programs) {
    echo get_string('errornomyprograms', 'tool_muprog');
    echo $OUTPUT->footer();
    die;
}

$data = [];

$programicon = $OUTPUT->pix_icon('program', '', 'tool_muprog');
$dateformat = get_string('strftimedatetimeshort');
$strnotset = get_string('notset', 'tool_muprog');
$sourceclasses = \tool_muprog\local\allocation::get_source_classes();

foreach ($programs as $program) {
    $allocation = $DB->get_record('tool_muprog_allocation', ['programid' => $program->id, 'userid' => $USER->id]);
    $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid]);
    /** @var \tool_muprog\local\source\base $sourceclass */
    $sourceclass = $sourceclasses[$source->type];
    $pcontext = context::instance_by_id($program->contextid);
    $row = [];
    $fullname = $programicon . format_string($program->fullname);
    $detailurl = new moodle_url('/admin/tool/muprog/my/program.php', ['id' => $program->id]);
    $fullname = html_writer::link($detailurl, $fullname);
    if ($CFG->usetags) {
        $tags = core_tag_tag::get_item_tags('tool_muprog', 'program', $program->id);
        if ($tags) {
            $fullname .= '<br />' . $OUTPUT->tag_list($tags, '', 'program-tags');
        }
    }

    $row[] = $fullname;
    $row[] = s($program->idnumber);
    $description = file_rewrite_pluginfile_urls($program->description, 'pluginfile.php',
        $pcontext->id, 'tool_muprog', 'description', $program->id);
    $row[] = format_text($description, $program->descriptionformat, ['context' => $pcontext]);

    $row[] = userdate($allocation->timestart, $dateformat);
    if ($allocation->timedue) {
        $row[] = userdate($allocation->timedue, $dateformat);
    } else {
        $row[] = $strnotset;
    }
    if ($allocation->timeend) {
        $row[] = userdate($allocation->timeend, $dateformat);
    } else {
        $row[] = $strnotset;
    }

    $row[] = $sourceclass::render_allocation_source($program, $source, $allocation);

    $row[] = \tool_muprog\local\allocation::get_completion_status_html($program, $allocation);

    $data[] = $row;
}

$columns = [];

$column = get_string('programname', 'tool_muprog');
$columndir = ($dir === "ASC" ? "DESC" : "ASC");
$columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
    ['class' => 'iconsort']);
$changeurl = new moodle_url($currenturl);
$changeurl->param('sort', 'fullname');
$changeurl->param('dir', $columndir);
$column = html_writer::link($changeurl, $column);
if ($sort === 'fullname') {
    $column .= $columnicon;
}
$columns[] = $column;

$column = get_string('programidnumber', 'tool_muprog');
$columndir = ($dir === "ASC" ? "DESC" : "ASC");
$columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
    ['class' => 'iconsort']);
$changeurl = new moodle_url($currenturl);
$changeurl->param('sort', 'idnumber');
$changeurl->param('dir', $columndir);
$column = html_writer::link($changeurl, $column);
if ($sort === 'idnumber') {
    $column .= $columnicon;
}
$columns[] = $column;

$columns[] = get_string('description');

$column = get_string('programstart', 'tool_muprog');
$columndir = ($dir === "ASC" ? "DESC" : "ASC");
$columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
    ['class' => 'iconsort']);
$changeurl = new moodle_url($currenturl);
$changeurl->param('sort', 'start');
$changeurl->param('dir', $columndir);
$column = html_writer::link($changeurl, $column);
if ($sort === 'start') {
    $column .= $columnicon;
}
$columns[] = $column;

$column = get_string('duedate', 'tool_muprog');
$columndir = ($dir === "ASC" ? "DESC" : "ASC");
$columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
    ['class' => 'iconsort']);
$changeurl = new moodle_url($currenturl);
$changeurl->param('sort', 'due');
$changeurl->param('dir', $columndir);
$column = html_writer::link($changeurl, $column);
if ($sort === 'due') {
    $column .= $columnicon;
}
$columns[] = $column;

$column = get_string('programend', 'tool_muprog');
$columndir = ($dir === "ASC" ? "DESC" : "ASC");
$columnicon = ($dir === "ASC" ? "sort_asc" : "sort_desc");
$columnicon = $OUTPUT->pix_icon('t/' . $columnicon, get_string(strtolower($columndir)), 'core',
    ['class' => 'iconsort']);
$changeurl = new moodle_url($currenturl);
$changeurl->param('sort', 'end');
$changeurl->param('dir', $columndir);
$column = html_writer::link($changeurl, $column);
if ($sort === 'end') {
    $column .= $columnicon;
}
$columns[] = $column;

$columns[] = get_string('source', 'tool_muprog');

$columns[] = get_string('programstatus', 'tool_muprog');

$table = new html_table();
$table->head = $columns;
$table->id = 'my_programs';
$table->attributes['class'] = 'generaltable';
$table->data = $data;
echo html_writer::table($table);

echo $OUTPUT->footer();
