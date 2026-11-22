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
use mod_mcp\content\metadata;
use mod_mcp\content\service;

/**
 * Manager-only read access to HTML and authored JSON, including answer keys.
 */
final class get_activity extends base_tool {
    /**
     * get name.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_get_activity";
    }

    /**
     * get title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: read HTML and JSON information";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Read activity type, editable JSON information, selection settings and HTML revisions.";
    }

    /**
     * get required capability.
     *
     * @return string Result.
     */
    public function get_required_capability(): string {
        return "mod/mcp:manage";
    }

    /**
     * get input schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema(["cmid" => ["type" => "integer", "minimum" => 1]], ["cmid"]);
    }

    /**
     * resolve context.
     *
     * @param mixed $arguments Input arguments.
     * @return context Result.
     */
    public function resolve_context(array $arguments): context {
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        return context_module::instance($cm->id);
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        global $DB;
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $revision = service::get($mcp->id, (int)$mcp->draftrevision);
        $published = service::get($mcp->id, (int)$mcp->publishedrevision);
        return [
            "cmid" => (int)$cm->id,
            "courseid" => (int)$mcp->course,
            "name" => $mcp->name,
            "draftrevision" => (int)$mcp->draftrevision,
            "publishedrevision" => (int)$mcp->publishedrevision,
            "draftsha256" => $revision ? $revision->sha256 : null,
            "html" => $revision ? untrusted_content::text($revision->content) : "",
            "publishedsha256" => $published ? $published->sha256 : null,
            "questionselection" => json_decode($mcp->informationjson ?? "{}", true)["selection"] ?? "all",
            "questionsperattempt" => json_decode($mcp->informationjson ?? "{}", true)["count"] ?? 0,
            "shufflequestions" => json_decode($mcp->informationjson ?? "{}", true)["shuffle"] ?? false,
            "type" => $mcp->activitytype,
            "information" => $mcp->informationjson === null ? null :
                json_decode($mcp->informationjson, true),
            "settingshash" => metadata::fingerprint($mcp),
        ];
    }
}
