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
 * Native activity description metadata.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp;

use local_mcp\exception\api_exception;

/**
 * Native description and initial completion metadata, independent of authored revisions.
 */
final class description {
    /**
     * Normalize optional initial native settings.
     */
    public static function creation(array $arguments, \stdClass $course): array {
        global $CFG;
        $mode = $arguments["completion"] ?? null;
        $view = !empty($arguments["requireview"]);
        if (($mode !== null && !in_array($mode, [0, 1, 2], true)) ||
                ($view !== ($mode === 2)) ||
                ($mode !== null && $mode !== 0 && (empty($CFG->enablecompletion) || empty($course->enablecompletion)))) {
            throw new api_exception("invalid_initial_completion", 400, "", [],
                get_string("invalidinitialcompletion", "mcp"));
        }
        return ["intro" => trim((string)($arguments["intro"] ?? "")), "introformat" => FORMAT_PLAIN,
            "showdescription" => !empty($arguments["showdescription"]), "completion" => $mode, "requireview" => $view];
    }

    /**
     * Return the exact native description and concurrency token.
     */
    public static function snapshot(\stdClass $cm, \stdClass $mcp): array {
        $data = ["intro" => (string)$mcp->intro, "introformat" => (int)$mcp->introformat,
            "showdescription" => (bool)$cm->showdescription];
        $data["sha256"] = hash("sha256", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $data;
    }
}
