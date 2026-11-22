<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade steps for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Apply schema upgrades when needed.
 *
 * @param int $oldversion Previously installed version.
 * @return bool Upgrade result.
 */
function xmldb_mcp_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Repeat this idempotent schema step for installations already marked as 2026112100.
    // That release could be installed without all the interactive completion fields/tables.
    if ($oldversion < 2026112101) {
        $table = new xmldb_table("mcp");
        $field = new xmldb_field("completioninteractive", XMLDB_TYPE_INTEGER, "1",
            null, XMLDB_NOTNULL, null, "0", "grade");
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $state = new xmldb_table("mcp_state");
        if (!$dbman->table_exists($state)) {
            $state->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $state->add_field("mcpid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
            $state->add_field("userid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
            $state->add_field("revisionid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
            $state->add_field("location", XMLDB_TYPE_CHAR, "255", null, XMLDB_NOTNULL, null, null);
            $state->add_field("statejson", XMLDB_TYPE_TEXT, null, null, null, null, null);
            $state->add_field("completed", XMLDB_TYPE_INTEGER, "1", null, XMLDB_NOTNULL, null, "0");
            $state->add_field("version", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
            $state->add_field("timemodified", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
            $state->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
            $state->add_key("mcp", XMLDB_KEY_FOREIGN, ["mcpid"], "mcp", ["id"]);
            $state->add_index("mcp_user", XMLDB_INDEX_UNIQUE, ["mcpid", "userid"]);
            $dbman->create_table($state);
        }

        $tracking = new xmldb_table("mcp_tracking");
        if (!$dbman->table_exists($tracking)) {
            $tracking->add_field("id", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $tracking->add_field("mcpid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
            $tracking->add_field("userid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
            $tracking->add_field("revisionid", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, "0");
            $tracking->add_field("eventtype", XMLDB_TYPE_CHAR, "30", null, XMLDB_NOTNULL, null, null);
            $tracking->add_field("action", XMLDB_TYPE_CHAR, "64", null, XMLDB_NOTNULL, null, null);
            $tracking->add_field("objectid", XMLDB_TYPE_CHAR, "255", null, XMLDB_NOTNULL, null, null);
            $tracking->add_field("datajson", XMLDB_TYPE_TEXT, null, null, null, null, null);
            $tracking->add_field("timecreated", XMLDB_TYPE_INTEGER, "10", null, XMLDB_NOTNULL, null, null);
            $tracking->add_key("primary", XMLDB_KEY_PRIMARY, ["id"]);
            $tracking->add_key("mcp", XMLDB_KEY_FOREIGN, ["mcpid"], "mcp", ["id"]);
            $tracking->add_index("mcp_user", XMLDB_INDEX_NOTUNIQUE, ["mcpid", "userid"]);
            $tracking->add_index("mcp_action", XMLDB_INDEX_NOTUNIQUE, ["mcpid", "eventtype", "action"]);
            $dbman->create_table($tracking);
        }
        upgrade_mod_savepoint(true, 2026112101, "mcp");
    }
    if ($oldversion < 2026112200) {
        $table = new xmldb_table("mcp");
        $fields = [
            new xmldb_field("questionselection", XMLDB_TYPE_CHAR, "10",
                null, XMLDB_NOTNULL, null, "all", "grade"),
            new xmldb_field("questionsperattempt", XMLDB_TYPE_INTEGER, "10",
                null, XMLDB_NOTNULL, null, "0", "questionselection"),
            new xmldb_field("shufflequestions", XMLDB_TYPE_INTEGER, "1",
                null, XMLDB_NOTNULL, null, "0", "questionsperattempt"),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_mod_savepoint(true, 2026112200, "mcp");
    }
    return true;
}
