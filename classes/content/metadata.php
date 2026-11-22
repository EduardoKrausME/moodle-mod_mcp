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
 * JSON activity assessments and metadata.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\content;

use moodle_exception;
use stdClass;

/**
 * Portable nullable JSON metadata, with a strict quiz schema.
 * Never give a learner the author-owned answer key.
  * @package mod_mcp
 */
final class metadata {
    /**
     * validate for JSON assessment handling.
     *
     * @return ?string Result.
     */
    public static function validate(?string $type, $information): ?string {
        if ($type === "") {
            $type = null;
        }
        if ($type !== null && !preg_match('/^[a-z][a-z0-9_]{0,39}$/', $type)) {
            throw new moodle_exception("invaliddata");
        }
        if ($information === null) {
            if ($type === "quiz") {
                throw new moodle_exception("questionnotconfigured", "mcp");
            }
            return null;
        }
        if (is_string($information)) {
            $information = json_decode($information, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new moodle_exception("invaliddata");
            }
        }
        if (!is_array($information) || ($information !== [] && array_is_list($information))) {
            throw new moodle_exception("invaliddata");
        }
        $json = $information === [] ? "{}" :
            json_encode($information, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        if ($json === false || strlen($json) > 1048576) {
            throw new moodle_exception("invaliddata");
        }
        if ($type === "quiz") {
            self::validate_quiz($information);
        }
        return $json;
    }

    /**
     * validate quiz for JSON assessment handling.
     *
     * @return void Result.
     */
    private static function validate_quiz(array $info): void {
        $questions = $info["questions"] ?? null;
        if (!is_array($questions) || !array_is_list($questions) ||
                count($questions) < 1 || count($questions) > 200) {
            throw new moodle_exception("questionnotconfigured", "mcp");
        }
        $selection = $info["selection"] ?? "all";
        $count = $info["count"] ?? 0;
        $shuffle = $info["shuffle"] ?? false;
        if (!in_array($selection, ["all", "random"], true) ||
                !is_int($count) || $count < 0 ||
                ($selection === "random" && ($count < 1 || $count > count($questions))) ||
                !is_bool($shuffle)) {
            throw new moodle_exception("invalidquestionconfiguration", "mcp");
        }
        $ids = [];
        foreach ($questions as $q) {
            if (!is_array($q) || !is_string($q["id"] ?? null) ||
                    !preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $q["id"]) ||
                    isset($ids[$q["id"]]) || !is_string($q["text"] ?? null) ||
                    trim($q["text"]) === "" || strlen($q["text"]) > 8000) {
                throw new moodle_exception("invaliddata");
            }
            $ids[$q["id"]] = true;
            $type = $q["type"] ?? "single";
            $points = $q["points"] ?? 1;
            if (!in_array($type, ["single", "multiple", "text"], true) ||
                    !is_numeric($points) || (float)$points <= 0 || (float)$points > 1000) {
                throw new moodle_exception("invaliddata");
            }
            if ($type === "text") {
                if (!is_string($q["answer"] ?? null) || trim($q["answer"]) === "" ||
                        strlen($q["answer"]) > 2048) {
                    throw new moodle_exception("invaliddata");
                }
                continue;
            }
            $options = $q["options"] ?? null;
            if (!is_array($options) || !array_is_list($options) ||
                    count($options) < 2 || count($options) > 20) {
                throw new moodle_exception("invaliddata");
            }
            $choices = [];
            foreach ($options as $opt) {
                if (!is_array($opt) || !is_string($opt["id"] ?? null) ||
                        !preg_match('/^[A-Za-z][A-Za-z0-9_-]{0,63}$/', $opt["id"]) ||
                        isset($choices[$opt["id"]]) || !is_string($opt["text"] ?? null) ||
                        trim($opt["text"]) === "" || strlen($opt["text"]) > 4096) {
                    throw new moodle_exception("invaliddata");
                }
                $choices[$opt["id"]] = true;
            }
            $answer = $q["answer"] ?? null;
            if ($type === "single" && (!is_string($answer) || !isset($choices[$answer]))) {
                throw new moodle_exception("invaliddata");
            }
            if ($type === "multiple" && (!is_array($answer) || !array_is_list($answer) ||
                    !$answer || count($answer) !== count(array_unique($answer)))) {
                throw new moodle_exception("invaliddata");
            }
            if ($type === "multiple") {
                foreach ($answer as $a) {
                    if (!is_string($a) || !isset($choices[$a])) {
                        throw new moodle_exception("invaliddata");
                    }
                }
            }
        }
    }

    /**
     * information for JSON assessment handling.
     *
     * @return ?array Result.
     */
    public static function information(stdClass $mcp): ?array {
        return empty($mcp->informationjson) ? null : json_decode($mcp->informationjson, true);
    }

    /**
     * Allowlist the quiz fields. In particular, never return answer or points.
     */
    public static function public_information(stdClass $mcp): ?array {
        $info = self::information($mcp);
        if ($info === null || $mcp->activitytype !== "quiz") {
            return $info;
        }
        return [
            "selection" => $info["selection"] ?? "all",
            "count" => $info["count"] ?? 0,
            "shuffle" => $info["shuffle"] ?? false,
            "questions" => array_map(static function(array $q): array {
                return [
                    "id" => $q["id"], "text" => $q["text"],
                    "type" => $q["type"] ?? "single",
                    "options" => array_map(static fn(array $option): array => [
                        "id" => $option["id"], "text" => $option["text"],
                    ], $q["options"] ?? []),
                ];
            }, $info["questions"]),
        ];
    }

    /**
     * fingerprint for JSON assessment handling.
     *
     * @return string Result.
     */
    public static function fingerprint(stdClass $mcp): string {
        return hash("sha256", (string)$mcp->activitytype . "|" .
            (string)$mcp->informationjson . "|" . (string)$mcp->timemodified);
    }

    /**
     * save for JSON assessment handling.
     *
     * @return string Result.
     */
    public static function save(int $mcpid, ?string $type, $information, ?string $expectedhash = null): string {
        global $DB;
        $mcp = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
        if ($expectedhash !== null && !hash_equals(self::fingerprint($mcp), $expectedhash)) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        $json = self::validate($type, $information);
        $DB->update_record("mcp", (object)[
            "id" => $mcpid, "activitytype" => $type === "" ? null : $type,
            "informationjson" => $json, "timemodified" => time(),
        ]);
        return self::fingerprint($DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST));
    }
}
