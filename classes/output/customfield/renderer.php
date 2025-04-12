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

namespace tool_muprog\output\customfield;

/**
 * Program custom field renderer.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Render custom field info.
     * @param int $programid
     * @return string
     */
    public function render_customfields(int $programid): string {
        $content = '';
        $handler = \tool_muprog\customfield\fields_handler::create();
        $datas = $handler->get_instance_data($programid);
        foreach ($datas as $data) {
            $content .= '<dt class="col-3">'.$data->get_field()->get('name').'</dt><dd class="col-9">'.$data->export_value().'</dd>';
        }

        return $content;
    }
}
