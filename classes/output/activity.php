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
 * Mustache data for MCP activity page.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\output;

use moodle_url;

/**
 * Prepare the page data without a custom renderer.
 */
final class activity {
    /**
 * Build the introductory prompt for an empty MCP activity.
 */
    public static function prompt(\stdClass $mcp, \stdClass $cm): string {
        $args = (object)[
            "courseid" => (int)$mcp->course,
            "cmid" => (int)$cm->id,
            "name" => $mcp->name,
            "url" => (new moodle_url("/mod/mcp/view.php", ["id" => $cm->id]))->out(false),
        ];
        return get_string("prompt", "mcp", $args);
    }

    /**
 * Build view data. HTML content always remains in an isolated sandboxed iframe.
 */
    public static function page(\stdClass $mcp, \stdClass $cm, bool $canmanage, bool $preview): array {
        $revisionid = $preview && $canmanage ? (int)$mcp->draftrevision : (int)$mcp->publishedrevision;
        $revision = \mod_mcp\content\service::get((int)$mcp->id, $revisionid);
        return [
            "name" => format_string($mcp->name),
            "hascontent" => $revision !== null,
            "content" => $revision ? $revision->content : "",
            "canmanage" => $canmanage,
            "prompt" => self::prompt($mcp, $cm),
            "manageurl" => (new moodle_url("/mod/mcp/manage.php", ["id" => $cm->id]))->out(false),
            "previewurl" => (new moodle_url("/mod/mcp/view.php",
                ["id" => $cm->id, "preview" => 1]))->out(false),
            "ispreview" => $preview,
        ];
    }
}
