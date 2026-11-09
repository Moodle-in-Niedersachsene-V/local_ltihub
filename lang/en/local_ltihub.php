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
 * English strings for local_ltihub.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitiescopied'] = '{$a} new activities were copied into your hub course.';
$string['cli_addschool_done'] = 'Registration created for the school instance (id {$a->regid}).

The school admin pastes this registration URL under Site administration > Plugins > External tool > Manage tools into "Tool URL", clicks "Add LTI Advantage" and activates the tool:
{$a->regurl}

The URL can be used once.';
$string['cli_addschool_help'] = 'Prepare the hub for a new school instance: creates the platform registration and prints the registration URL for the school admin.

Options:
-n, --name=NAME   Name of the school instance, e.g. "Grundschule Baumhaus" (required)
-h, --help        Print this help

Example:
sudo -u www-data php public/local/ltihub/cli/add_school.php --name="Grundschule Baumhaus"';
$string['cli_ltidisabled'] = 'Enable the enrolment method "Publish as LTI tool" and the authentication method "LTI" first.';
$string['cli_noname'] = 'Please pass the name of the school instance with --name.';
$string['coursecreated'] = 'Your hub course has been created. Now choose the activities to add to your course.';
$string['coursefullname'] = '{$a->title} · {$a->template} ({$a->platform})';
$string['createbutton'] = 'Create hub course';
$string['createheading'] = 'Create your hub course';
$string['createintro'] = 'You do not have a hub course for your course "{$a}" yet. Choose a template; afterwards its activities appear in the list below. The hub course belongs to you; colleagues get their own.';
$string['eventhubcoursecreated'] = 'Hub course created';
$string['eventhubcourseupdated'] = 'New template activities copied into hub course';
$string['eventschoolregistrationcreated'] = 'Registration for a school instance created';
$string['invalidtemplate'] = 'Please choose one of the offered templates.';
$string['locktimeout'] = 'Your hub course is being created right now. Please reload this page in a few seconds.';
$string['ltihub:createhubcourse'] = 'Create a hub course from a template';
$string['nocategory'] = 'No parent category for hub courses is configured.';
$string['nolaunch'] = 'This page can only be opened from "Select content" of the hub tool on your school instance.';
$string['notemplates'] = 'No templates are configured on the hub yet. Please contact the administrator.';
$string['parentcategory'] = 'Parent category';
$string['parentcategory_desc'] = 'Hub courses are created in one sub category per school instance below this category.';
$string['pluginname'] = 'LTI hub course provisioning';
$string['privacy:metadata:local_ltihub_map'] = 'Assignment of a teacher and a course of the school instance to a hub course.';
$string['privacy:metadata:local_ltihub_map:ownerid'] = 'The teacher who owns the hub course.';
$string['privacy:metadata:local_ltihub_map:platformcontexttitle'] = 'Title of the course on the school instance.';
$string['privacy:metadata:local_ltihub_map:timecreated'] = 'Time the hub course was created.';
$string['restorefailed'] = 'Copying the template failed: {$a}';
$string['studentrole'] = 'Learner role';
$string['studentrole_desc'] = 'Role learners get in published activities. Default: student.';
$string['teacherrole'] = 'Instructor role';
$string['teacherrole_desc'] = 'Role teachers get in their hub course and in published activities. It must allow enrol/lti:config, otherwise "Select content" lists nothing. Default: editingteacher.';
$string['templatecategory'] = 'Template category';
$string['templatecategory_desc'] = 'Every course in this category is offered as template. The templates must not be published as LTI tools themselves.';
$string['templatechoice'] = 'Template';
$string['templatechoice_help'] = 'The template is copied into your own hub course, including all its activities. The choice cannot be changed later; ask the administrator to delete the hub course if you need another template.';
$string['untitledcourse'] = 'Course';
$string['updatebutton'] = 'Copy new activities';
$string['updateheading'] = 'New activities available';
$string['updateintro'] = 'The template now contains activities that your hub course does not have yet: {$a}. Copy them into your hub course to be able to choose them below.';
