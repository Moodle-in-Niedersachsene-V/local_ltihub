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
 * Creates the hub course from the box on the deep linking page and returns there.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_ltihub\form\create_form;
use local_ltihub\local\course_provisioner;
use local_ltihub\local\launch_context;

require(__DIR__ . '/../../config.php');

require_login(null, false);

$url = new moodle_url('/local/ltihub/create.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');

$launch = launch_context::from_session();
if (!$launch || !$launch->is_instructor() || !$launch->has_platform_context()) {
    throw new moodle_exception('nolaunch', 'local_ltihub');
}
require_capability('local/ltihub:createhubcourse', context_system::instance());

$returnurl = new moodle_url('/enrol/lti/launch_deeplink.php', ['launchid' => $launch->get_launchid()]);
$form = new create_form($url, ['templates' => course_provisioner::get_templates()]);
if ($data = $form->get_data()) {
    course_provisioner::create($launch, (int) $data->templateid, (int) $USER->id);
    redirect($returnurl, get_string('coursecreated', 'local_ltihub'), null, \core\output\notification::NOTIFY_SUCCESS);
}
redirect($returnurl);
