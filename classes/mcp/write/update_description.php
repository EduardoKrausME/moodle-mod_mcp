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

use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use mod_mcp\mcp\description;

/**
 * Update only native description fields, leaving revisions and academic settings untouched.
 */
final class update_description extends activity_tool {
    /** @return string Tool name. */
    public function get_name(): string {
        return "mcp_update_description";
    }

    /** @return string Tool title. */
    public function get_title(): string {
        return "Update native activity description";
    }

    /** @return string Tool description. */
    public function get_description(): string {
        return "Update the plain-text native activity description and optional course-page display. Read description.sha256 first.";
    }

    /** @return array Schema. */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "intro" => ["type" => "string", "maxLength" => 4000],
            "showdescription" => ["type" => "boolean"],
            "expected_description_sha256" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
        ], ["cmid", "intro", "expected_description_sha256"]);
    }

    /** @return bool Supports preview. */
    public function supports_dry_run(): bool {
        return true;
    }

    /** Preview only, checking native and plugin permissions. */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $ctx = $this->resolve_context($arguments);
        require_capability("mod/mcp:manage", $ctx, $identity->userid);
        require_capability("moodle/course:manageactivities", $ctx, $identity->userid);
        $cm = get_coursemodule_from_id("mcp", (int)$arguments["cmid"], 0, false, MUST_EXIST);
        $before = description::snapshot($cm, $this->activity($arguments));
        if (!hash_equals($before["sha256"], $arguments["expected_description_sha256"])) {
            throw new api_exception("description_changed", 409, "", [], get_string("descriptionchanged", "mcp"));
        }
        return ["cmid" => (int)$cm->id, "before" => $before, "after" => [
            "intro" => trim($arguments["intro"]), "introformat" => FORMAT_PLAIN,
            "showdescription" => $arguments["showdescription"] ?? $before["showdescription"],
        ]];
    }

    /** Apply the narrow, confirmed change with cache invalidation. */
    public function execute(array $arguments, authenticated_identity $identity): array {
        global $DB;
        $factory = \core\lock\lock_config::get_lock_factory("mod_mcp");
        $lock = $factory->get_lock("description_" . (int)$arguments["cmid"], 5);
        if (!$lock) {
            throw new api_exception("activity_busy", 409);
        }
        try {
            $transaction = $DB->start_delegated_transaction();
            try {
                $plan = $this->preview($arguments, $identity);
                $cm = get_coursemodule_from_id("mcp", $plan["cmid"], 0, false, MUST_EXIST);
                $after = $plan["after"];
                $DB->update_record("mcp", (object)["id" => $cm->instance, "intro" => $after["intro"],
                    "introformat" => $after["introformat"], "timemodified" => time()]);
                $DB->set_field("course_modules", "showdescription", (int)$after["showdescription"], ["id" => $cm->id]);
                rebuild_course_cache($cm->course, true);
                \core\event\course_module_updated::create_from_cm($cm, \context_module::instance($cm->id))->trigger();
                $transaction->allow_commit();
            } catch (\Throwable $e) {
                $transaction->rollback($e);
            }
            $cm = get_coursemodule_from_id("mcp", $plan["cmid"], 0, false, MUST_EXIST);
            return ["cmid" => $plan["cmid"], "description" => description::snapshot($cm, $this->activity($arguments))];
        } finally {
            $lock->release();
        }
    }
}
