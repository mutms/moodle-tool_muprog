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
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\checkbox;
use tool_mulib\muform\element\info;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;

/**
 * Upload programs confirmation.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload_options extends form {
    #[\Override]
    protected function definition(): void {
        $filedata = $this->get_extra_data()['filedata'];

        $uploadcount = 0;
        $invalidcount = 0;
        $categoryfail = false;
        foreach ($filedata as $program) {
            if ($program->errors) {
                $invalidcount++;
                continue;
            }
            $uploadcount++;
            if (!$program->contextid) {
                $categoryfail = true;
            }
        }

        $usecategory = new checkbox('usecategory', get_string('upload_usecategory', 'tool_muprog'));
        if ($categoryfail) {
            $usecategory->set_default(0);
            $usecategory->set_frozen(true);
        } else {
            $usecategory->set_default(1);
        }
        $this->add($usecategory);

        $this->add(new select('contextid', get_string('upload_targetcontext', 'tool_muprog'), self::get_category_options()));
        $this->get_display_manager()->hide_if('contextid', 'usecategory', 'checked');

        $this->add(new info('uploadcount', get_string('upload_uploadcount', 'tool_muprog'), (string)$uploadcount));
        $this->add(new info('invalidcount', get_string('upload_invalidcount', 'tool_muprog'), (string)$invalidcount));

        $this->add(new buttons('buttons'));
        if ($uploadcount) {
            $this->add(new submit('submit', get_string('upload', 'tool_muprog')), 'buttons');
        }
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Returns categories.
     *
     * @return array
     */
    protected static function get_category_options(): array {
        $options = [];
        $syscontext = \context_system::instance();
        if (has_capability('tool/muprog:upload', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        $categories = \core_course_category::make_categories_list('tool/muprog:upload');
        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid);
            $options[$catcontext->id] = (string)$categoryname;
        }
        return $options;
    }
}
