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
 * Mustache data for MCP activity page.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\output;

use mod_mcp\content\service;
use moodle_url;
use stdClass;

/**
 * Prepare the page data without a custom renderer.
 */
final class activity {
    /**
     * Build the introductory prompt for an empty MCP activity.
     */
    public static function prompt(stdClass $mcp, stdClass $cm, string $type = "interactive"): string {
        global $CFG, $PAGE;

        $args = (object)[
            "wwwroot" => $CFG->wwwroot,
            "courseid" => (int)$mcp->course,
            "cmid" => (int)$cm->id,
            "name" => $mcp->name,
            "url" => (new moodle_url("/mod/mcp/view.php", ["id" => $cm->id]))->out(false),
        ];
        $prompt = get_string($type === "page" ? "promptpage" : "promptquiz", "mcp", $args);
        return get_string("promptinstructions", "mcp") . " " .
            $prompt . " " . self::css_instructions($PAGE->theme->name);
    }

    /**
     * Generate CSS guidance for the active Moodle theme.
     *
     * @param string $themename Effective theme name, optionally prefixed with theme_.
     * @return string CSS variable and dark mode instructions.
     */
    public static function css_instructions(string $themename): string {
        $themename = strtolower($themename);
        if (strpos($themename, "theme_") === 0) {
            $themename = substr($themename, 6);
        }

        $cssvariables = [];
        if (strpos($themename, "learnlingo") !== false) {
            $cssvariables = ["--bs-primary", "--bs-accent"];
        } else if (in_array($themename, ["degrade", "eadflix", "eadtraining", "boost_magnific"], true)) {
            $cssvariables = ["--bs-primary", "--bs-secondary"];
        }

        $instructions = [];
        if ($cssvariables) {
            $args = (object)[
                "theme" => $themename,
                "variables" => implode(", ", $cssvariables),
            ];
            $instructions[] = get_string("promptcolours", "mcp", $args);
        }
        $instructions[] = get_string("promptdarkmode", "mcp");

        return implode(" ", $instructions);
    }

    /**
     * Whether this Moodle user has an active MCP OAuth connection.
     *
     * @param int $userid Moodle user ID.
     * @return bool True when a non-revoked connection and its client are enabled.
     */
    public static function has_active_connection(int $userid): bool {
        global $DB;

        if ($userid <= 0) {
            return false;
        }

        $sql = "SELECT 1
                  FROM {local_mcp_connection} c
                  JOIN {local_mcp_oauth_client} client ON client.id = c.clientid
                 WHERE c.userid = :userid
                   AND c.enabled = 1
                   AND c.revokedat IS NULL
                   AND client.enabled = 1";
        return $DB->record_exists_sql($sql, ["userid" => $userid]);
    }

    /**
     * Inject the trusted SDK into iframe HTML without changing the stored revision.
     *
     * An inline bootstrap does not depend on a separate network request from
     * the sandboxed srcdoc document.
     *
     * @param string $content Stored activity HTML.
     * @return string HTML with the SDK injected before authored scripts.
     */
    public static function inject_sdk(string $content): string {
        $sdk = file_get_contents(__DIR__ . "/../../sdk.js");
        if ($sdk === false) {
            throw new \coding_exception("Cannot read the mod_mcp sandbox SDK");
        }
        $bootstrap = "<script>\n" . $sdk . "\n</script>";

        // Preserve the doctype even if the document has no head element.
        foreach (["/<head\b[^>]*>/i", "/<html\b[^>]*>/i"] as $pattern) {
            if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1] + strlen($matches[0][0]);
                return substr_replace($content, $bootstrap, $position, 0);
            }
        }
        return $bootstrap . $content;
    }

    /**
     * Build view data. HTML content always remains in an isolated sandboxed iframe.
     */
    public static function page(stdClass $mcp, stdClass $cm, bool $canmanage, bool $preview): array {
        global $USER;
        $revisionid = $preview && $canmanage ? (int)$mcp->draftrevision : (int)$mcp->publishedrevision;
        $revision = service::get((int)$mcp->id, $revisionid);
        // Embed the trusted SDK at render time, before authored scripts.
        // An external script can be unavailable inside an isolated srcdoc iframe.
        $content = $revision ? self::inject_sdk($revision->content) : "";
        $hasconnection = $canmanage && !$revision && self::has_active_connection((int)$USER->id);
        return [
            "name" => format_string($mcp->name),
            "hascontent" => $revision !== null,
            "content" => $content,
            "canmanage" => $canmanage,
            "prompt" => self::prompt($mcp, $cm),
            "promptpage" => self::prompt($mcp, $cm, "page"),
            "cmid" => (int)$cm->id,
            "hasconnection" => $hasconnection,
            "connectionurl" => (new moodle_url("/local/mcp/new_connection.php"))->out(false),
            "manageurl" => (new moodle_url("/mod/mcp/manage.php", ["id" => $cm->id]))->out(false),
            "previewurl" => (new moodle_url("/mod/mcp/view.php",
                ["id" => $cm->id, "preview" => 1]))->out(false),
            "ispreview" => $preview,
        ];
    }
}
