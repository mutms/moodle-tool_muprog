<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_muprog\local\form;

use tool_muprog\local\content\set;
use tool_muprog\local\content\course;
use tool_muprog\local\content\training;
use tool_muprog\local\content\top;

/**
 * Delete program content item.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_delete extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $item = $this->_customdata['item'];

        $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->setDefault('fullname', format_string($item->get_fullname()));
        $mform->freeze('fullname');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $item->get_id());

        if ($item instanceof course) {
            $deletestr = get_string('deletecourse', 'tool_muprog');
        } else if ($item instanceof training) {
            $deletestr = get_string('deletetraining', 'tool_muprog');
        } else {
            $deletestr = get_string('deleteset', 'tool_muprog');
        }

        $this->add_action_buttons(true, $deletestr);
    }
}
