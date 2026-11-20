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
 * Attach a ready native question without changing its definition.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;

/** All academic questions must exist in the Moodle bank before attaching them. */
final class attach_question extends activity_tool {
    public function get_name(): string {
        return "mcp_attach_question";
    }

    public function get_title(): string {
        return "MCP activity: attach pinned question";
    }

    public function get_description(): string {
        return "Attach a native ready question ID; optional replace_slot invalidates the old link for future attempts only.";
    }

    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "questionid" => ["type" => "integer", "minimum" => 1],
            "maxmark" => ["type" => "number", "exclusiveMinimum" => 0],
            "replace_slot" => ["type" => "integer", "minimum" => 1],
        ], ["cmid", "questionid"]);
    }

    public function preview(array $arguments, authenticated_identity $identity): array {
        $mcp = $this->activity($arguments);
        $q = \mod_mcp\question\slots::validate_question(
            (int)$arguments["questionid"], (int)$mcp->course, $identity->userid);
        return ["cmid" => (int)$arguments["cmid"], "questionid" => $q->id,
            "version" => $q->version, "replace_slot" => (int)($arguments["replace_slot"] ?? 0),
            "maxmark" => (float)($arguments["maxmark"] ?? $q->defaultmark)];
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        $mcp = $this->activity($arguments);
        $q = \mod_mcp\question\slots::validate_question(
            (int)$arguments["questionid"], (int)$mcp->course, $identity->userid);
        return \mod_mcp\question\slots::attach(
            (int)$mcp->id, (int)$q->id, (float)($arguments["maxmark"] ?? $q->defaultmark),
            $identity->userid, (int)($arguments["replace_slot"] ?? 0));
    }
}

