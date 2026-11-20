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
 * Save an immutable HTML draft through the existing MCP confirmation flow.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\content\service;

/**
 * Save a new revision without making it visible to students.
 */
final class save_content extends activity_tool {
    /**
     * get name.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_save_content";
    }

    /**
     * get title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: save HTML draft";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Save one complete HTML document with inline CSS and JavaScript, without publishing.";
    }

    /**
     * get input schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "html" => ["type" => "string", "minLength" => 1],
            "expectedrevision" => ["type" => "integer", "minimum" => 0],
        ], ["cmid", "html", "expectedrevision"]);
    }

    /**
     * preview.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $activity = $this->activity($arguments);
        return ["cmid" => $arguments["cmid"], "draftrevision" => (int)$activity->draftrevision,
            "expectedrevision" => (int)$arguments["expectedrevision"],
            "bytes" => strlen($arguments["html"]), "sha256" => hash("sha256", $arguments["html"]),
            "students_affected" => false];
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
        $mcp = $this->activity($arguments);
        $revision = service::save(
            (int)$mcp->id, $arguments["html"], $identity->userid, (int)$arguments["expectedrevision"]);
        return ["cmid" => (int)$arguments["cmid"], "draftrevision" => $revision,
            "published" => false];
    }
}
