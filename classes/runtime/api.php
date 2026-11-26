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
 * Server-owned operations exposed through the iframe bridge.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\runtime;

use completion_info;
use context_module;
use mod_mcp\question\attempts;
use mod_mcp\content\metadata;
use moodle_exception;
use stdClass;

/**
 * Single allowlisted dispatch table. The client can never select another user or module.
 */
final class api {
    /**
     * Visible course-module order is calculated by the Moodle course cache.
     */
    public static function activities(stdClass $cm): array {
        global $USER;
        $modinfo = get_fast_modinfo((int)$cm->course, (int)$USER->id);
        $items = [];
        foreach ($modinfo->get_sections() as $sectionnum => $moduleids) {
            $section = $modinfo->get_section_info($sectionnum);
            if (!$section || !$section->uservisible) {
                continue;
            }
            foreach ($moduleids as $id) {
                $item = $modinfo->get_cm($id);
                if (!$item->uservisible || !$item->url || $item->is_stealth()) {
                    continue;
                }
                $items[] = [
                    "cmid" => (int)$item->id,
                    "name" => format_string($item->name, true,
                        ["context" => context_module::instance((int)$item->id)]),
                    "modname" => $item->modname,
                    "section" => (int)$sectionnum,
                    "url" => $item->url->out(false),
                ];
            }
        }
        return $items;
    }

    /**
     * Find previous and next reachable content in the learner's visible ordering.
     */
    public static function navigation(stdClass $cm): array {
        $items = self::activities($cm);
        $index = null;
        foreach ($items as $i => $item) {
            if ($item["cmid"] === (int)$cm->id) {
                $index = $i;
                break;
            }
        }
        return [
            "previous" => $index !== null && $index > 0 ? $items[$index - 1] : null,
            "next" => $index !== null && $index < count($items) - 1 ? $items[$index + 1] : null,
        ];
    }

    /**
     * Read the actual course completion record, not a flag forged by the document.
     */
    public static function completion(stdClass $mcp, stdClass $cm): array {
        global $USER;
        $course = get_course((int)$cm->course);
        $info = new completion_info($course);
        $modinfo = get_fast_modinfo((int)$cm->course, (int)$USER->id);
        $module = $modinfo->get_cm((int)$cm->id);
        $enabled = $info->is_enabled($module);
        $data = $enabled ? $info->get_data($module, false, (int)$USER->id) : null;
        $manual = (int)$module->completion === COMPLETION_TRACKING_MANUAL;
        $custom = (int)$module->completion === COMPLETION_TRACKING_AUTOMATIC &&
            !empty($mcp->completioninteractive);
        return [
            "enabled" => (bool)$enabled,
            "mode" => $manual ? "manual" : ($custom ? "interactive" : "native"),
            "state" => $data ? (int)$data->completionstate : COMPLETION_INCOMPLETE,
            "completed" => $data && (int)$data->completionstate !== COMPLETION_INCOMPLETE,
            "canrequest" => (bool)$enabled && ($manual || $custom),
        ];
    }

    /**
     * Moodle is the sole authority for applying completion rules.
     */
    private static function complete(stdClass $mcp, stdClass $cm): array {
        global $USER;
        $current = self::completion($mcp, $cm);
        if (!$current["canrequest"]) {
            throw new moodle_exception("completionnotallowed", "mcp");
        }
        if ($current["mode"] === "interactive") {
            // An authored page may request completion, but cannot bypass a native assessment.
            if ($mcp->activitytype === "quiz") {
                $attempt = attempts::own((int)$mcp->id, (int)$USER->id);
                if (!$attempt || $attempt->status !== "finished") {
                    throw new moodle_exception("completionnotallowed", "mcp");
                }
            }
            state::complete((int)$mcp->id, (int)$USER->id, (int)$mcp->publishedrevision);
        }
        $info = new completion_info(get_course((int)$cm->course));
        $module = get_fast_modinfo((int)$cm->course, (int)$USER->id)->get_cm((int)$cm->id);
        $info->update_state($module, COMPLETION_COMPLETE, (int)$USER->id);
        return self::completion($mcp, $cm);
    }

