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
 * Read MCP activity, revision and pinned questions.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\read;

use context;
use context_module;
use local_mcp\read\tool\base_tool;
use local_mcp\security\authenticated_identity;
use local_mcp\security\untrusted_content;

/** READ access to the editable revision without exposing question answers. */
final class get_activity extends base_tool {
    public function get_name(): string {
        return "mcp_get_activity";
    }

    public function get_title(): string {
        return "MCP activity: get content and question IDs";
    }

    public function get_description(): string {
        return "Read an existing mod_mcp activity draft, published revision and question slots.";
    }

    public function get_required_capability(): string {
        return "mod/mcp:manage";
    }

    public function get_input_schema(): array {
        return $this->object_schema(["cmid" => ["type" => "integer", "minimum" => 1]], ["cmid"]);
    }

    public function resolve_context(array $arguments): context {
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        return context_module::instance($cm->id);
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        global $DB;
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $revision = \mod_mcp\content\service::get($mcp->id, (int)$mcp->draftrevision);
        $published = \mod_mcp\content\service::get($mcp->id, (int)$mcp->publishedrevision);
        return [
            "cmid" => (int)$cm->id,
            "courseid" => (int)$mcp->course,
            "name" => $mcp->name,
            "draftrevision" => (int)$mcp->draftrevision,
            "publishedrevision" => (int)$mcp->publishedrevision,
            "draftsha256" => $revision ? $revision->sha256 : null,
            "html" => $revision ? untrusted_content::text($revision->content) : "",
            "publishedsha256" => $published ? $published->sha256 : null,
            "questions" => array_map(static fn($slot) => [
                "slot" => (int)$slot->slot, "questionid" => (int)$slot->questionid,
                "version" => (int)$slot->questionversion, "maxmark" => (float)$slot->maxmark,
            ], \mod_mcp\question\slots::active($mcp->id)),
        ];
    }
}

