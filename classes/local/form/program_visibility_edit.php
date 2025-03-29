<?php
// This file is part of Programs for Moodle™.
// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon
// phpcs:disable moodle.Files.LineLength.TooLong

namespace tool_muprog\local\form;

use tool_muprog\local\management;

/**
 * Edit program visibility.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_visibility_edit extends \tool_mulib\local\dialog_form {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $context = $this->_customdata['context'];

        $mform->addElement('select', 'public', get_string('public', 'tool_muprog'), [0 => get_string('no'), 1 => get_string('yes')]);
        $mform->setDefault('public', $data->public);
        $mform->addHelpButton('public', 'public', 'tool_muprog');

        $options = ['contextid' => $context->id, 'multiple' => true];
        /** @var \MoodleQuickForm_cohort $cohortsel */
        $cohortsel = $mform->addElement('cohort', 'cohorts', get_string('cohorts', 'tool_muprog'), $options);
        $mform->addHelpButton('cohorts', 'cohorts', 'tool_muprog');
        // WARNING: The cohort element is not great at all, work around the current value problems here in a very hacky way.
        $cohorts = management::fetch_current_cohorts_menu($data->id);
        $cohorts = array_map('format_string', $cohorts);
        foreach ($cohorts as $cid => $cname) {
            $cohortsel->addOption($cname, $cid);
        }
        $cohortsel->setSelected(array_keys($cohorts));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $data->id);

        $this->add_action_buttons(true, get_string('updateprogram', 'tool_muprog'));
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        $currentcohorts = management::fetch_current_cohorts_menu($data['id']);
        foreach ($data['cohorts'] as $cohortid) {
            if (isset($currentcohorts[$cohortid])) {
                // Allow current.
                continue;
            }
            $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'id, contextid, visible');
            if ($cohort->visible) {
                // NOTE: add some tenant restrictions here if necessary.
                continue;
            }
            $cohortcontext = \context::instance_by_id($cohort->contextid);
            if (has_capability('moodle/cohort:view', $cohortcontext)) {
                continue;
            }
            $errors['cohorts'] = get_string('error');
            break;
        }

        return $errors;
    }
}
