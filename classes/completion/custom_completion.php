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
 * Native Moodle custom completion rules for interactive content.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\completion;

use core_completion\activity_custom_completion;

/**
 * Completion is only available when enabled in the module settings.
 */
final class custom_completion extends activity_custom_completion {
    /**
     * Check the server-owned record for the current user.
     */
    public function get_state(string $rule): int {
        global $DB;
        $this->validate_rule($rule);
        return $DB->record_exists("mcp_state", [
            "mcpid" => (int)$this->cm->instance,
            "userid" => (int)$this->userid,
            "completed" => 1,
        ]) ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Supported rules.
     */
    public static function get_defined_custom_rules(): array {
        return ["completioninteractive"];
    }

    /**
     * Explain the rule to learners.
     */
    public function get_custom_rule_descriptions(): array {
        return ["completioninteractive" => get_string("completioninteractive", "mcp")];
    }

    /**
     * Place the custom rule after view completion.
     */
    public function get_sort_order(): array {
        return ["completionview", "completioninteractive"];
    }
}
