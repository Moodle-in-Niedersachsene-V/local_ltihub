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
 * CLI: prepare the hub for a new school instance.
 *
 * Creates the platform registration and prints the registration URL that the school admin
 * pastes into "Site administration > Plugins > External tool > Manage tools".
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    ['name' => '', 'help' => false],
    ['n' => 'name', 'h' => 'help']
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help'] || trim($options['name']) === '') {
    cli_writeln(get_string('cli_addschool_help', 'local_ltihub'));
    exit($options['help'] ? 0 : 1);
}

\core\session\manager::set_user(get_admin());

$result = \local_ltihub\local\school_registrar::create($options['name']);
cli_writeln(get_string('cli_addschool_done', 'local_ltihub', (object) $result));
