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
 * Register a reusable authoring style through the Moodle MCP confirmation flow.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\mcp\write;

use context;
use context_course;
use context_system;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;
use mod_mcp\authoring\material_theme;
use mod_mcp\mcp\authoring_guidance;
use moodle_exception;

final class configure_material_theme extends base_tool {
    public function get_name(): string {
        return "mcp_configure_material_theme";
    }

    public function get_title(): string {
        return "MCP activity: configure material theme";
    }

    public function get_description(): string {
        return "FIRST-USE SETUP: Before the first mod_mcp material, ask the teacher to "
            . "register a reusable visual theme (name and visual style). "
            . "scope=course applies to one course; scope=site sets the default for all "
            . "courses (administrator only). Course overrides win. Saves to mod_mcp "
            . "config_plugins without changing the Moodle UI theme. Follow the active "
            . "Moodle theme CSS variables instead of inventing fixed brand colours. "
            . "The selected visual style is automatically reused on future materials.";
    }

    public function get_required_capability(): string {
        return "moodle/course:manageactivities";
    }

    public function get_input_schema(): array {
        return $this->object_schema([
            "scope" => ["type" => "string", "enum" => ["site", "course"]],
            "courseid" => ["type" => "integer", "minimum" => 1,
                "description" => "Required for course scope; omit for site scope."],
            "name" => ["type" => "string", "minLength" => 1, "maxLength" => 160],
            "style" => ["type" => "string", "minLength" => 1, "maxLength" => 3000,
                "description" => "Reusable visual rules for typography, spacing, cards, images and layout."],
        ], ["scope", "name", "style"]);
    }

    public function resolve_context(array $arguments): context {
        if ($arguments["scope"] === "site") {
            return context_system::instance();
        }
        if ($arguments["scope"] !== "course" || empty($arguments["courseid"])) {
            throw new moodle_exception("invalidparameter");
        }
        return context_course::instance((int)$arguments["courseid"], MUST_EXIST);
    }

    private function authorize(array $arguments, authenticated_identity $identity): void {
        $context = $this->resolve_context($arguments);
        $capability = $arguments["scope"] === "site" ?
            "moodle/site:config" : "moodle/course:manageactivities";
        require_capability($capability, $context, $identity->userid);
    }

    public function preview(array $arguments, authenticated_identity $identity): array {
        $this->authorize($arguments, $identity);
        $scope = $arguments["scope"];
        $courseid = (int)($arguments["courseid"] ?? 0);
        $previous = $scope === "site" ? material_theme::site_default() :
            material_theme::course_override($courseid);
        return [
            "scope" => $scope,
            "courseid" => $scope === "course" ? $courseid : null,
            "previous" => $previous,
            "proposed" => material_theme::definition($arguments["name"], $arguments["style"]),
            "changes_moodle_ui_theme" => false,
        ];
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->authorize($arguments, $identity);
        $courseid = (int)($arguments["courseid"] ?? 0);
        $saved = material_theme::save($arguments["scope"], $courseid,
            $arguments["name"], $arguments["style"]);
        $result = ["saved" => true, "materialtheme" => $saved];
        if ($arguments["scope"] === "course") {
            $result["courseid"] = $courseid;
            $result["authoring"] = authoring_guidance::course_context($courseid);
        }
        return $result;
    }
}
