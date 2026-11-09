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

namespace local_ltihub\event;

/**
 * Raised when a teacher creates a hub course from a template.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hub_course_created extends \core\event\base {
    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_ltihub_map';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('eventhubcoursecreated', 'local_ltihub');
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '{$this->userid}' created the hub course with id '{$this->courseid}' " .
            "from the template course with id '{$this->other['templateid']}'.";
    }

    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    #[\Override]
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['templateid'])) {
            throw new \coding_exception('The \'templateid\' value must be set in other.');
        }
    }
}
