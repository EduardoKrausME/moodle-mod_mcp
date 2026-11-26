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

namespace mod_mcp\mcp\write;

use mod_mcp\mcp\authoring_error;
use local_mcp\security\authenticated_identity;
use mod_mcp\content\metadata;
use mod_mcp\mcp\authoring_guidance;
use moodle_exception;

/**
 * ChatGPT/Claude write tool; the information JSON never goes through a question bank.
 * @package mod_mcp
 */
final class save_information extends activity_tool {
    /**
     * get name for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_save_information";
    }

    /**
     * get title for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: save type and JSON information";
    }

    /**
     * get description for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return authoring_guidance::tool_description(
            "Save nullable activity type and private JSON information, including graded quiz "
            . "questions and answer keys. Use mcp_get_activity to obtain expectedhash first; "
            . "never embed answer keys in HTML or grade client-side. Confirmation required.",
            ["core", "quiz"]);
    }

    /**
     * get input schema for JSON assessment handling.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "type" => ["type" => ["string", "null"], "maxLength" => 40],
            "information" => ["type" => ["object", "null"]],
            "expectedhash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
        ], ["cmid", "type", "information", "expectedhash"]);
    }

    /**
     * preview for JSON assessment handling.
     *
     * @param array $arguments Parameter.
     * @param authenticated_identity $identity Parameter.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        try {
            $mcp = $this->activity($arguments);
            $hash = metadata::fingerprint($mcp);
            if (!hash_equals($hash, (string)$arguments["expectedhash"])) {
                throw new moodle_exception("revisionconflict", "mcp");
            }
            $json = metadata::validate($arguments["type"], $arguments["information"]);
            return [
                "cmid" => (int)$arguments["cmid"], "type" => $arguments["type"],
                "questions" => $arguments["type"] === "quiz" ?
                    count($arguments["information"]["questions"] ?? []) : 0,
                "information_sha256" => hash("sha256", (string)$json),
                "bytes" => strlen((string)$json), "expectedhash" => $hash,
                "active_content_affected" => !empty($mcp->publishedrevision),
            ];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }

    /**
     * execute for JSON assessment handling.
     *
     * @param array $arguments Parameter.
     * @param authenticated_identity $identity Parameter.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        try {
            $this->resolve_context($arguments);
            $mcp = $this->activity($arguments);
            $hash = metadata::save((int)$mcp->id, $arguments["type"],
                $arguments["information"], $arguments["expectedhash"]);
            return ["cmid" => (int)$arguments["cmid"], "type" => $arguments["type"],
                "settingshash" => $hash];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }
}
