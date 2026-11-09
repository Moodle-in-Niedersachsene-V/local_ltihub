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
 * Upgrade steps for local_ltihub.
 *
 * @package    local_ltihub
 * @copyright  2026 Moodle in Niedersachsen e. V.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade local_ltihub.
 *
 * @param int $oldversion Version installed before the upgrade.
 * @return bool
 */
function xmldb_local_ltihub_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026103006) {
        // The gate resource of the two-tool model is gone; its setting is no longer read.
        unset_config('gateuuid', 'local_ltihub');
        upgrade_plugin_savepoint(true, 2026103006, 'local', 'ltihub');
    }

    if ($oldversion < 2026103100) {
        // Define table local_ltihub_item to be created.
        $table = new xmldb_table('local_ltihub_item');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mapid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('templatecmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('mapid', XMLDB_KEY_FOREIGN, ['mapid'], 'local_ltihub_map', ['id']);
        $table->add_index('mapidtemplatecmid', XMLDB_INDEX_UNIQUE, ['mapid', 'templatecmid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Existing hub courses: link their activities to the template so they are not copied twice.
        \local_ltihub\local\course_provisioner::link_untracked_courses();

        upgrade_plugin_savepoint(true, 2026103100, 'local', 'ltihub');
    }

    return true;
}
