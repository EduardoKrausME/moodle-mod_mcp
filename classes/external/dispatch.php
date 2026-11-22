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
 * Authenticated AJAX endpoint for embedded HTML operations.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mcp\runtime\api;
use moodle_exception;

/**
 * A single whitelisted endpoint; neither session nor the target user is supplied by the iframe.
 */
final class dispatch extends external_api {
    /**
     * Parameter definition.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            "cmid" => new external_value(PARAM_INT, "Current course-module ID"),
            "action" => new external_value(PARAM_TEXT, "Allowlisted API action"),
            "payload" => new external_value(PARAM_RAW, "Bounded JSON argument object"),
            "preview" => new external_value(PARAM_BOOL, "Draft preview", VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Require the current Moodle user and enforce the module context.
     */
    public static function execute(int $cmid, string $action, string $payload, bool $preview = false): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), [
            "cmid" => $cmid, "action" => $action, "payload" => $payload, "preview" => $preview,
        ]);
        if (strlen($payload) > 262144 || strlen($action) > 64 ||
                !preg_match("/^[a-z][a-zA-Z.]+$/", $action)) {
            throw new moodle_exception("invalidparameter");
        }
        $cm = get_coursemodule_from_id("mcp", $params["cmid"], 0, false, MUST_EXIST);
        $course = get_course((int)$cm->course);
        require_login($course, true, $cm);
        $context = context_module::instance((int)$cm->id);
        self::validate_context($context);
        require_capability("mod/mcp:view", $context);
        $mcp = $DB->get_record("mcp", ["id" => (int)$cm->instance], "*", MUST_EXIST);
        $canmanage = has_capability("mod/mcp:manage", $context);
        if (!$mcp->publishedrevision && !$canmanage) {
            throw new moodle_exception("activitynotpublished", "mcp");
        }
        if ($preview && !$canmanage) {
            throw new moodle_exception("nopermissions");
        }
        $args = json_decode($payload, true);
        if (!is_array($args) || json_last_error() !== JSON_ERROR_NONE) {
            throw new moodle_exception("invalidparameter");
        }
        if ($preview) {
            // Preview HTML cannot mutate academic records.
            if (!in_array($action, ["activity.getContext", "course.getActivities",
                    "course.getNavigation", "completion.get", "progress.load",
                    "progress.getStatus", "attempt.get"], true)) {
                throw new moodle_exception("nopermissions");
            }
        } else if (!$mcp->publishedrevision) {
            throw new moodle_exception("activitynotpublished", "mcp");
        }
        $result = api::run($mcp, $cm, $action, $args);
        return ["json" => json_encode($result, JSON_INVALID_UTF8_SUBSTITUTE)];
    }

    /**
     * Result definition.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            "json" => new external_value(PARAM_RAW, "JSON result"),
        ]);
    }
}
