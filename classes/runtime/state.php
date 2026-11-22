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
 * Student-owned progress, completion and lightweight learning analytics.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\runtime;

use core\lock\lock_config;
use moodle_exception;
use stdClass;

/**
 * Stores bounded per-user state. Never accepts a user ID or an activity ID from authored HTML.
 */
final class state {
    /**
     * A short lock serialises updates to a single student's progress.
     */
    private static function locked(int $mcpid, int $userid, callable $callback) {
        $factory = lock_config::get_lock_factory("mod_mcp");
        $lock = $factory->get_lock("state_" . $mcpid . "_" . $userid, 10);
        if (!$lock) {
            throw new moodle_exception("invaliddata");
        }
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * State belongs to a published revision; completion is activity-wide.
     */
    public static function load(int $mcpid, int $userid, int $revision): array {
        global $DB;
        $record = $DB->get_record("mcp_state", ["mcpid" => $mcpid, "userid" => $userid]);
        if (!$record) {
            return ["version" => 0, "location" => "", "data" => [], "userdata" => [], "completed" => false];
        }
        $same = (int)$record->revisionid === $revision;
        return [
            "version" => (int)$record->version,
            "location" => $same ? $record->location : "",
            "data" => $same ? (json_decode($record->statejson, true) ?: []) : [],
            "userdata" => $same ? (json_decode($record->userdatajson ?? "{}", true) ?: []) : [],
            "completed" => (bool)$record->completed,
        ];
    }

    /**
     * Require a matching version instead of silently discarding another browser's progress.
     */
    public static function save(int $mcpid, int $userid, int $revision, array $payload): array {
        global $DB;
        $location = $payload["location"] ?? "";
        $data = $payload["data"] ?? [];
        $expected = $payload["version"] ?? null;
        $userdata = $payload["userdata"] ?? null;
        if ($userdata !== null && (!is_array($userdata) || strlen(json_encode(
                $userdata, JSON_INVALID_UTF8_SUBSTITUTE)) > 131072)) {
            throw new moodle_exception("invaliddata");
        }
        if (!is_string($location) || strlen($location) > 255 ||
                !is_array($data) || !is_int($expected) || $expected < 0) {
            throw new moodle_exception("invaliddata");
        }
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false || strlen($json) > 12288) {
            throw new moodle_exception("invaliddata");
        }
        return self::locked($mcpid, $userid, function () use (
                $DB, $mcpid, $userid, $revision, $location, $json, $expected, $userdata) {
            $record = $DB->get_record("mcp_state", ["mcpid" => $mcpid, "userid" => $userid]);
            $version = $record ? (int)$record->version : 0;
            if ($version !== $expected) {
                throw new moodle_exception("progressconflict", "mcp");
            }
            if ($record) {
                $newuserdata = $userdata === null ?
                    ($record->revisionid == $revision ? $record->userdatajson : null) :
                    json_encode($userdata, JSON_INVALID_UTF8_SUBSTITUTE);
                $DB->update_record("mcp_state", (object)[
                    "id" => $record->id, "revisionid" => $revision,
                    "location" => $location, "statejson" => $json,
                    "userdatajson" => $newuserdata,
                    "version" => $version + 1, "timemodified" => time(),
                ]);
            } else {
                $DB->insert_record("mcp_state", (object)[
                    "mcpid" => $mcpid, "userid" => $userid, "revisionid" => $revision,
                    "location" => $location, "statejson" => $json,
                    "userdatajson" => $userdata === null ? null :
                        json_encode($userdata, JSON_INVALID_UTF8_SUBSTITUTE),
                    "completed" => 0, "version" => 1, "timemodified" => time(),
                ]);
            }
            return self::load($mcpid, $userid, $revision);
        });
    }


    /**
     * Current learning position, independently of Moodle activity completion.
     *
     * @param int $mcpid Activity instance ID.
     * @param int $userid Authenticated user ID from the trusted caller.
     * @param int $revision Published revision ID.
     * @param bool $moodlecompleted Actual Moodle completion state.
     * @return array Current status for this student and published revision.
     */
    public static function status(int $mcpid, int $userid, int $revision, bool $moodlecompleted): array {
        $snapshot = self::load($mcpid, $userid, $revision);
        $details = $snapshot["data"]["_modmcpstatus"] ?? [];
        if (!is_array($details)) {
            $details = [];
        }
        $percent = $details["percent"] ?? 0;
        $currentstep = $details["currentstep"] ?? 0;
        $totalsteps = $details["totalsteps"] ?? 0;
        $percent = is_int($percent) && $percent >= 0 && $percent <= 100 ? $percent : 0;
        $currentstep = is_int($currentstep) && $currentstep >= 0 ? $currentstep : 0;
        $totalsteps = is_int($totalsteps) && $totalsteps >= $currentstep ? $totalsteps : 0;
        $started = $snapshot["location"] !== "" || !empty($snapshot["data"]);
        return [
            "version" => $snapshot["version"],
            "location" => $snapshot["location"],
            "percent" => $percent,
            "currentstep" => $currentstep,
            "totalsteps" => $totalsteps,
            "status" => !$started ? "notstarted" : ($percent === 100 ? "finished" : "inprogress"),
            "moodlecompleted" => $moodlecompleted,
        ];
    }

