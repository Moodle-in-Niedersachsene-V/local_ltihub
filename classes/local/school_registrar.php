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

use enrol_lti\local\ltiadvantage\repository\application_registration_repository;
use enrol_lti\local\ltiadvantage\repository\context_repository;
use enrol_lti\local\ltiadvantage\repository\deployment_repository;
use enrol_lti\local\ltiadvantage\repository\resource_link_repository;
use enrol_lti\local\ltiadvantage\repository\user_repository;
use enrol_lti\local\ltiadvantage\service\application_registration_service;
use moodle_exception;
use moodle_url;

/**
 * Prepares the hub for a new school instance.
 *
 * Creates the draft platform registration of a school instance; the school admin completes it
 * by pasting the registration URL into "Manage tools" (LTI Advantage dynamic registration).
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class school_registrar {
    /**
     * Create the draft registration for a school instance.
     *
     * @param string $schoolname Name of the school instance, used as registration and category name.
     * @return array Keys regid and regurl.
     */
    public static function create(string $schoolname): array {
        $schoolname = trim($schoolname);
        if ($schoolname === '') {
            throw new moodle_exception('cli_noname', 'local_ltihub');
        }
        if (!enrol_is_enabled('lti') || !is_enabled_auth('lti')) {
            throw new moodle_exception('cli_ltidisabled', 'local_ltihub');
        }

        $service = new application_registration_service(
            new application_registration_repository(),
            new deployment_repository(),
            new resource_link_repository(),
            new context_repository(),
            new user_repository()
        );
        $registration = $service->create_draft_application_registration((object) ['name' => $schoolname]);

        \local_ltihub\event\school_registration_created::create([
            'context' => \context_system::instance(),
            'objectid' => $registration->get_id(),
            'other' => ['schoolname' => $schoolname],
        ])->trigger();

        return [
            'regid' => $registration->get_id(),
            'regurl' => (new moodle_url('/enrol/lti/register.php', ['token' => $registration->get_uniqueid()]))->out(false),
        ];
    }
}
