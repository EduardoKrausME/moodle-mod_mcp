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

namespace mod_mcp\question;

use context_module;
use core\lock\lock_config;
use mod_mcp\content\metadata;
use mod_mcp\grade\service;
use mod_mcp\runtime\state;
use moodle_exception;
use stdClass;

/**
 * One server-owned JSON quiz attempt per student. No Question Engine or bank tables.
 */
final class attempts {
    /**
     * locked for JSON assessment handling.
     *
     * @return mixed Result.
     */
    private static function locked(int $mcpid, int $userid, callable $callback) {
        $factory = lock_config::get_lock_factory("mod_mcp");
        $lock = $factory->get_lock("attempt_" . $mcpid . "_" . $userid, 10);
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
     * own for JSON assessment handling.
     *
     * @return ?stdClass Result.
     */
    public static function own(int $mcpid, int $userid): ?stdClass {
        global $DB;
        return $DB->get_record("mcp_attempts", [
            "mcpid" => $mcpid, "userid" => $userid,
        ]) ?: null;
    }

    /**
     * Draw from the activity JSON with a server-side CSPRNG and freeze entire question
     * definitions (including keys) inside the private attempt record.
     */
    public static function start(stdClass $mcp, stdClass $cm, int $userid): stdClass {
        global $DB;
        $context = context_module::instance((int)$cm->id);
        if (empty($mcp->publishedrevision) || $mcp->activitytype !== "quiz" ||
                !has_capability("mod/mcp:submit", $context, $userid)) {
            throw new moodle_exception("nopermissions");
        }
        return self::locked((int)$mcp->id, $userid, function () use ($DB, $mcp, $userid) {
            if (self::own((int)$mcp->id, $userid)) {
                throw new moodle_exception("invaliddata");
            }
            $info = metadata::information($mcp);
            metadata::validate("quiz", $info);
            $chosen = selector::choose($info["questions"], $info["selection"] ?? "all",
                $info["count"] ?? 0, $info["shuffle"] ?? false);
            $now = time();
            $id = $DB->insert_record("mcp_attempts", (object)[
                "mcpid" => (int)$mcp->id, "userid" => $userid,
                "attemptnum" => 1, "revisionid" => (int)$mcp->publishedrevision,
                "selectedjson" => json_encode($chosen, JSON_INVALID_UTF8_SUBSTITUTE),
                "answersjson" => "{}", "status" => "inprogress",
                "score" => null, "timecreated" => $now, "timemodified" => $now,
            ]);
            return $DB->get_record("mcp_attempts", ["id" => $id], "*", MUST_EXIST);
        });
    }

    /**
     * Never return correct answers, fractions or points to authored HTML or a mobile app.
     */
    public static function questions(stdClass $attempt): array {
        $selected = json_decode($attempt->selectedjson, true) ?: [];
        return array_map(static function (array $q): array {
            return [
                "id" => $q["id"], "text" => $q["text"],
                "type" => $q["type"] ?? "single",
                "options" => array_map(static fn(array $opt): array => [
                    "id" => $opt["id"], "text" => $opt["text"],
                ], $q["options"] ?? []),
            ];
        }, $selected);
    }

    /**
     * status for JSON assessment handling.
     *
     * @return array Result.
     */
    public static function status(stdClass $mcp, ?stdClass $attempt): array {
        $hasquestions = $mcp->activitytype === "quiz" &&
            !empty(metadata::information($mcp)["questions"]);
        return [
            "hasquestions" => $hasquestions,
            "status" => $attempt ? $attempt->status : "notstarted",
            "canstart" => $hasquestions && !$attempt,
            "canfinish" => $attempt && $attempt->status === "inprogress",
        ];
    }

    /**
     * Validate every submitted question ID and option against the frozen attempt.
     */
    private static function merge_responses(array $selected, array $previous, array $responses): array {
        if (count($responses) > 200) {
            throw new moodle_exception("invaliddata");
        }
        $questions = [];
        foreach ($selected as $q) {
            $questions[$q["id"]] = $q;
        }
        foreach ($responses as $id => $value) {
            if (!is_string($id) || !isset($questions[$id])) {
                throw new moodle_exception("invaliddata");
            }
            $q = $questions[$id];
            $type = $q["type"] ?? "single";
            if ($type === "multiple") {
                $options = array_column($q["options"], "id");
                if (!is_array($value) || !array_is_list($value) ||
                        count($value) > count($options) ||
                        count($value) !== count(array_unique($value))) {
                    throw new moodle_exception("invaliddata");
                }
                foreach ($value as $answer) {
                    if (!is_string($answer) || !in_array($answer, $options, true)) {
                        throw new moodle_exception("invaliddata");
                    }
                }
                $previous[$id] = $value;
            } else {
                if (!is_string($value) || strlen($value) > 2048) {
                    throw new moodle_exception("invaliddata");
                }
                if ($type === "single" && !in_array($value,
                        array_column($q["options"], "id"), true)) {
                    throw new moodle_exception("invaliddata");
                }
                $previous[$id] = $value;
            }
        }
        $json = json_encode($previous, JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false || strlen($json) > 131072) {
            throw new moodle_exception("invaliddata");
        }
        return $previous;
    }

    /**
     * The answer key stays on the server; client-supplied marks are never accepted.
     */
    private static function grade(array $questions, array $answers, float $maximum): float {
        $earned = 0.0;
        $possible = 0.0;
        foreach ($questions as $q) {
            $points = (float)($q["points"] ?? 1);
            $possible += $points;
            $submitted = $answers[$q["id"]] ?? null;
            $correct = $q["answer"];
            $type = $q["type"] ?? "single";
            if ($type === "multiple" && is_array($submitted)) {
                sort($submitted);
                sort($correct);
                if ($submitted === $correct) {
                    $earned += $points;
                }
            } else if ($type === "text" && is_string($submitted) &&
                    strtolower(trim($submitted)) === strtolower(trim($correct))) {
                $earned += $points;
            } else if ($type === "single" && $submitted === $correct) {
                $earned += $points;
            }
        }
        return $possible > 0 ? round($earned / $possible * max(0, $maximum), 5) : 0.0;
    }

    /**
     * Save validated answers and optionally finish; mirror results in student userdata JSON.
     */
    public static function submit(stdClass $mcp, stdClass $attempt, array $responses, bool $finish): void {
        global $DB;
        self::locked((int)$mcp->id, (int)$attempt->userid, function () use (
                $DB, $mcp, $attempt, $responses, $finish) {
            $current = self::own((int)$mcp->id, (int)$attempt->userid);
            if (!$current || $current->status !== "inprogress") {
                throw new moodle_exception("submissionsclosed", "mcp");
            }
            $selected = json_decode($current->selectedjson, true) ?: [];
            $answers = self::merge_responses($selected,
                json_decode($current->answersjson, true) ?: [], $responses);
            $fields = (object)[
                "id" => $current->id, "answersjson" => json_encode($answers, JSON_INVALID_UTF8_SUBSTITUTE),
                "timemodified" => time(),
            ];
            if ($finish) {
                $fields->status = "finished";
                $fields->score = self::grade($selected, $answers, (float)$mcp->grade);
            }
            $DB->update_record("mcp_attempts", $fields);
            state::save_answers((int)$mcp->id, (int)$current->userid,
                (int)$current->revisionid, $answers);
        });
        if ($finish) {
            service::update($mcp, (int)$attempt->userid);
        }
    }

    /**
     * Server-rendered fallback uses escaped text and the same JSON submission API.
     */
    public static function render(stdClass $attempt): array {
        $answers = json_decode($attempt->answersjson, true) ?: [];
        $result = [];
        foreach (self::questions($attempt) as $q) {
            $id = htmlspecialchars($q["id"], ENT_QUOTES, "UTF-8");
            $html = "<fieldset><legend>" .
                htmlspecialchars($q["text"], ENT_QUOTES, "UTF-8") . "</legend>";
            $type = $q["type"];
            if ($type === "text") {
                $value = htmlspecialchars((string)($answers[$q["id"]] ?? ""), ENT_QUOTES, "UTF-8");
                $html .= "<input class=\"form-control\" type=\"text\" name=\"responses[{$id}]\" value=\"{$value}\">";
            } else {
                foreach ($q["options"] as $opt) {
                    $oid = htmlspecialchars($opt["id"], ENT_QUOTES, "UTF-8");
                    $text = htmlspecialchars($opt["text"], ENT_QUOTES, "UTF-8");
                    $multiple = $type === "multiple";
                    $selected = $answers[$q["id"]] ?? ($multiple ? [] : "");
                    $checked = $multiple ? in_array($opt["id"], (array)$selected, true) :
                        $selected === $opt["id"];
                    $inputtype = $multiple ? "checkbox" : "radio";
                    $name = "responses[{$id}]" . ($multiple ? "[]" : "");
                    $html .= "<label class=\"d-block\"><input type=\"{$inputtype}\" name=\"{$name}\" " .
                        "value=\"{$oid}\"" . ($checked ? " checked" : "") . "> {$text}</label>";
                }
            }
            $result[] = ["id" => $q["id"], "html" => $html . "</fieldset>"];
        }
        return $result;
    }
}
