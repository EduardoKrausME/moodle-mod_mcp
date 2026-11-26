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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Active activity content for the Moodle mobile app.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\external;

use context_module;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mcp\content\service;
use mod_mcp\content\metadata;
use mod_mcp\output\activity;
use moodle_exception;
use moodle_url;

/**
 * Read-only mobile export of the active revision.
 * Never export historical source, question answers, session cookies or access tokens.
 */
final class mobile_content extends external_api {
    /**
     * Defines the required parameters for retrieving active mobile content.
     *
     * @return external_function_parameters Parameter definition.
     */
    public static function get_parameters(): external_function_parameters {
        return new external_function_parameters([
            "cmid" => new external_value(PARAM_INT, "Course module ID"),
        ]);
    }

    /**
     * Retrieves the active activity revision for the mobile app.
     *
     * @param int $cmid Course module ID.
     * @return array Published revision content and asset URLs.
     */
    public static function get(int $cmid): array {
        global $DB;

        $params = self::validate_parameters(self::get_parameters(), ["cmid" => $cmid]);
        $cm = get_coursemodule_from_id("mcp", $params["cmid"], 0, false, MUST_EXIST);
        $course = get_course((int)$cm->course);
        require_login($course, true, $cm);
        $context = context_module::instance((int)$cm->id);
        self::validate_context($context);
        require_capability("mod/mcp:view", $context);

        $mcp = $DB->get_record("mcp", ["id" => (int)$cm->instance], "*", MUST_EXIST);
        if (empty($mcp->publishedrevision)) {
            throw new moodle_exception("missingcontent", "mcp");
        }
        $revision = service::get((int)$mcp->id, (int)$mcp->publishedrevision);
        if (!$revision) {
            throw new moodle_exception("missingcontent", "mcp");
        }

        $assets = [];
        foreach (get_file_storage()->get_area_files(
            $context->id, "mod_mcp", "assets", false, "id", false
        ) as $file) {
            if ($file->is_directory()) {
                continue;
            }
            $assets[] = [
                "url" => moodle_url::make_pluginfile_url(
                    $context->id, "mod_mcp", "assets", $file->get_itemid(),
                    $file->get_filepath(), $file->get_filename()
                )->out(false),
            ];
        }

        return [
            "cmid" => (int)$cm->id,
            "revision" => (int)$mcp->publishedrevision,
            "sha256" => $revision->sha256,
            "html" => activity::inject_sdk($revision->content),
            "type" => (string)($mcp->activitytype ?? ""),
            "information" => json_encode(metadata::public_information($mcp), JSON_INVALID_UTF8_SUBSTITUTE),
            "assets" => $assets,
        ];
    }

    /**
     * Defines the active mobile content response structure.
     *
     * @return external_single_structure Response definition.
     */
    public static function get_returns(): external_single_structure {
        return new external_single_structure([
            "cmid" => new external_value(PARAM_INT, "Course module ID"),
            "revision" => new external_value(PARAM_INT, "Active immutable revision ID"),
            "sha256" => new external_value(PARAM_ALPHANUMEXT, "Content SHA-256"),
            "html" => new external_value(PARAM_RAW, "Sandboxed HTML with the injected SDK"),
            "type" => new external_value(PARAM_TEXT, "Activity type, blank when unset"),
            "information" => new external_value(PARAM_RAW, "JSON without private quiz answers"),
            "assets" => new external_multiple_structure(new external_single_structure([
                "url" => new external_value(PARAM_URL, "Authorized Moodle asset URL"),
            ])),
        ]);
    }
}
