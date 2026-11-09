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

use auth_plugin_lti;
use backup;
use backup_controller;
use backup_setting;
use context_course;
use context_module;
use core_course_category;
use moodle_exception;
use restore_controller;
use restore_dbops;
use stdClass;

/**
 * Creates the hub course of a teacher for one platform course and publishes its activities.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_provisioner {
    /** @var string[] Module types that are never published as LTI resources. */
    public const SKIP_MODULES = ['label', 'qbank', 'subsection'];

    /**
     * Get the hub course mapping for a launch.
     *
     * @param launch_context $launch
     * @return stdClass|null Mapping record, null if there is none or its course no longer exists.
     */
    public static function get_mapping(launch_context $launch): ?stdClass {
        global $DB;
        $record = $DB->get_record_sql(
            'SELECT m.*
               FROM {local_ltihub_map} m
               JOIN {course} c ON c.id = m.courseid
              WHERE m.platformkey = :platformkey',
            ['platformkey' => $launch->get_platformkey()]
        );
        return $record ?: null;
    }

    /**
     * Get the courses teachers can choose as template.
     *
     * @return stdClass[] Course records (id, fullname, summary, summaryformat), keyed by id.
     */
    public static function get_templates(): array {
        global $DB;
        $categoryid = (int) get_config('local_ltihub', 'templatecategory');
        if (!$categoryid) {
            return [];
        }
        return $DB->get_records(
            'course',
            ['category' => $categoryid],
            'sortorder ASC',
            'id, fullname, summary, summaryformat'
        );
    }

    /**
     * Create the hub course for a launch from the chosen template.
     *
     * Serialised with a lock so that a double click does not create two courses.
     *
     * @param launch_context $launch Validated instructor launch.
     * @param int $templateid Course id of the chosen template.
     * @param int $userid Hub user who becomes the owner.
     * @return stdClass Mapping record.
     */
    public static function create(launch_context $launch, int $templateid, int $userid): stdClass {
        global $DB;

        if (!array_key_exists($templateid, self::get_templates())) {
            throw new moodle_exception('invalidtemplate', 'local_ltihub');
        }

        $key = $launch->get_platformkey();
        $lock = \core\lock\lock_config::get_lock_factory('local_ltihub')->get_lock('map_' . $key, 60);
        if (!$lock) {
            throw new moodle_exception('locktimeout', 'local_ltihub');
        }

        try {
            if ($existing = self::get_mapping($launch)) {
                return $existing;
            }
            // A mapping whose course was deleted is replaced by the new one.
            $stale = $DB->get_fieldset_select('local_ltihub_map', 'id', 'platformkey = ?', [$key]);
            if ($stale) {
                $DB->delete_records_list('local_ltihub_item', 'mapid', $stale);
                $DB->delete_records_list('local_ltihub_map', 'id', $stale);
            }

            $template = get_course($templateid);
            [$courseid, $cmmap] = self::restore_template($launch, $template);
            self::publish_activities($courseid);
            self::enrol_owner($courseid, $userid);

            $now = time();
            $map = (object) [
                'platformkey' => $key,
                'issuer' => $launch->get_issuer(),
                'platformcontextid' => $launch->get_platform_contextid(),
                'platformcontexttitle' => \core_text::substr($launch->get_platform_contexttitle(), 0, 1333),
                'ltideploymentid' => $launch->get_deploymentid(),
                'templateid' => $templateid,
                'courseid' => $courseid,
                'ownerid' => $userid,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $map->id = $DB->insert_record('local_ltihub_map', $map);
            foreach ($cmmap as $templatecmid => $cmid) {
                self::record_item((int) $map->id, (int) $templatecmid, (int) $cmid);
            }

            \local_ltihub\event\hub_course_created::create([
                'objectid' => $map->id,
                'context' => context_course::instance($courseid),
                'other' => ['templateid' => $templateid],
            ])->trigger();

            return $map;
        } finally {
            $lock->release();
        }
    }

    /**
     * Publish every relevant activity of a hub course as an LTI 1.3 resource.
     *
     * Activities that are already published are left alone. Instructors get the teacher
     * role and learners the student role in the module context; grades go back via AGS,
     * memberships are not synced.
     *
     * @param int $courseid
     */
    public static function publish_activities(int $courseid): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/auth/lti/auth.php');

        $course = get_course($courseid);
        $plugin = enrol_get_plugin('lti');
        [$roleinstructor, $rolelearner] = self::get_roles();

        foreach (get_fast_modinfo($course)->get_cms() as $cm) {
            if (in_array($cm->modname, self::SKIP_MODULES) || $cm->deletioninprogress) {
                continue;
            }
            // The announcements forum of a hub course is of no use on the school instance.
            if ($cm->modname === 'forum' && $DB->get_field('forum', 'type', ['id' => $cm->instance]) === 'news') {
                continue;
            }
            $context = context_module::instance($cm->id);
            if ($DB->record_exists('enrol_lti_tools', ['contextid' => $context->id])) {
                continue;
            }
            $plugin->add_instance($course, [
                'name' => $cm->get_formatted_name(),
                'status' => ENROL_INSTANCE_ENABLED,
                'contextid' => $context->id,
                'ltiversion' => 'LTI-1p3',
                'roleinstructor' => $roleinstructor,
                'rolelearner' => $rolelearner,
                'provisioningmodeinstructor' => auth_plugin_lti::PROVISIONING_MODE_AUTO_ONLY,
                'provisioningmodelearner' => auth_plugin_lti::PROVISIONING_MODE_AUTO_ONLY,
                'gradesync' => 1,
                'gradesynccompletion' => 0,
                'membersync' => 0,
                'membersyncmode' => 0,
                'maxenrolled' => 0,
                'maildisplay' => 0,
                'institution' => '',
                'city' => '',
                'country' => '',
                'lang' => $CFG->lang,
                'timezone' => '99',
            ]);
        }
    }

    /**
     * Get the configured instructor and learner role ids, with core defaults.
     *
     * @return int[] Instructor role id and learner role id.
     */
    protected static function get_roles(): array {
        global $DB;
        $instructor = (int) get_config('local_ltihub', 'teacherrole');
        if (!$instructor) {
            $instructor = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
        }
        $learner = (int) get_config('local_ltihub', 'studentrole');
        if (!$learner) {
            $learner = (int) $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        }
        return [$instructor, $learner];
    }

    /**
     * Enrol the owner with the instructor role at course level.
     *
     * A course level role is needed for enrol/lti:config, which deep linking requires
     * before it lists the published activities.
     *
     * @param int $courseid
     * @param int $userid
     */
    protected static function enrol_owner(int $courseid, int $userid): void {
        global $DB;
        [$roleinstructor] = self::get_roles();
        $course = get_course($courseid);
        $plugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', IGNORE_MULTIPLE);
        if (!$instance) {
            $instance = $DB->get_record('enrol', ['id' => $plugin->add_default_instance($course)], '*', MUST_EXIST);
        }
        $plugin->enrol_user($instance, $userid, $roleinstructor);
    }

    /**
     * Copy the template into a new course in the category of the school instance.
     *
     * @param launch_context $launch
     * @param stdClass $template Template course record.
     * @return array Id of the new course and a map of template course module id => new course module id.
     */
    protected static function restore_template(launch_context $launch, stdClass $template): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        require_once($CFG->dirroot . '/course/lib.php');

        $categoryid = self::get_platform_category($launch);
        $adminid = get_admin()->id;
        $title = $launch->get_platform_contexttitle() ?: get_string('untitledcourse', 'local_ltihub');
        $fullname = get_string('coursefullname', 'local_ltihub', (object) [
            'title' => $title,
            'template' => format_string($template->fullname, true, ['escape' => false]),
            'platform' => $launch->get_platformlabel(),
        ]);
        $shortname = 'ltihub-' . substr($launch->get_platformkey(), 0, 16);
        $settings = [
            'users' => 0,
            'role_assignments' => 0,
            'enrolments' => backup::ENROL_NEVER,
            'comments' => 0,
            'logs' => 0,
            'grade_histories' => 0,
        ];

        $bc = new backup_controller(
            backup::TYPE_1COURSE,
            $template->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            $adminid
        );
        foreach ($settings as $name => $value) {
            if ($bc->get_plan()->setting_exists($name)) {
                $bc->get_plan()->get_setting($name)->set_value($value);
            }
        }
        $backupid = $bc->get_backupid();
        $basepath = $bc->get_plan()->get_basepath();
        $bc->execute_plan();
        $file = $bc->get_results()['backup_destination'];
        $bc->destroy();

        if (!file_exists($basepath . '/moodle_backup.xml')) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $basepath);
        }

        $courseid = restore_dbops::create_new_course($fullname, $shortname, $categoryid);
        $rc = new restore_controller(
            $backupid,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_SAMESITE,
            $adminid,
            backup::TARGET_NEW_COURSE
        );
        foreach ($settings as $name => $value) {
            if ($rc->get_plan()->setting_exists($name)) {
                $setting = $rc->get_plan()->get_setting($name);
                if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                    $setting->set_value($value);
                }
            }
        }
        try {
            if (!$rc->execute_precheck()) {
                $results = $rc->get_precheck_results();
                if (!empty($results['errors'])) {
                    throw new moodle_exception('restorefailed', 'local_ltihub', '', implode(' ', $results['errors']));
                }
            }
            $rc->execute_plan();
            $cmmap = self::get_restored_cms($rc);
        } finally {
            $rc->destroy();
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($basepath);
            }
            $file->delete();
        }

        // The restore copies the template's names; set ours through the course API.
        update_course((object) [
            'id' => $courseid,
            'fullname' => $fullname,
            'shortname' => $shortname,
            'idnumber' => $shortname,
            'visible' => 1,
        ]);
        return [$courseid, $cmmap];
    }

    /**
     * Read which course module was restored from which original course module.
     *
     * Same approach as core_courseformat\local\cmactions::duplicate(): the restore plan knows the
     * old context of every restored activity.
     *
     * @param restore_controller $rc Restore controller after execute_plan().
     * @return array Original course module id => new course module id.
     */
    protected static function get_restored_cms(restore_controller $rc): array {
        $map = [];
        foreach ($rc->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task && $task->get_moduleid()) {
                $oldcontext = \context::instance_by_id($task->get_old_contextid(), IGNORE_MISSING);
                if ($oldcontext && $oldcontext->contextlevel == CONTEXT_MODULE) {
                    $map[(int) $oldcontext->instanceid] = (int) $task->get_moduleid();
                }
            }
        }
        return $map;
    }

    /**
     * Remember that a hub course activity was copied from a template activity.
     *
     * @param int $mapid
     * @param int $templatecmid
     * @param int $cmid 0 if the copy is not known.
     */
    protected static function record_item(int $mapid, int $templatecmid, int $cmid): void {
        global $DB;
        if ($DB->record_exists('local_ltihub_item', ['mapid' => $mapid, 'templatecmid' => $templatecmid])) {
            return;
        }
        $DB->insert_record('local_ltihub_item', (object) [
            'mapid' => $mapid,
            'templatecmid' => $templatecmid,
            'cmid' => $cmid,
            'timecreated' => time(),
        ]);
    }

    /**
     * Get the template activities that were added to the template after the hub course was created.
     *
     * @param stdClass $map Mapping record.
     * @return \cm_info[] Template course modules not yet copied into the hub course, keyed by id.
     */
    public static function get_missing_template_cms(stdClass $map): array {
        global $DB;
        if (!$DB->record_exists('course', ['id' => $map->templateid])) {
            return [];
        }
        $known = $DB->get_records_menu('local_ltihub_item', ['mapid' => $map->id], '', 'templatecmid, cmid');
        $missing = [];
        foreach (get_fast_modinfo($map->templateid)->get_cms() as $cm) {
            if (!$cm->deletioninprogress && !array_key_exists($cm->id, $known)) {
                $missing[$cm->id] = $cm;
            }
        }
        return $missing;
    }

    /**
     * Copy the template activities that the hub course is missing and publish them.
     *
     * @param stdClass $map Mapping record of the teacher.
     * @return int Number of copied activities.
     */
    public static function copy_missing_activities(stdClass $map): int {
        $lock = \core\lock\lock_config::get_lock_factory('local_ltihub')->get_lock('map_' . $map->platformkey, 120);
        if (!$lock) {
            throw new moodle_exception('locktimeout', 'local_ltihub');
        }
        try {
            $missing = self::get_missing_template_cms($map);
            foreach ($missing as $cm) {
                $cmid = self::copy_activity($cm, (int) $map->courseid);
                self::record_item((int) $map->id, (int) $cm->id, $cmid);
            }
            if ($missing) {
                self::publish_activities((int) $map->courseid);
                \local_ltihub\event\hub_course_updated::create([
                    'objectid' => $map->id,
                    'context' => context_course::instance($map->courseid),
                    'other' => ['count' => count($missing)],
                ])->trigger();
            }
            return count($missing);
        } finally {
            $lock->release();
        }
    }

    /**
     * Copy one template activity into a hub course.
     *
     * @param \cm_info $cm Template course module.
     * @param int $courseid Hub course.
     * @return int Id of the new course module, 0 if the restore did not report it.
     */
    protected static function copy_activity(\cm_info $cm, int $courseid): int {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $adminid = get_admin()->id;
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $cm->id,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $adminid
        );
        $backupid = $bc->get_backupid();
        $basepath = $bc->get_plan()->get_basepath();
        $bc->execute_plan();
        $bc->destroy();

        $rc = new restore_controller(
            $backupid,
            $courseid,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $adminid,
            backup::TARGET_EXISTING_ADDING
        );
        try {
            if (!$rc->execute_precheck()) {
                $results = $rc->get_precheck_results();
                if (!empty($results['errors'])) {
                    throw new moodle_exception('restorefailed', 'local_ltihub', '', implode(' ', $results['errors']));
                }
            }
            $rc->execute_plan();
            $cmmap = self::get_restored_cms($rc);
        } finally {
            $rc->destroy();
            if (empty($CFG->keeptempdirectoriesonbackup)) {
                fulldelete($basepath);
            }
        }
        return (int) ($cmmap[$cm->id] ?? 0);
    }

    /**
     * Link the activities of hub courses created before activities were tracked to their template.
     *
     * Matches by module type and name, so that a later copy does not duplicate them. Runs during
     * the plugin upgrade, where get_fast_modinfo() is not allowed, so it reads the tables directly.
     */
    public static function link_untracked_courses(): void {
        global $DB;
        $maps = $DB->get_records_sql(
            'SELECT m.*
               FROM {local_ltihub_map} m
               JOIN {course} c ON c.id = m.courseid
              WHERE NOT EXISTS (SELECT 1 FROM {local_ltihub_item} i WHERE i.mapid = m.id)'
        );
        foreach ($maps as $map) {
            if (!$DB->record_exists('course', ['id' => $map->templateid])) {
                continue;
            }
            $roomcms = [];
            foreach (self::get_course_cm_keys((int) $map->courseid) as $cmid => $key) {
                $roomcms[$key][] = $cmid;
            }
            foreach (self::get_course_cm_keys((int) $map->templateid) as $cmid => $key) {
                if (!empty($roomcms[$key])) {
                    self::record_item((int) $map->id, $cmid, array_shift($roomcms[$key]));
                }
            }
        }
    }

    /**
     * Get "module type|activity name" for every course module of a course, without the modinfo cache.
     *
     * @param int $courseid
     * @return string[] Course module id => key, ordered by course module id.
     */
    protected static function get_course_cm_keys(int $courseid): array {
        global $DB;
        $cms = $DB->get_records_sql(
            'SELECT cm.id, cm.instance, md.name AS modname
               FROM {course_modules} cm
               JOIN {modules} md ON md.id = cm.module
              WHERE cm.course = :courseid AND cm.deletioninprogress = 0
           ORDER BY cm.id',
            ['courseid' => $courseid]
        );
        $dbman = $DB->get_manager();
        $keys = [];
        foreach ($cms as $cm) {
            $name = '';
            if ($dbman->table_exists($cm->modname)) {
                $name = (string) $DB->get_field($cm->modname, 'name', ['id' => $cm->instance]);
            }
            $keys[(int) $cm->id] = $cm->modname . '|' . $name;
        }
        return $keys;
    }

    /**
     * Get or create the category of the school instance below the configured parent.
     *
     * The category is keyed by issuer, so both registrations of a school end up in it.
     *
     * @param launch_context $launch
     * @return int Category id.
     */
    protected static function get_platform_category(launch_context $launch): int {
        global $DB;
        $parent = (int) get_config('local_ltihub', 'parentcategory');
        if (!$parent || !$DB->record_exists('course_categories', ['id' => $parent])) {
            throw new moodle_exception('nocategory', 'local_ltihub');
        }
        $idnumber = 'ltihub-iss-' . substr(hash('sha256', $launch->get_issuer()), 0, 16);
        if ($id = $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber])) {
            return (int) $id;
        }
        $category = core_course_category::create((object) [
            'name' => $launch->get_platformlabel(),
            'idnumber' => $idnumber,
            'parent' => $parent,
        ]);
        return (int) $category->id;
    }
}