    /**
     * Save structured learning progress without overwriting other authored state.
     * The underlying save method keeps the existing optimistic lock and size limits.
     *
     * @param int $mcpid Activity instance ID.
     * @param int $userid Authenticated user ID from the trusted caller.
     * @param int $revision Published revision ID.
     * @param array $payload Bounded progress data and expected state version.
     */
    public static function save_status(int $mcpid, int $userid, int $revision, array $payload): void {
        $version = $payload["version"] ?? null;
        $percent = $payload["percent"] ?? null;
        $currentstep = $payload["currentstep"] ?? 0;
        $totalsteps = $payload["totalsteps"] ?? 0;
        if (!is_int($version) || $version < 0 || !is_int($percent) ||
                $percent < 0 || $percent > 100 || !is_int($currentstep) ||
                !is_int($totalsteps) || $currentstep < 0 || $totalsteps < 0 ||
                $currentstep > $totalsteps || $totalsteps > 1000000) {
            throw new moodle_exception("invaliddata");
        }

        $snapshot = self::load($mcpid, $userid, $revision);
        $location = $payload["location"] ?? $snapshot["location"];
        if (!is_string($location) || strlen($location) > 255) {
            throw new moodle_exception("invaliddata");
        }
        $data = $snapshot["data"];
        $data["_modmcpstatus"] = [
            "percent" => $percent,
            "currentstep" => $currentstep,
            "totalsteps" => $totalsteps,
        ];
        self::save($mcpid, $userid, $revision, [
            "version" => $version,
            "location" => $location,
            "data" => $data,
        ]);
    }

    /**
     * A declaration from HTML is not a grade; it only fulfils an enabled custom completion rule.
     */
    public static function complete(int $mcpid, int $userid, int $revision): void {
        global $DB;
        self::locked($mcpid, $userid, function () use ($DB, $mcpid, $userid, $revision) {
            $record = $DB->get_record("mcp_state", ["mcpid" => $mcpid, "userid" => $userid]);
            if ($record && $record->completed) {
                return;
            }
            if ($record) {
                $DB->update_record("mcp_state", (object)[
                    "id" => $record->id, "completed" => 1,
                    "version" => (int)$record->version + 1, "timemodified" => time(),
                ]);
            } else {
                $DB->insert_record("mcp_state", (object)[
                    "mcpid" => $mcpid, "userid" => $userid, "revisionid" => $revision,
                    "location" => "", "statejson" => "{}", "userdatajson" => null,
                    "completed" => 1,
                    "version" => 1, "timemodified" => time(),
                ]);
            }
        });
    }

    /**
     * Mirror server-validated quiz answers into the separate per-user JSON column.
     * Grades remain in mcp_attempts and are never calculated from client values.
     */
    public static function save_answers(int $mcpid, int $userid, int $revision, array $answers): void {
        global $DB;
        self::locked($mcpid, $userid, function () use ($DB, $mcpid, $userid, $revision, $answers) {
            $record = $DB->get_record("mcp_state", ["mcpid" => $mcpid, "userid" => $userid]);
            $userdata = $record && (int)$record->revisionid === $revision ?
                (json_decode($record->userdatajson ?? "{}", true) ?: []) : [];
            $userdata["quiz"] = ["answers" => $answers];
            $json = json_encode($userdata, JSON_INVALID_UTF8_SUBSTITUTE);
            if ($json === false || strlen($json) > 131072) {
                throw new \moodle_exception("invaliddata");
            }
            if ($record) {
                $DB->update_record("mcp_state", (object)[
                    "id" => $record->id, "revisionid" => $revision, "userdatajson" => $json,
                    "version" => (int)$record->version + 1, "timemodified" => time(),
                ]);
            } else {
                $DB->insert_record("mcp_state", (object)[
                    "mcpid" => $mcpid, "userid" => $userid, "revisionid" => $revision,
                    "location" => "", "statejson" => "{}", "userdatajson" => $json,
                    "completed" => 0, "version" => 1, "timemodified" => time(),
                ]);
            }
        });
    }

    /**
     * Only allow bounded, explicitly categorised telemetry from authored content.
     */
    public static function track(int $mcpid, int $userid, int $revision, array $payload): array {
        global $DB;
        $type = $payload["type"] ?? "";
        $action = $payload["action"] ?? "";
        $object = $payload["object"] ?? "";
        $data = $payload["result"] ?? [];
        if (!in_array($type, ["interaction", "progress", "navigation"], true) ||
                !is_string($action) || !preg_match("/^[a-z][a-z0-9_]{0,63}$/", $action) ||
                !is_string($object) || strlen($object) > 255 || !is_array($data)) {
            throw new moodle_exception("invaliddata");
        }
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false || strlen($json) > 4096) {
            throw new moodle_exception("invaliddata");
        }
        $count = $DB->count_records("mcp_tracking", ["mcpid" => $mcpid, "userid" => $userid]);
        if ($count >= 2000) {
            throw new moodle_exception("trackinglimit", "mcp");
        }
        $DB->insert_record("mcp_tracking", (object)[
            "mcpid" => $mcpid, "userid" => $userid, "revisionid" => $revision,
            "eventtype" => $type, "action" => $action, "objectid" => $object,
            "datajson" => $json, "timecreated" => time(),
        ]);
        return ["recorded" => true];
    }

    /**
     * Aggregate counts only; no classmate identities or raw free-text responses.
     */
    public static function summary(int $mcpid): array {
        global $DB;
        $sql = "SELECT eventtype, action, COUNT(*) AS total, COUNT(DISTINCT userid) AS participants
                  FROM {mcp_tracking}
                 WHERE mcpid = :mcpid
              GROUP BY eventtype, action
              ORDER BY eventtype, action";
        $rows = $DB->get_recordset_sql($sql, ["mcpid" => $mcpid]);
        $result = [];
        foreach ($rows as $record) {
            $result[] = [
                "type" => $record->eventtype,
                "action" => $record->action,
                "total" => (int)$record->total,
                "participants" => (int)$record->participants,
            ];
        }
        $rows->close();
        return $result;
    }
}
