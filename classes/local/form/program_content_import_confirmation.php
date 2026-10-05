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
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Add program content items confirmation.
 *
 * @package    tool_muprog
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_content_import_confirmation extends form {
    #[\Override]
    protected function definition(): void {
        global $PAGE;

        $fromprogram = $this->get_extra_data()['fromprogram'];
        $fromcontext = \context::instance_by_id($fromprogram->contextid);

        $renderer = $PAGE->get_renderer('core', null, RENDERER_TARGET_GENERAL);

        $a = new \stdClass();
        $a->fullname = format_string($fromprogram->fullname);
        $a->idnumber = s($fromprogram->idnumber);
        $a->category = $fromcontext->get_context_name(false);
        $message = get_string('importprogramcontentconfirmation', 'tool_muprog', $a);
        $message = markdown_to_html($message);
        $message = $renderer->notification($message, \core\notification::INFO);
        $this->add(new inforawhtml('confirmation', '', $message));

        /** @var \tool_muprog\output\management\renderer $managementoutput */
        $managementoutput = $PAGE->get_renderer('tool_muprog', 'management', RENDERER_TARGET_GENERAL);
        $this->add(new inforawhtml('content', '', $managementoutput->render_program_content($fromprogram)));

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('importprogramcontent', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }
}
