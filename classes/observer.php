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
 * Hide newly created MCP modules before first publication.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use core\event\course_module_created;

/**
 * Enforces a hidden-first workflow even when course editing creates a visible module.
 */
final class observer {
    /**
     * Hide an empty MCP module after core has completed module creation.
     */
    public static function created(course_module_created $event): void {
        global $CFG, $DB;
        $cmid = (int)$event->contextinstanceid;
        $cm = get_coursemodule_from_id("mcp", $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "id, publishedrevision");
        if (!$mcp || (int)$mcp->publishedrevision !== 0) {
            return;
        }
        require_once($CFG->dirroot . "/course/lib.php");
        set_coursemodule_visible($cmid, 0);
        rebuild_course_cache($cm->course, true);
    }
}
