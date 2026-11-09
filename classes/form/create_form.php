<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_ltihub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Lets a teacher choose the template for the new hub course.
 *
 * Expects customdata 'templates': course records as returned by course_provisioner::get_templates().
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class create_form extends \moodleform {
    #[\Override]
    protected function definition(): void {
        $mform = $this->_form;
        $templates = $this->_customdata['templates'];

        // With a single template there is nothing to choose.
        if (count($templates) === 1) {
            $template = reset($templates);
            $mform->addElement('hidden', 'templateid', $template->id);
            $mform->setType('templateid', PARAM_INT);
            $this->add_action_buttons(false, get_string('createbutton', 'local_ltihub'));
            return;
        }

        $radios = [];
        foreach ($templates as $template) {
            $context = \context_course::instance($template->id);
            $summary = format_text($template->summary, $template->summaryformat, ['context' => $context]);
            $summary = shorten_text(html_to_text($summary, 0, false), 200);
            $label = format_string($template->fullname, true, ['context' => $context]);
            if ($summary !== '') {
                $label .= ' – ' . s($summary);
            }
            $radios[] = $mform->createElement('radio', 'templateid', '', $label, $template->id);
        }
        $mform->addGroup($radios, 'templategroup', get_string('templatechoice', 'local_ltihub'), '<br>', false);
        $mform->addHelpButton('templategroup', 'templatechoice', 'local_ltihub');
        $mform->addRule('templategroup', get_string('required'), 'required', null, 'client');
        $mform->setType('templateid', PARAM_INT);

        $this->add_action_buttons(false, get_string('createbutton', 'local_ltihub'));
    }

    #[\Override]
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (empty($data['templateid']) || !array_key_exists((int) $data['templateid'], $this->_customdata['templates'])) {
            $errors['templategroup'] = get_string('invalidtemplate', 'local_ltihub');
        }
        return $errors;
    }
}
