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
 * Privacy metadata and user data handling.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\privacy;

use context;
use context_module;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use question_engine;
use stdClass;

/**
 * Protect authorship and student attempt information under Moodle Privacy API.
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored user-linked records.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table("mcp_revisions", [
            "createdby" => "privacy:metadata:mcp_revisions:createdby",
        ], "privacy:metadata:mcp_revisions");
        $collection->add_database_table("mcp_attempts", [
            "userid" => "privacy:metadata:mcp_attempts:userid",
        ], "privacy:metadata:mcp_attempts");
        $collection->add_database_table("mcp_state", [
            "userid" => "privacy:metadata:mcp_state:userid",
            "statejson" => "privacy:metadata:mcp_state:statejson",
        ], "privacy:metadata:mcp_state");
        $collection->add_database_table("mcp_tracking", [
            "userid" => "privacy:metadata:mcp_tracking:userid",
            "datajson" => "privacy:metadata:mcp_tracking:datajson",
        ], "privacy:metadata:mcp_tracking");
        return $collection;
    }

    /**
     * Find all MCP module contexts where the user authored or submitted data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {modules} m ON m.id = cm.module
                  JOIN {mcp} a ON a.id = cm.instance
             LEFT JOIN {mcp_attempts} att ON att.mcpid = a.id AND att.userid = :userid
             LEFT JOIN {mcp_revisions} rev ON rev.mcpid = a.id AND rev.createdby = :createdby
             LEFT JOIN {mcp_state} st ON st.mcpid = a.id AND st.userid = :stateuser
             LEFT JOIN {mcp_tracking} tr ON tr.mcpid = a.id AND tr.userid = :trackuser
                 WHERE m.name = :modulename AND ctx.contextlevel = :contextlevel
                   AND (att.id IS NOT NULL OR rev.id IS NOT NULL OR st.id IS NOT NULL OR tr.id IS NOT NULL)";
        $contextlist->add_from_sql($sql, [
            "userid" => $userid, "createdby" => $userid,
            "stateuser" => $userid, "trackuser" => $userid,
            "modulename" => "mcp", "contextlevel" => CONTEXT_MODULE,
        ]);
        return $contextlist;
    }

    /**
     * Export only records belonging to the approved user and context.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id("mcp", (int)$context->instanceid,
                0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            $attempts = $DB->get_records("mcp_attempts", [
                "mcpid" => $cm->instance, "userid" => $userid,
            ]);
            $revisions = $DB->get_records("mcp_revisions", [
                "mcpid" => $cm->instance, "createdby" => $userid,
            ]);
            $state = $DB->get_record("mcp_state", [
                "mcpid" => $cm->instance, "userid" => $userid,
            ]);
            $tracking = $DB->get_records("mcp_tracking", [
                "mcpid" => $cm->instance, "userid" => $userid,
            ]);
            writer::with_context($context)->export_data([get_string("modulename", "mcp")],
                (object)["attempts" => array_values($attempts),
                    "revisions" => array_values($revisions), "progress" => $state,
                    "tracking" => array_values($tracking)]);
        }
    }

    /**
     * Anonymize shared authorship and delete user-owned native question usages.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB, $CFG;
        $cm = self::module($context);
        if (!$cm) {
            return;
        }
        require_once($CFG->dirroot . "/question/engine/lib.php");
        foreach ($DB->get_records("mcp_attempts", ["mcpid" => $cm->instance]) as $attempt) {
            question_engine::delete_questions_usage_by_activity((int)$attempt->qubaid);
        }
        $DB->delete_records("mcp_attempts", ["mcpid" => $cm->instance]);
        $DB->delete_records("mcp_state", ["mcpid" => $cm->instance]);
        $DB->delete_records("mcp_tracking", ["mcpid" => $cm->instance]);
        $DB->set_field("mcp_revisions", "createdby", 0, ["mcpid" => $cm->instance]);
    }

    /**
     * Delete one user's attempts, leaving pedagogical content intact.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/question/engine/lib.php");
        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = self::module($context);
            if (!$cm) {
                continue;
            }
            foreach ($DB->get_records("mcp_attempts", [
                    "mcpid" => $cm->instance, "userid" => $userid]) as $attempt) {
                question_engine::delete_questions_usage_by_activity((int)$attempt->qubaid);
            }
            $DB->delete_records("mcp_attempts", ["mcpid" => $cm->instance, "userid" => $userid]);
            $DB->delete_records("mcp_state", ["mcpid" => $cm->instance, "userid" => $userid]);
            $DB->delete_records("mcp_tracking", ["mcpid" => $cm->instance, "userid" => $userid]);
            $DB->set_field("mcp_revisions", "createdby", 0,
                ["mcpid" => $cm->instance, "createdby" => $userid]);
        }
    }

    /**
     * Return only module contexts belonging to the MCP activity.
     */
    private static function module(context $context): ?stdClass {
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return null;
        }
        return get_coursemodule_from_id("mcp", (int)$context->instanceid,
            0, false, IGNORE_MISSING) ?: null;
    }
}
