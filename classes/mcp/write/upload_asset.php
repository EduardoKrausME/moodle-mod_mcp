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
 * MCP asset upload using Moodle File API.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\assets\service;

/**
 * Explicitly allowlisted media files become Moodle file records.
 */
final class upload_asset extends activity_tool {
    /**
     * get name.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_upload_asset";
    }

    /**
     * get title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: upload media asset";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Upload PNG, JPG, GIF, WebP, MP3, WAV, OGG, MP4, WebM or PDF (max 8 MiB).";
    }

    /**
     * get input schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "filename" => ["type" => "string", "minLength" => 1],
            "base64" => ["type" => "string", "minLength" => 1],
        ], ["cmid", "filename", "base64"]);
    }

    /**
     * preview.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $this->activity($arguments);
        return ["cmid" => (int)$arguments["cmid"],
            "filename" => clean_param($arguments["filename"], PARAM_FILE),
            "encodedbytes" => strlen($arguments["base64"])];
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        return service::upload((int)$arguments["cmid"],
            $arguments["filename"], $arguments["base64"], (int)$identity->userid);
    }
}
