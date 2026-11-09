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

namespace local_ltihub\local;

use context_system;
use local_ltihub\form\create_form;
use moodle_url;

/**
 * The "create your hub course" box shown above the content selection of enrol_lti.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deeplinking_panel {
    /**
     * Render the box for the current deep linking launch.
     *
     * @return string HTML, empty if the teacher already has a hub course or may not create one.
     */
    public static function render(): string {
        global $OUTPUT;

        $launch = launch_context::from_session();
        if (!$launch || !$launch->is_instructor() || !$launch->has_platform_context()) {
            return '';
        }
        if (!has_capability('local/ltihub:createhubcourse', context_system::instance())) {
            return '';
        }
        if ($map = course_provisioner::get_mapping($launch)) {
            return self::render_update_box($map);
        }

        $title = s($launch->get_platform_contexttitle());
        $templates = course_provisioner::get_templates();
        if (!$templates) {
            return $OUTPUT->notification(get_string('notemplates', 'local_ltihub'), 'warning');
        }

        $form = new create_form(new moodle_url('/local/ltihub/create.php'), ['templates' => $templates]);
        return \html_writer::div(
            $OUTPUT->heading(get_string('createheading', 'local_ltihub'), 3) .
                \html_writer::tag('p', get_string('createintro', 'local_ltihub', $title)) .
                $form->render(),
            'local_ltihub-createpanel border rounded p-3 mb-4'
        );
    }

    /**
     * Render the box that offers template activities added after the hub course was created.
     *
     * @param \stdClass $map Mapping record of the teacher.
     * @return string HTML, empty if nothing is missing.
     */
    protected static function render_update_box(\stdClass $map): string {
        global $OUTPUT;
        $missing = course_provisioner::get_missing_template_cms($map);
        if (!$missing) {
            return '';
        }
        $names = array_map(fn(\cm_info $cm) => $cm->get_formatted_name(), $missing);
        return \html_writer::div(
            $OUTPUT->heading(get_string('updateheading', 'local_ltihub'), 3) .
                \html_writer::tag('p', get_string('updateintro', 'local_ltihub', implode(', ', $names))) .
                $OUTPUT->single_button(
                    new moodle_url('/local/ltihub/sync.php'),
                    get_string('updatebutton', 'local_ltihub'),
                    'post',
                    ['type' => \single_button::BUTTON_PRIMARY]
                ),
            'local_ltihub-updatepanel border rounded p-3 mb-4'
        );
    }
}
