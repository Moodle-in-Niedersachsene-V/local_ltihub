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
 * Raised when new template activities are copied into an existing hub course.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hub_course_updated extends \core\event\base {
    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_ltihub_map';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('eventhubcourseupdated', 'local_ltihub');
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '{$this->userid}' copied {$this->other['count']} new template activities " .
            "into the hub course with id '{$this->courseid}'.";
    }

    #[\Override]
    public function get_url(): \moodle_url {
        return new \moodle_url('/course/view.php', ['id' => $this->courseid]);
    }

    #[\Override]
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['count'])) {
            throw new \coding_exception('The \'count\' value must be set in other.');
        }
    }
}
