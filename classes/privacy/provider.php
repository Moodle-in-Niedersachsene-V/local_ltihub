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

namespace local_ltihub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: the plugin stores the owner of each hub course.
 *
 * Accounts, enrolments and grades are handled by auth_lti, enrol_lti and the activities.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_ltihub_map', [
            'ownerid' => 'privacy:metadata:local_ltihub_map:ownerid',
            'platformcontexttitle' => 'privacy:metadata:local_ltihub_map:platformcontexttitle',
            'timecreated' => 'privacy:metadata:local_ltihub_map:timecreated',
        ], 'privacy:metadata:local_ltihub_map');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT ctx.id
               FROM {local_ltihub_map} m
               JOIN {context} ctx ON ctx.instanceid = m.courseid AND ctx.contextlevel = :level
              WHERE m.ownerid = :userid',
            ['level' => CONTEXT_COURSE, 'userid' => $userid]
        );
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }
        $userlist->add_from_sql(
            'ownerid',
            'SELECT ownerid FROM {local_ltihub_map} WHERE courseid = :courseid AND ownerid > 0',
            ['courseid' => $context->instanceid]
        );
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_COURSE) {
                continue;
            }
            $records = $DB->get_records('local_ltihub_map', ['courseid' => $context->instanceid, 'ownerid' => $userid]);
            foreach ($records as $record) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_ltihub')],
                    (object) [
                        'platformcontexttitle' => $record->platformcontexttitle,
                        'timecreated' => transform::datetime($record->timecreated),
                    ]
                );
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel == CONTEXT_COURSE) {
            $DB->set_field('local_ltihub_map', 'ownerid', 0, ['courseid' => $context->instanceid]);
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $DB->set_field('local_ltihub_map', 'ownerid', 0, ['courseid' => $context->instanceid, 'ownerid' => $userid]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE || !$userlist->get_userids()) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['courseid'] = $context->instanceid;
        $DB->set_field_select('local_ltihub_map', 'ownerid', 0, "courseid = :courseid AND ownerid $insql", $params);
    }
}
