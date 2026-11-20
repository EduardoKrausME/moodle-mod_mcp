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
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Publish a confirmed MCP activity revision.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;

/** Explicit publish operation, separate from editing HTML. */
final class publish extends activity_tool {
    public function get_name(): string {
        return "mcp_publish";
    }

    public function get_title(): string {
        return "MCP activity: publish";
    }

    public function get_description(): string {
        return "Publish exactly the expected draft revision to students, and unhide the module.";
    }

    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "revision" => ["type" => "integer", "minimum" => 1],
        ], ["cmid", "revision"]);
    }

    public function preview(array $arguments, authenticated_identity $identity): array {
        $activity = $this->activity($arguments);
        return ["cmid" => (int)$arguments["cmid"], "revision" => (int)$arguments["revision"],
            "currentdraft" => (int)$activity->draftrevision,
            "questions" => count(\mod_mcp\question\slots::active((int)$activity->id)),
            "visible_to_students_after_confirmation" => true];
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        $mcp = $this->activity($arguments);
        \mod_mcp\content\service::publish((int)$mcp->id, (int)$arguments["revision"]);
        return ["cmid" => (int)$arguments["cmid"], "publishedrevision" => (int)$arguments["revision"]];
    }
}

