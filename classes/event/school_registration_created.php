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
 * Raised when the platform registration for a school instance is created on the hub.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school_registration_created extends \core\event\base {
    #[\Override]
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'enrol_lti_app_registration';
    }

    #[\Override]
    public static function get_name(): string {
        return get_string('eventschoolregistrationcreated', 'local_ltihub');
    }

    #[\Override]
    public function get_description(): string {
        return "The user with id '{$this->userid}' created the platform registration with id '{$this->objectid}' " .
            "for the school instance '{$this->other['schoolname']}'.";
    }

    #[\Override]
    protected function validate_data(): void {
        parent::validate_data();
        if (!isset($this->other['schoolname'])) {
            throw new \coding_exception('The \'schoolname\' value must be set in other.');
        }
    }
}
