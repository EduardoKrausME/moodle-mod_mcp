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
 * Translate expected authoring errors at the MCP boundary only.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp;

use local_mcp\exception\api_exception;
use moodle_exception;

/**
 * Keep native Moodle authoring exceptions intact outside the MCP integration.
 */
final class authoring_error {
    /**
     * Return an actionable public error for known authoring conditions.
     *
     * @param moodle_exception $failure Native failure.
     * @param int $courseid Authorized course context, if available.
     * @return moodle_exception
     */
    public static function translate(moodle_exception $failure, int $courseid = 0): moodle_exception {
        if ($failure instanceof api_exception) {
            return $failure;
        }
        $details = $courseid > 0 ? ["courseid" => $courseid] : [];
        if ($failure->module === "mcp" || $failure->module === "mod_mcp") {
            switch ($failure->errorcode) {
                case "empty_data":
                    return new api_exception("empty_data", 422, "", $details,
                        "The HTML content cannot be empty.");
                case "materialthemeinvalid":
                    return new api_exception("material_theme_invalid", 409, "", $details + [
                        "required_action" => "mcp_configure_material_theme",
                    ], "The stored material theme is invalid. Review and replace the affected theme configuration.");
                case "revisionconflict":
                    return new api_exception("revision_conflict", 409, "", $details + [
                        "required_action" => "mcp_get_activity",
                    ], "The activity has changed. Read its current revision and request a new confirmation.");
                case "questionnotconfigured":
                    return new api_exception("questions_not_configured", 409, "", $details,
                        "Configure the quiz question pool before changing question selection.");
                case "invalidquestioncount":
                case "invalidquestionconfiguration":
                    return new api_exception("invalid_question_configuration", 422, "", $details,
                        "Review the quiz question pool and selection settings.");
            }
        }
        if (in_array($failure->module, ["error", "moodle"], true) && $failure->errorcode === "invaliddata") {
            return new api_exception("invalid_authoring_data", 422, "", $details,
                "Review the supplied activity content or configuration.");
        }
        return $failure;
    }
}
