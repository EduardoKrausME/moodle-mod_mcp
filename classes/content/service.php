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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Immutable HTML drafts and controlled publication.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\content;

use context_module;
use moodle_exception;

/**
 * Isolates untrusted HTML authoring from published Moodle course content.
 */
final class service {
    /**
     * Save a new immutable revision with optimistic concurrency control.
     *
     * @return int Revision ID.
     */
    public static function save(int $mcpid, string $html, int $author, ?int $expectedrevision = null): int {
        global $DB;
        $html = trim($html);
        if ($html === "" || strlen($html) > 2097152) {
            throw new moodle_exception("invaliddata");
        }
        $transaction = $DB->start_delegated_transaction();
        $activity = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
        if ($expectedrevision !== null && (int)$activity->draftrevision !== $expectedrevision) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        $current = $activity->draftrevision ?
            $DB->get_record("mcp_revisions", ["id" => $activity->draftrevision], "*", MUST_EXIST) : null;
        $hash = hash("sha256", $html);
        if ($current && hash_equals($current->sha256, $hash)) {
            $transaction->allow_commit();
            return (int)$current->id;
        }
        $revisionid = $DB->insert_record("mcp_revisions", (object)[
            "mcpid" => $mcpid,
            "content" => $html,
            "sha256" => $hash,
            "createdby" => $author,
            "timecreated" => time(),
        ]);
        $DB->update_record("mcp", (object)[
            "id" => $mcpid, "draftrevision" => $revisionid, "timemodified" => time(),
        ]);
        $transaction->allow_commit();
        return $revisionid;
    }

    /** Retrieve exactly one immutable revision owned by the activity. */
    public static function get(int $mcpid, int $revisionid): ?\stdClass {
        global $DB;
        if (!$revisionid) {
            return null;
        }
        return $DB->get_record("mcp_revisions", ["id" => $revisionid, "mcpid" => $mcpid]) ?: null;
    }

    /**
     * Publish the draft only after its content and attached questions are valid.
     */
    public static function publish(int $mcpid, int $expectedrevision): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        $activity = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
        $cm = get_coursemodule_from_instance("mcp", $mcpid, $activity->course, false, MUST_EXIST);
        require_capability("mod/mcp:manage", context_module::instance($cm->id));
        if (!$expectedrevision || $expectedrevision !== (int)$activity->draftrevision) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        $draft = self::get($mcpid, $expectedrevision);
        if (!$draft || trim($draft->content) === "") {
            throw new moodle_exception("invaliddata");
        }
        // Assessment question IDs must be pinned in the native question bank.
        foreach ($DB->get_records("mcp_slots", ["mcpid" => $mcpid, "active" => 1]) as $slot) {
            \mod_mcp\question\slots::validate_question((int)$slot->questionid);
        }
        $DB->update_record("mcp", (object)[
            "id" => $mcpid, "publishedrevision" => $expectedrevision, "timemodified" => time(),
        ]);
        set_coursemodule_visible($cm->id, 1);
        rebuild_course_cache($activity->course, true);
    }
}

