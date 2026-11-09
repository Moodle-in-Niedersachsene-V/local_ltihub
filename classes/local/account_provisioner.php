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

use enrol_lti\local\ltiadvantage\lib\launch_cache_session;
use enrol_lti\local\ltiadvantage\utility\message_helper;
use moodle_url;

/**
 * Creates hub accounts for teachers who start with "Select content".
 *
 * Enrol_lti logs deep linking launches in with the mode "existing accounts only", so a
 * teacher without hub account lands on auth/lti/login.php and is asked to link an existing
 * account. On the hub every teacher should get an own account instead, exactly as the
 * "new account" choice on that page would create it. Learners never start deep linking.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class account_provisioner {
    /**
     * Create and log in the account of a deep linking instructor, then return to the deep linking page.
     *
     * Does nothing unless auth/lti/login.php holds a pending deep linking launch of an instructor
     * and the site allows account creation. Redirects on success.
     */
    public static function provision_deeplinking_instructor(): void {
        global $CFG, $SESSION;

        if (!empty($CFG->authpreventaccountcreation) || empty($SESSION->auth_lti->launchdata)) {
            return;
        }
        $returnurl = $SESSION->auth_lti->returnurl ?? null;
        if (!$returnurl instanceof moodle_url) {
            return;
        }
        if ($returnurl->get_path() !== (new moodle_url('/enrol/lti/launch_deeplink.php'))->get_path()) {
            return;
        }
        $launchdata = $SESSION->auth_lti->launchdata;
        if (!is_array($launchdata) || !message_helper::is_instructor_launch($launchdata)) {
            return;
        }

        $launchid = (string) $returnurl->get_param('launchid');
        if ($launchid === '') {
            return;
        }

        // Same steps as the "new account" choice in auth/lti/login.php.
        $auth = get_auth_plugin('lti');
        $user = $auth->find_or_create_user_from_launch($launchdata);
        complete_user_login($user);
        $auth->update_user_account($user, $launchdata, $launchdata['iss']);
        unset($SESSION->auth_lti);

        // The login starts a new session. Put the already validated launch back into the launch
        // cache, so that launch_deeplink.php?launchid= finds it even if the session data was lost.
        (new launch_cache_session())->cacheLaunchData($launchid, $launchdata);

        redirect($returnurl);
    }
}
