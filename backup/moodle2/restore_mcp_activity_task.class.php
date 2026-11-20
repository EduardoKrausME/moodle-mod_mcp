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
 * Restore MCP module activities.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/mod/mcp/backup/moodle2/restore_mcp_stepslib.php");

/**
 * Restore the module and its isolated revision history.
 */
class restore_mcp_activity_task extends restore_activity_task {
    /**
     * No activity-specific settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Use the standard Moodle activity restore sequence.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_mcp_activity_structure_step("mcp_structure", "mcp.xml"));
    }

    /**
     * No custom link rewriting is required.
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * No custom URL rules.
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Legacy logs are not imported.
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Legacy course logs are not imported.
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
