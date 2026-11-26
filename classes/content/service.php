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
 * Immutable HTML revisions and direct content updates.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\content;

use moodle_exception;
use stdClass;

/**
 * Stores immutable source revisions without changing native module visibility.
 */
final class service {
    /**
     * Reject empty documents without modifying their source bytes.
     *
     * @param string $html Authored document.
     */
    public static function validate_html(string $html): void {
        if (trim($html) === "") {
            throw new moodle_exception("empty_data", "mod_mcp");
        }
    }

    /**
     * Save a new immutable revision with optimistic concurrency control.
     *
     * @param int $mcpid Activity instance ID.
     * @param string $html Complete HTML source.
     * @param int $author Author ID.
     * @param int|null $expectedrevision Current revision, if supplied.
     * @param array|null $settings Optional private metadata and expected settings hash.
     * @return int Revision ID.
     */
    public static function save(int $mcpid, string $html, int $author, ?int $expectedrevision = null,
            ?array $settings = null): int {
        global $DB;
        self::validate_html($html);
        $lock = \core\lock\lock_config::get_lock_factory("mod_mcp")->get_lock("content_" . $mcpid, 10);
        if (!$lock) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            try {
                $activity = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
                // Keep the legacy columns until a versioned schema migration can be shipped.
                $active = (int)$activity->publishedrevision;
                if ($expectedrevision !== null && $active !== $expectedrevision) {
                    throw new moodle_exception("revisionconflict", "mcp");
                }
                if ($settings !== null) {
                    if (!hash_equals(metadata::fingerprint($activity), $settings["expectedhash"])) {
                        throw new moodle_exception("revisionconflict", "mcp");
                    }
                    $information = metadata::validate($settings["type"], $settings["information"]);
                } else {
                    metadata::validate($activity->activitytype, $activity->informationjson);
                }
                $current = self::get($mcpid, $active);
                $hash = hash("sha256", $html);
                if ($current && hash_equals($current->sha256, $hash)) {
                    $revisionid = (int)$current->id;
                } else {
                    $revisionid = $DB->insert_record("mcp_revisions", (object)[
                        "mcpid" => $mcpid,
                        "content" => $html,
                        "sha256" => $hash,
                        "createdby" => $author,
                        "timecreated" => time(),
                    ]);
                }
                $update = (object)[
                    "id" => $mcpid, "draftrevision" => $revisionid,
                    "publishedrevision" => $revisionid, "timemodified" => time(),
                ];
                if ($settings !== null) {
                    $update->activitytype = $settings["type"] === "" ? null : $settings["type"];
                    $update->informationjson = $information;
                }
                $DB->update_record("mcp", $update);
                $transaction->allow_commit();
                return $revisionid;
            } catch (\Throwable $e) {
                $transaction->rollback($e);
            }
        } finally {
            $lock->release();
        }
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
     * Only the active revision may be served; historical source remains stored.
     */
    public static function can_access_revision(stdClass $activity, int $revisionid, bool $canmanage): bool {
        return $revisionid > 0 && $revisionid === (int)$activity->publishedrevision;
    }
}
