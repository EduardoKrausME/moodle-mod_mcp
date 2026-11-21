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
 * Create a hidden MCP activity in a Moodle course.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use context;
use context_course;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;
use moodle_url;

/**
 * Create real Moodle modules using core course APIs, not direct SQL.
 */
final class create_activity extends base_tool {
    /**
     * get name.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_create_activity";
    }

    /**
     * get title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: create";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Create an unpublished, hidden mod_mcp activity and return the real cmid.";
    }

    /**
     * get required capability.
     *
     * @return string Result.
     */
    public function get_required_capability(): string {
        return "moodle/course:manageactivities";
    }

    /**
     * get input schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "courseid" => ["type" => "integer", "minimum" => 1],
            "sectionnum" => ["type" => "integer", "minimum" => 0],
            "name" => ["type" => "string", "minLength" => 1, "maxLength" => 255],
        ], ["courseid", "sectionnum", "name"]);
    }

    /**
     * resolve context.
     *
     * @param mixed $arguments Input arguments.
     * @return context Result.
     */
    public function resolve_context(array $arguments): context {
        return context_course::instance((int)$arguments["courseid"], MUST_EXIST);
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        global $CFG;
        require_once($CFG->dirroot . "/course/modlib.php");
        $ctx = $this->resolve_context($arguments);
        require_capability("mod/mcp:addinstance", $ctx, $identity->userid);
        $course = get_course((int)$arguments["courseid"]);
        [$module, $context, $section, $cm, $info] = prepare_new_moduleinfo_data(
            $course, "mcp", (int)$arguments["sectionnum"]);
        $info->name = clean_param($arguments["name"], PARAM_TEXT);
        $info->intro = "";
        $info->introformat = FORMAT_HTML;
        $info->grade = 0;
        $info->visible = 0;
        $created = add_moduleinfo($info, $course);
        return ["cmid" => (int)$created->coursemodule, "instanceid" => (int)$created->instance,
            "courseid" => (int)$course->id,
            "url" => (new moodle_url("/mod/mcp/view.php",
                ["id" => $created->coursemodule]))->out(false)];
    }
}
