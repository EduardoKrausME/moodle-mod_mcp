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
 * Material themes stored in the existing Moodle plugin config.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\authoring;

use moodle_exception;

/**
 * One site default and optional course overrides, without a new database table.
 * These are visual styles for materials, not installed Moodle interface themes.
 */
final class material_theme {
    /** @var string Plugin configuration key for the default visual material theme. */
    private const DEFAULT_KEY = "materialtheme_default";

    /** @var string Plugin configuration key prefix for course visual styles. */
    private const COURSE_PREFIX = "materialtheme_course_";

    /**
     * Read a saved JSON definition, failing closed on malformed settings.
     */
    private static function read(string $key): ?array {
        $raw = get_config("mod_mcp", $key);
        if (!is_string($raw) || $raw === "") {
            return null;
        }
        $value = json_decode($raw, true);
        if (!is_array($value) || !is_string($value["name"] ?? null) ||
                !is_string($value["style"] ?? null) ||
                trim($value["name"]) === "" || trim($value["style"]) === "") {
            throw new moodle_exception("materialthemeinvalid", "mcp");
        }
        return ["name" => $value["name"], "style" => $value["style"]];
    }

    /**
     * Read the Moodle-wide default material theme.
     *
     * @return ?array Site style or null if unconfigured.
     */
    public static function site_default(): ?array {
        return self::read(self::DEFAULT_KEY);
    }

    /**
     * Read a course-specific material theme.
     *
     * @param int $courseid Course identifier.
     *
     * @return ?array Course style or null if unconfigured.
     */
    public static function course_override(int $courseid): ?array {
        if ($courseid <= 0) {
            throw new moodle_exception("invalidcourseid");
        }
        return self::read(self::COURSE_PREFIX . $courseid);
    }

    /**
     * Prefer the course-specific style over the Moodle-wide default.
     *
     * @param int $courseid Course identifier.
     *
     * @return ?array Effective style and scope or null.
     */
    public static function effective(int $courseid): ?array {
        $course = self::course_override($courseid);
        if ($course !== null) {
            return ["scope" => "course"] + $course;
        }
        $site = self::site_default();
        return $site === null ? null : ["scope" => "site"] + $site;
    }

    /**
     * Block authoring when no visual style is configured.
     *
     * @param int $courseid Course identifier.
     *
     * @return array Effective material theme.
     */
    public static function require_for_course(int $courseid): array {
        $theme = self::effective($courseid);
        if ($theme === null) {
            throw new moodle_exception("materialthemerequired", "mcp");
        }
        return $theme;
    }

    /**
     * Normalize a reusable visual style before storing it.
     *
     * @param string $name Material theme name.
     * @param string $style Material style instructions.
     *
     * @return array Validated material theme.
     */
    public static function definition(string $name, string $style): array {
        $name = trim(clean_param($name, PARAM_TEXT));
        $style = trim(clean_param($style, PARAM_TEXT));
        if ($name === "" || $style === "" || strlen($name) > 160 || strlen($style) > 3000) {
            throw new moodle_exception("invaliddata");
        }
        return ["name" => $name, "style" => $style];
    }

    /**
     * Call only after validating course/site capabilities.
     */
    public static function save(string $scope, int $courseid, string $name, string $style): array {
        if (!in_array($scope, ["site", "course"], true) ||
                ($scope === "course" && $courseid <= 0)) {
            throw new moodle_exception("invalidparameter");
        }
        $definition = self::definition($name, $style);
        $key = $scope === "site" ? self::DEFAULT_KEY : self::COURSE_PREFIX . $courseid;
        set_config($key, json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), "mod_mcp");
        return ["scope" => $scope] + $definition;
    }
}
