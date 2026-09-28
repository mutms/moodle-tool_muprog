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

use tool_certificate\certificate;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\cancel;
use tool_mulib\muform\element\datetime;
use tool_mulib\muform\element\duration;
use tool_mulib\muform\element\inforawhtml;
use tool_mulib\muform\element\select;
use tool_mulib\muform\element\submit;
use tool_mulib\muform\form;
use tool_mulib\muform\validator\required_if_visible;

/**
 * Edit program certificate settings.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_certificate_edit extends form {
    #[\Override]
    protected function definition(): void {
        global $OUTPUT;

        $current = $this->get_current_data();
        $context = $this->get_extra_data()['context'];

        $templates = self::get_templates($context, $current['templateid'] ? (int)$current['templateid'] : null);
        $templateoptions = ['' => get_string('certificatetemplatechoose', 'tool_muprog')] + $templates;
        $templateid = new select('templateid', get_string('certificatetemplate', 'tool_certificate'), $templateoptions);
        $templateid->set_required(true);
        $this->add($templateid);

        if (\tool_certificate\permission::can_manage_anywhere()) {
            // Opens in a new window, the form must not be lost.
            $manage = get_string('managetemplates', 'tool_certificate');
            $link = \html_writer::link(
                new \core\url('/admin/tool/certificate/manage_templates.php'),
                $OUTPUT->pix_icon('i/settings', $manage) . ' ' . $manage,
                ['target' => '_blank', 'class' => 'small']
            );
            $this->add(new inforawhtml('managetemplates', '', $link));
        }

        $expirydateoptions = [
            certificate::DATE_EXPIRATION_NEVER => get_string('never', 'tool_certificate'),
            certificate::DATE_EXPIRATION_ABSOLUTE => get_string('selectdate', 'tool_certificate'),
            certificate::DATE_EXPIRATION_AFTER => get_string('after', 'tool_certificate'),
        ];
        $this->add(new select('expirydatetype', get_string('expirydate', 'tool_certificate'), $expirydateoptions));

        $absolute = new datetime('expirydateabsolute', get_string('selectdate', 'tool_certificate'));
        $absolute->set_required_marker(true);
        $absolute->add_validator(new required_if_visible());
        $this->add($absolute);
        $this->get_display_manager()->hide_if('expirydateabsolute', 'expirydatetype', 'neq', certificate::DATE_EXPIRATION_ABSOLUTE);

        $relative = new duration('expirydaterelative', get_string('after', 'tool_certificate'), ['w', 'd']);
        $relative->set_required_marker(true);
        $relative->add_validator(new required_if_visible());
        $this->add($relative);
        $this->get_display_manager()->hide_if('expirydaterelative', 'expirydatetype', 'neq', certificate::DATE_EXPIRATION_AFTER);

        $this->add(new buttons('buttons'));
        $this->add(new submit('submit', get_string('program_update', 'tool_muprog')), 'buttons');
        $this->add(new cancel(), 'buttons');
    }

    /**
     * Returns templates.
     *
     * @param \context $context
     * @param int|null $templateid
     * @return array
     */
    public static function get_templates(\context $context, ?int $templateid): array {
        global $DB;

        $templates = [];
        if (!empty($records = \tool_certificate\permission::get_visible_templates($context))) {
            foreach ($records as $record) {
                $templates[$record->id] = format_string($record->name);
            }
        }
        if ($templateid && !isset($templates[$templateid])) {
            $record = $DB->get_record('tool_certificate_templates', ['id' => $templateid]);
            if ($record) {
                $templates[$record->id] = format_string($record->name);
            } else {
                $templates[$templateid] = get_string('error');
            }
        }

        asort($templates);
        return $templates;
    }
}
