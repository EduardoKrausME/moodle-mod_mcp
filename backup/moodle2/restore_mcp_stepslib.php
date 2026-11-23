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
 * Restore MCP authored HTML and safely remap question references.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Never import another user's attempts or native question usages from a content-only backup.
 */
class restore_mcp_activity_structure_step extends restore_activity_structure_step {
    /**
     * @var int Old draft revision ID.
     */
    private int $olddraft = 0;
    /**
     * @var int Old published revision ID.
     */
    private int $oldpublished = 0;
    /**
     * @var int Newly created MCP instance ID.
     */
    private int $newmcpid = 0;

    /**
     * Map child data through Moodle's normal restore framework.
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element("mcp", "/activity/mcp"),
            new restore_path_element("revision", "/activity/mcp/revisions/revision"),
        ];
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Insert only the course-owned instance.
     */
    protected function process_mcp($data) {
        global $DB;
        $data = (object)$data;
        $this->olddraft = (int)$data->draftrevision;
        $this->oldpublished = (int)$data->publishedrevision;
        $data->course = $this->get_courseid();
        $data->draftrevision = 0;
        $data->publishedrevision = 0;
        $this->newmcpid = $DB->insert_record("mcp", $data);
        $this->apply_activity_instance($this->newmcpid);
    }

    /**
     * Restore immutable revision content and remap historical author IDs.
     */
    protected function process_revision($data) {
        global $DB;
        $data = (object)$data;
        $oldid = (int)$data->id;
        $data->mcpid = $this->newmcpid;
        $data->createdby = (int)$this->get_mappingid("user", $data->createdby, 0);
        $newid = $DB->insert_record("mcp_revisions", $data);
        $this->set_mapping("mcp_revision", $oldid, $newid);
    }

    /**
     * Connect the new revision pointers and restore Moodle-managed files.
     */
    protected function after_execute() {
        global $DB;
        $DB->update_record("mcp", (object)[
            "id" => $this->newmcpid,
            "draftrevision" => (int)$this->get_mappingid("mcp_revision", $this->olddraft, 0),
            "publishedrevision" => (int)$this->get_mappingid("mcp_revision", $this->oldpublished, 0),
        ]);
        // Moodle's activity restore creates the context mapping; use it for itemid 0 assets.
        $this->add_related_files("mod_mcp", "assets", null);
    }
}
