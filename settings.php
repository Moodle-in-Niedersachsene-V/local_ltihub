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

/**
 * Admin settings for local_ltihub.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_ltihub', get_string('pluginname', 'local_ltihub'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $categories = [0 => get_string('choosedots')] + core_course_category::make_categories_list();
        $roles = [0 => get_string('choosedots')] + role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT, true);

        $settings->add(new admin_setting_configselect(
            'local_ltihub/templatecategory',
            get_string('templatecategory', 'local_ltihub'),
            get_string('templatecategory_desc', 'local_ltihub'),
            0,
            $categories
        ));
        $settings->add(new admin_setting_configselect(
            'local_ltihub/parentcategory',
            get_string('parentcategory', 'local_ltihub'),
            get_string('parentcategory_desc', 'local_ltihub'),
            0,
            $categories
        ));
        $settings->add(new admin_setting_configselect(
            'local_ltihub/teacherrole',
            get_string('teacherrole', 'local_ltihub'),
            get_string('teacherrole_desc', 'local_ltihub'),
            0,
            $roles
        ));
        $settings->add(new admin_setting_configselect(
            'local_ltihub/studentrole',
            get_string('studentrole', 'local_ltihub'),
            get_string('studentrole_desc', 'local_ltihub'),
            0,
            $roles
        ));
    }
}
