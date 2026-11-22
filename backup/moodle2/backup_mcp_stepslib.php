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
 * Backup structure of MCP HTML and question slots.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Backup the authored document and question bank links without duplicating answers.
 */
class backup_mcp_activity_structure_step extends backup_activity_structure_step {
    /**
     * Describe the backup XML tree and its database sources.
     */
    protected function define_structure() {
        $mcp = new backup_nested_element("mcp", ["id"],
            ["name", "intro", "introformat", "grade",
                "activitytype", "informationjson", "draftrevision",
                "publishedrevision", "completioninteractive", "timecreated", "timemodified"]);
        $revisions = new backup_nested_element("revisions");
        $revision = new backup_nested_element("revision", ["id"],
            ["content", "sha256", "createdby", "timecreated"]);
        $revision->annotate_ids("user", "createdby");
        // Assets use itemid 0 in Moodle File API, not an element named "0" in the backup XML.
        $mcp->annotate_files("mod_mcp", "assets", null);
        return $this->prepare_activity_structure($mcp);
    }
}
