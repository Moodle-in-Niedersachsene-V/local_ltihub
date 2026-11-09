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
 * Version information for local_ltihub.
 *
 * Maintainer: Moodle in Niedersachsen e. V.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_ltihub';
$plugin->version = 2026103101;
$plugin->requires = 2026042000;
$plugin->supported = [502, 502];
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.5.1';
$plugin->dependencies = [
    'auth_lti' => ANY_VERSION,
    'enrol_lti' => ANY_VERSION,
];