    /**
     * The existing attempt APIs now use immutable question selections stored as JSON.
     */
    private static function attempt(stdClass $mcp): array {
        global $USER;
        return attempts::status($mcp, attempts::own((int)$mcp->id, (int)$USER->id));
    }

    /**
     * A question response is a scalar or list of option identifiers.
     */
    private static function responses(array $payload): array {
        $responses = $payload["responses"] ?? null;
        if (!is_array($responses) || count($responses) > 200) {
            throw new moodle_exception("invaliddata");
        }
        return $responses;
    }

    /**
     * Execute an explicitly supported operation.
     */
    public static function run(stdClass $mcp, stdClass $cm, string $action, array $payload): array {
        global $USER;
        $context = context_module::instance((int)$cm->id);
        $userid = (int)$USER->id;
        $mcpid = (int)$mcp->id;
        $revision = (int)$mcp->publishedrevision;
        switch ($action) {
            case "activity.getContext":
                return [
                    "cmid" => (int)$cm->id, "courseid" => (int)$cm->course,
                    "language" => current_language(),
                    "revision" => $revision,
                    "type" => $mcp->activitytype,
                    "information" => metadata::public_information($mcp),
                ];
            case "course.getActivities":
                return ["activities" => self::activities($cm)];
            case "course.getNavigation":
                return self::navigation($cm);
            case "completion.get":
                return self::completion($mcp, $cm);
            case "completion.complete":
                require_capability("mod/mcp:submit", $context);
                return self::complete($mcp, $cm);
            case "progress.load":
                return state::load($mcpid, $userid, $revision);
            case "progress.save":
                require_capability("mod/mcp:submit", $context);
                return state::save($mcpid, $userid, $revision, $payload);
            case "progress.getStatus":
                $completion = self::completion($mcp, $cm);
                return state::status($mcpid, $userid, $revision, (bool)$completion["completed"]);
            case "progress.saveStatus":
                require_capability("mod/mcp:submit", $context);
                state::save_status($mcpid, $userid, $revision, $payload);
                $completion = self::completion($mcp, $cm);
                return state::status($mcpid, $userid, $revision, (bool)$completion["completed"]);
            case "tracking.record":
                require_capability("mod/mcp:submit", $context);
                return state::track($mcpid, $userid, $revision, $payload);
            case "tracking.summary":
                require_capability("mod/mcp:manage", $context);
                return ["summary" => state::summary($mcpid)];
            case "attempt.get":
                require_capability("mod/mcp:submit", $context);
                return self::attempt($mcp);
            case "attempt.start":
                require_capability("mod/mcp:submit", $context);
                attempts::start($mcp, $cm, $userid);
                return self::attempt($mcp);
            case "attempt.finish":
                require_capability("mod/mcp:submit", $context);
                $attempt = attempts::own($mcpid, $userid);
                if (!$attempt) {
                    throw new moodle_exception("invaliddata");
                }
                attempts::submit($mcp, $attempt, [], true);
                return self::attempt($mcp);
            case "questions.get":
                require_capability("mod/mcp:submit", $context);
                $attempt = attempts::own($mcpid, $userid);
                return [
                    "attempt" => self::attempt($mcp),
                    "questions" => $attempt && $attempt->status === "inprogress" ?
                        array_map(static function($question, $rendered) {
                            return $question + ["html" => $rendered["html"]];
                        }, attempts::questions($attempt), attempts::render($attempt)) : [],
                ];
            case "questions.submit":
                require_capability("mod/mcp:submit", $context);
                $attempt = attempts::own($mcpid, $userid);
                if (!$attempt) {
                    throw new moodle_exception("invaliddata");
                }
                $finish = ($payload["finish"] ?? false) === true;
                attempts::submit($mcp, $attempt, self::responses($payload), $finish);
                return self::attempt($mcp);
            default:
                throw new moodle_exception("invalidparameter");
        }
    }
}
