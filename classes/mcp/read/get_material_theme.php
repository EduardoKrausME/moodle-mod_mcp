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
 * Resolve a course material theme before the first activity is created.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\mcp\read;

use context;
use context_course;
use local_mcp\read\tool\base_tool;
use local_mcp\security\authenticated_identity;
use mod_mcp\authoring\material_theme;
use mod_mcp\mcp\authoring_guidance;

final class get_material_theme extends base_tool {
    public function get_name(): string {
        return "mcp_get_material_theme";
    }

    public function get_title(): string {
        return "MCP activity: read material theme";
    }

    public function get_description(): string {
        return "Check whether a visual material theme is registered for a Moodle course "
            . "before creating any activity. Course overrides take precedence over "
            . "the site default. If configured=false, use mcp_configure_material_theme "
            . "first and do not generate HTML, CSS, JavaScript or questions.";
    }

    public function get_required_capability(): string {
        return "moodle/course:manageactivities";
    }

    public function get_input_schema(): array {
        return $this->object_schema(["courseid" => ["type" => "integer", "minimum" => 1]], ["courseid"]);
    }

    public function resolve_context(array $arguments): context {
        return context_course::instance((int)$arguments["courseid"], MUST_EXIST);
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        $courseid = (int)$arguments["courseid"];
        require_capability("moodle/course:manageactivities",
            $this->resolve_context($arguments), $identity->userid);
        return [
            "courseid" => $courseid,
            "site_default" => material_theme::site_default(),
            "course_override" => material_theme::course_override($courseid),
            "authoring" => authoring_guidance::course_context($courseid),
        ];
    }
}
