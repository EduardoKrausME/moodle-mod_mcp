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
 * Create a complete MCP activity in a Moodle course.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use mod_mcp\mcp\authoring_error;
use mod_mcp\content\service;
use mod_mcp\content\metadata;
use moodle_exception;
use context;
use context_course;
use local_mcp\course\activity_position_service;
use local_mcp\exception\api_exception;
use mod_mcp\mcp\authoring_guidance;
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
        return authoring_guidance::tool_description(
            "Create a complete mod_mcp activity with its final HTML and return the real cmid. "
            . "Optionally specify aftercmid or beforecmid, native sanitized HTML intro and showdescription (enabled by default), "
            . "and initial completion (off, manual or automatic view). Include private quiz information in this same call.",
            ["core", "interview", "learning", "html", "sdk", "quiz", "approval", "theme", "material"]);
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
            "aftercmid" => ["type" => "integer", "minimum" => 1],
            "beforecmid" => ["type" => "integer", "minimum" => 1],
            "name" => ["type" => "string", "minLength" => 1, "maxLength" => 255],
            "intro" => ["type" => "string", "maxLength" => 4000],
            "showdescription" => ["type" => "boolean", "default" => true,
                "description" => "Display the native description on the course page."],
            "completion" => ["type" => "integer", "minimum" => 0, "maximum" => 2],
            "requireview" => ["type" => "boolean"],
            "html" => ["type" => "string", "minLength" => 1],
            "type" => ["type" => ["string", "null"]],
            "information" => ["type" => ["object", "null"]],
        ], ["courseid", "sectionnum", "name", "html"]);
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
     * Preview activity creation without writing to the course.
     *
     * @param array $arguments Input arguments.
     * @param authenticated_identity $identity Authenticated teacher.
     * @return array Safe preview.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        try {
            $ctx = $this->resolve_context($arguments);
            require_capability("mod/mcp:addinstance", $ctx, $identity->userid);
            $authoring = authoring_guidance::course_context((int)$arguments["courseid"]);
            require_capability("moodle/course:manageactivities", $ctx, $identity->userid);
            $position = $this->requested_position($arguments, $identity);
            $settings = \mod_mcp\mcp\description::creation($arguments, get_course((int)$arguments["courseid"]));
            $this->validate_content($arguments);
            return [
                "description" => $settings,
                "courseid" => (int)$arguments["courseid"],
                "sectionnum" => (int)$arguments["sectionnum"],
                "name" => clean_param($arguments["name"], PARAM_TEXT),
                "materialtheme" => $authoring["materialtheme"],
                "authoring" => $authoring,
                "visible_by_default" => true,
                "type" => $arguments["type"] ?? null,
                "questioncount" => count($arguments["information"]["questions"] ?? []),
                "position" => $position,
            ];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }

    /**
     * Validate the complete source and private assessment configuration before writing.
     *
     * @param array $arguments Tool arguments.
     */
    private function validate_content(array $arguments): void {
        $html = $arguments["html"] ?? "";
        service::validate_html($html);
        service::validate_theme_colours((int)$arguments["courseid"], $html);
        metadata::validate($arguments["type"] ?? null, $arguments["information"] ?? null);
    }

    /**
     * Resolve an optional anchor through the shared local plugin service.
     *
     * @param array $arguments Input arguments.
     * @param authenticated_identity $identity Connected user.
     * @return array|null Validated position or null for ordinary creation.
     */
    private function requested_position(array $arguments, authenticated_identity $identity): ?array {
        if (!isset($arguments["aftercmid"]) && !isset($arguments["beforecmid"])) {
            return null;
        }
        if (!class_exists(activity_position_service::class)) {
            throw new api_exception("activity_position_unavailable", 409, "", [],
                "Update local_mcp before creating activities at an explicit position.");
        }
        $position = activity_position_service::position((int)$arguments["courseid"], $arguments, $identity);
        unset($position["sequence"]);
        return $position;
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        try {
            global $CFG, $DB;
            require_once($CFG->dirroot . "/course/modlib.php");
            $ctx = $this->resolve_context($arguments);
            require_capability("mod/mcp:addinstance", $ctx, $identity->userid);
            require_capability("moodle/course:manageactivities", $ctx, $identity->userid);
            $position = $this->requested_position($arguments, $identity);
            $course = get_course((int)$arguments["courseid"]);
            [$module, $context, $section, $cm, $info] = prepare_new_moduleinfo_data(
                $course, "mcp", (int)$arguments["sectionnum"]);
            $info->name = clean_param($arguments["name"], PARAM_TEXT);
            $settings = \mod_mcp\mcp\description::creation($arguments, $course);
            $info->intro = $settings["intro"];
            $info->introformat = (int)FORMAT_HTML;
            // The prepare_new_moduleinfo_data function supplies an empty introeditor, which
            // add_moduleinfo otherwise uses to overwrite intro and introformat.
            unset($info->introeditor);
            $info->showdescription = (int)$settings["showdescription"];
            if (isset($arguments["completion"])) {
                $info->completion = $settings["completion"];
                $info->completionview = (int)$settings["requireview"];
                $info->completiongradeitemnumber = null;
                $info->completionusegrade = 0;
                $info->completionpassgrade = 0;
                $info->completioninteractive = 0;
            }
            $info->grade = 0;
            $this->validate_content($arguments);
            $authoring = authoring_guidance::course_context((int)$course->id);
            $info->visible = 1;
            $transaction = $DB->start_delegated_transaction();
            try {
                $created = add_moduleinfo($info, $course);
                $cmrecord = $DB->get_record("course_modules", ["id" => $created->coursemodule], "*", MUST_EXIST);
                metadata::save((int)$created->instance, $arguments["type"] ?? null, $arguments["information"] ?? null);
                $revision = service::save((int)$created->instance, $arguments["html"], $identity->userid, 0);
                $instance = $DB->get_record("mcp", ["id" => $created->instance], "*", MUST_EXIST);
                $saveddescription = \mod_mcp\mcp\description::snapshot($cmrecord, $instance);
                if ($saveddescription["intro"] !== $settings["intro"] ||
                        (int)$instance->introformat !== (int)FORMAT_HTML ||
                        $saveddescription["showdescription"] !== $settings["showdescription"] ||
                        ($settings["completion"] !== null &&
                            ((int)$cmrecord->completion !== $settings["completion"] ||
                                (bool)$cmrecord->completionview !== $settings["requireview"] ||
                                $cmrecord->completiongradeitemnumber !== null || !empty($instance->completioninteractive)))) {
                    throw new api_exception("activity_creation_settings_mismatch", 409);
                }
                if ($position !== null) {
                    $moveargs = ["cmid" => (int)$created->coursemodule,
                        "sectionnum" => (int)$arguments["sectionnum"]];
                    foreach (["aftercmid", "beforecmid"] as $key) {
                        if (isset($arguments[$key])) {
                            $moveargs[$key] = (int)$arguments[$key];
                        }
                    }
                    $position = activity_position_service::move($moveargs, $identity);
                }
                $transaction->allow_commit();
            } catch (\Throwable $e) {
                $transaction->rollback($e);
            }
            return ["cmid" => (int)$created->coursemodule, "instanceid" => (int)$created->instance,
                "courseid" => (int)$course->id, "position" => $position, "description" => $saveddescription,
                "revision" => $revision, "visible" => (bool)$DB->get_field("course_modules", "visible",
                    ["id" => $created->coursemodule], MUST_EXIST),
                "authoring" => $authoring,
                "url" => (new moodle_url("/mod/mcp/view.php",
                    ["id" => $created->coursemodule]))->out(false)];
        } catch (moodle_exception $failure) {
            throw authoring_error::translate($failure,
                (int)($arguments["courseid"] ?? $mcp->course ?? $activity->course ?? 0));
        }
    }
}
