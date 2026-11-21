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
            return ["version" => 0, "location" => "", "data" => [], "completed" => false];
        }
        $same = (int)$record->revisionid === $revision;
        return [
            "version" => (int)$record->version,
            "location" => $same ? $record->location : "",
            "data" => $same ? (json_decode($record->statejson, true) ?: []) : [],
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
        if (!is_string($location) || strlen($location) > 255 ||
                !is_array($data) || !is_int($expected) || $expected < 0) {
            throw new moodle_exception("invaliddata");
        }
        $json = json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false || strlen($json) > 12288) {
            throw new moodle_exception("invaliddata");
        }
        return self::locked($mcpid, $userid, function () use (
                $DB, $mcpid, $userid, $revision, $location, $json, $expected) {
            $record = $DB->get_record("mcp_state", ["mcpid" => $mcpid, "userid" => $userid]);
            $version = $record ? (int)$record->version : 0;
            if ($version !== $expected) {
                throw new moodle_exception("progressconflict", "mcp");
            }
            if ($record) {
                $DB->update_record("mcp_state", (object)[
                    "id" => $record->id, "revisionid" => $revision,
                    "location" => $location, "statejson" => $json,
                    "version" => $version + 1, "timemodified" => time(),
                ]);
            } else {
                $DB->insert_record("mcp_state", (object)[
                    "mcpid" => $mcpid, "userid" => $userid, "revisionid" => $revision,
                    "location" => $location, "statejson" => $json,
                    "completed" => 0, "version" => 1, "timemodified" => time(),
                ]);
            }
            return self::load($mcpid, $userid, $revision);
        });
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
                    "location" => "", "statejson" => "{}", "completed" => 1,
                    "version" => 1, "timemodified" => time(),
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
                !is_string($action) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/', $action) ||
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
