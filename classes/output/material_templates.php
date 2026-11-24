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
 * View data and prompts for optional reusable material templates.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\output;

use context_course;
use mod_mcp\authoring\material_theme;
use moodle_exception;
use moodle_url;

/**
 * Present the current course and system templates without changing their storage.
 */
final class material_templates {
    /**
     * Return the manager link only for users allowed to manage the course.
     *
     * @param int $courseid Course ID.
     * @return string Authorized URL or empty string.
     */
    public static function manager_url(int $courseid): string {
        if (!has_capability("moodle/course:manageactivities", context_course::instance($courseid))) {
            return "";
        }
        return (new moodle_url("/mod/mcp/templates.php", ["courseid" => $courseid]))->out(false);
    }

    /**
     * Build a localized template creation prompt, independently of activity authoring.
     *
     * @param int $courseid Course ID.
     * @param string $scope Site or course.
     * @return string Copyable prompt.
     */
    public static function prompt(int $courseid, string $scope): string {
        global $CFG;
        if (!in_array($scope, ["site", "course"], true)) {
            throw new moodle_exception("invalidparameter");
        }
        return get_string("materialtemplatesprompt", "mcp", (object)[
            "wwwroot" => $CFG->wwwroot,
            "courseid" => $courseid,
            "scope" => $scope,
        ]);
    }

    /**
     * Build both template cards after the caller has checked course permissions.
     *
     * @param int $courseid Course ID.
     * @param bool $caneditsite Whether the current user can manage site configuration.
     * @return array Mustache context.
     */
    public static function page(int $courseid, bool $caneditsite): array {
        $templates = [];
        $prompts = [];
        foreach (["site", "course"] as $scope) {
            $invalid = false;
            try {
                $current = $scope === "site" ? material_theme::site_default() : material_theme::course_override($courseid);
            } catch (moodle_exception $failure) {
                if ($failure->errorcode !== "materialthemeinvalid" ||
                        !in_array($failure->module, ["mcp", "mod_mcp"], true)) {
                    throw $failure;
                }
                $current = null;
                $invalid = true;
            }
            $canedit = $scope === "course" || $caneditsite;
            $entry = [
                "scope" => $scope,
                "label" => get_string("materialtemplates" . $scope, "mcp"),
                "configured" => $current !== null,
                "invalid" => $invalid,
                "empty" => $current === null && !$invalid,
                "name" => $current["name"] ?? "",
                "style" => $current["style"] ?? "",
                "canedit" => $canedit,
                "editurl" => $canedit ? (new moodle_url("/mod/mcp/theme.php", [
                    "courseid" => $courseid, "scope" => $scope,
                ]))->out(false) : "",
                "replace" => $current !== null || $invalid,
                "prompt" => $canedit ? self::prompt($courseid, $scope) : "",
            ];
            $templates[] = $entry;
            if ($canedit) {
                $prompts[] = $entry;
            }
        }
        return ["courseid" => $courseid, "templates" => $templates, "prompts" => $prompts];
    }
}
