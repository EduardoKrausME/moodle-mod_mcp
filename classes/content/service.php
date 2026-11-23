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
 * Immutable HTML drafts and controlled publication.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\content;

use context_module;
use mod_mcp\authoring\material_theme;
use moodle_exception;
use stdClass;

/**
 * Isolates untrusted HTML authoring from published Moodle course content.
 */
final class service {
    /**
     * Convert typographic quotes and dashes to plain ASCII before storing HTML.
     *
     * This replacement applies to the entire document, including inline scripts.
     *
     * @param string $html Incoming document.
     * @return string Normalized document.
     */
    public static function normalize_html(string $html): string {
        return strtr($html, [
            "“" => "\"",
            "”" => "\"",
            "‘" => "'",
            "’" => "'",
            "—" => "-",
            "–" => "-",
        ]);
    }

    /**
     * Save a new immutable revision with optimistic concurrency control.
     *
     * @return int Revision ID.
     */
    public static function save(int $mcpid, string $html, int $author, ?int $expectedrevision = null): int {
        global $DB;
        $html = trim(self::normalize_html($html));
        if ($html === "" || strlen($html) > 2097152) {
            throw new moodle_exception("invaliddata");
        }
        $transaction = $DB->start_delegated_transaction();
        $activity = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
        if ($expectedrevision !== null && (int)$activity->draftrevision !== $expectedrevision) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        self::validate_theme_colours((int)$activity->course, $html);
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

    /**
     * Enforce the effective course or site theme in all HTML authoring entry points.
     *
     * @param int $courseid Activity course ID.
     * @param string $html Authored HTML.
     */
    public static function validate_theme_colours(int $courseid, string $html): void {
        global $CFG, $DB;
        material_theme::require_for_course($courseid);
        $theme = (string)($CFG->theme ?? "");
        if (!empty($CFG->allowcoursethemes)) {
            $coursetheme = $DB->get_field("course", "theme", ["id" => $courseid]);
            if (is_string($coursetheme) && $coursetheme !== "") {
                $theme = $coursetheme;
            }
        }
        colour_validator::validate($html, $theme);
    }

    /**
     * Retrieve exactly one immutable revision owned by the activity.
     */
    public static function get(int $mcpid, int $revisionid): ?stdClass {
        global $DB;
        if (!$revisionid) {
            return null;
        }
        return $DB->get_record("mcp_revisions", ["id" => $revisionid, "mcpid" => $mcpid]) ?: null;
    }

    /**
     * Allow only the current published revision or a manager's current draft.
     *
     * An arbitrary historical revision must never become visible by guessing its URL.
     */
    public static function can_access_revision(stdClass $activity, int $revisionid, bool $canmanage): bool {
        if ($revisionid <= 0) {
            return false;
        }
        return $revisionid === (int)$activity->publishedrevision ||
            ($canmanage && $revisionid === (int)$activity->draftrevision);
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
        self::validate_theme_colours((int)$activity->course, (string)$draft->content);
        // A published quiz must have a complete self-contained JSON pool.
        metadata::validate((string)($activity->activitytype ?? ""),
            $activity->informationjson);
        $DB->update_record("mcp", (object)[
            "id" => $mcpid, "publishedrevision" => $expectedrevision, "timemodified" => time(),
        ]);
        set_coursemodule_visible($cm->id, 1);
        rebuild_course_cache($activity->course, true);
    }
}
