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
 * Shared MCP edit context and capability checks.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use context;
use context_module;
use local_mcp\write\tool\base_tool;
use moodle_exception;
use stdClass;

/**
 * Base for editor tools that operate on an existing module only.
 */
abstract class activity_tool extends base_tool {
    /**
     * Restrict edits to authorized activity managers.
     */
    public function get_required_capability(): string {
        return "mod/mcp:manage";
    }

    /**
     * Resolve the real module and reject arbitrary course-module IDs.
     */
    public function resolve_context(array $arguments): context {
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        return context_module::instance((int)$cm->id);
    }

    /**
     * Fetch the instance associated with a module ID, never trust a separate instance ID.
     */
    protected function activity(array $arguments): stdClass {
        global $DB;
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        return $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
    }
}
