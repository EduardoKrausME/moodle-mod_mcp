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
 * Save an immutable HTML revision through the existing MCP confirmation flow.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use mod_mcp\mcp\authoring_error;
use moodle_exception;
use local_mcp\security\authenticated_identity;
use mod_mcp\content\service;
use mod_mcp\content\metadata;
use mod_mcp\mcp\authoring_guidance;

/**
 * Replace active content without changing module visibility.
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
        return "MCP activity: update HTML content";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return authoring_guidance::tool_description(
            "Replace the active HTML with one complete document with inline CSS/JS. First call "
            . "mcp_get_activity and follow its course-specific authoring instructions. The "
            . "server rejects hard-coded brand colours; fix errors and retry. "
            . "Use the current revision for concurrency protection. Preserve native visibility. Include type, information "
            . "and expectedhash in this call if private assessment information must also change.",
            ["core", "html", "sdk", "approval", "theme", "material"]);
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
            "type" => ["type" => ["string", "null"], "maxLength" => 40],
            "information" => ["type" => ["object", "null"]],
            "expectedhash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
        ], ["cmid", "html", "expectedrevision"]);
    }

    /**
     * Optional private information must be supplied as one complete, guarded change.
     *
     * @param array $arguments Input arguments.
     * @return array|null
     */
    private function settings(array $arguments): ?array {
        $keys = ["type", "information", "expectedhash"];
        $settings = array_intersect_key($arguments, array_flip($keys));
        if (!$settings) {
            return null;
        }
        if (count($settings) !== count($keys)) {
            throw new moodle_exception("invaliddata");
        }
        metadata::validate($settings["type"], $settings["information"]);
        return $settings;
    }

    /**
     * preview.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        try {
            require_capability("mod/mcp:manage", $this->resolve_context($arguments), $identity->userid);
            $activity = $this->activity($arguments);
            $settings = $this->settings($arguments);
            if ($settings !== null && !hash_equals(metadata::fingerprint($activity), $settings["expectedhash"])) {
                throw new moodle_exception("revisionconflict", "mcp");
            }
            $html = $arguments["html"];
            service::validate_html($html);
            service::validate_theme_colours((int)$activity->course, $html);
            if ((int)$activity->publishedrevision !== (int)$arguments["expectedrevision"]) {
                throw new moodle_exception("revisionconflict", "mcp");
            }
            return ["cmid" => $arguments["cmid"], "revision" => (int)$activity->publishedrevision,
                "expectedrevision" => (int)$arguments["expectedrevision"], "updates_active_content" => true,
                "preserves_visibility" => true, "updates_information" => $settings !== null,
                "authoring" => authoring_guidance::course_context((int)$activity->course)];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        try {
            require_capability("mod/mcp:manage", $this->resolve_context($arguments), $identity->userid);
            $mcp = $this->activity($arguments);
            $authoring = authoring_guidance::course_context((int)$mcp->course);
            $revision = service::save(
                (int)$mcp->id, $arguments["html"], $identity->userid, (int)$arguments["expectedrevision"],
                $this->settings($arguments));
            $stored = service::get((int)$mcp->id, $revision);
            if (!$stored) {
                throw new moodle_exception("invaliddata");
            }
            return ["cmid" => (int)$arguments["cmid"], "revision" => $revision, "updated" => true,
                "preserves_visibility" => true, "authoring" => $authoring];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }
}
