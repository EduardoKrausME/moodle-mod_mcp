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

use context_module;
use coding_exception;
use mod_mcp\content\service;
use mod_mcp\authoring\material_theme;
use moodle_url;
use stdClass;

/**
 * Prepare the page data without a custom renderer.
 */
final class activity {
    /**
     * Supported ChatGPT/Claude authoring modes. These modes guide content generation;
     * all modes share the same HTML revision, sandbox and publication lifecycle.
     *
     * @return array<string, array{label: string, prompt: string, description: string}>
     */
    public static function prompt_types(): array {
        return [
            "page" => ["label" => "promptselectpage", "prompt" => "promptpage",
                "description" => "promptdescriptionpage"],
            "interactive" => ["label" => "promptselectinteractive", "prompt" => "promptquiz",
                "description" => "promptdescriptioninteractive"],
            "survey" => ["label" => "promptselectsurvey", "prompt" => "promptsurvey",
                "description" => "promptdescriptionsurvey"],
            "timeline" => ["label" => "promptselecttimeline", "prompt" => "prompttimeline",
                "description" => "promptdescriptiontimeline"],
            "case" => ["label" => "promptselectcase", "prompt" => "promptcase",
                "description" => "promptdescriptioncase"],
            "simulation" => ["label" => "promptselectsimulation", "prompt" => "promptsimulation",
                "description" => "promptdescriptionsimulation"],
            "custom" => ["label" => "promptselectcustom", "prompt" => "promptcustom",
                "description" => "promptdescriptioncustom"],
        ];
    }

    /**
     * Build the localized selector displayed on empty and management pages.
     */
    public static function prompt_options(stdClass $mcp, stdClass $cm): array {
        global $OUTPUT;

        $options = [];
        foreach (self::prompt_types() as $type => $configuration) {
            $label = get_string($configuration["label"], "mcp");
            $options[] = [
                "type" => $type,
                "label" => $label,
                "description" => get_string($configuration["description"], "mcp"),
                "imageurl" => $OUTPUT->image_url("material_types/{$type}", "mod_mcp"),
                "imagealt" => get_string("promptimagealt", "mcp", $label),
                "prompt" => self::prompt($mcp, $cm, $type),
            ];
        }
        return $options;
    }

    /**
     * Build the introductory prompt for an empty MCP activity.
     */
    public static function prompt(stdClass $mcp, stdClass $cm, string $type = "interactive"): string {
        global $CFG, $PAGE;

        $types = self::prompt_types();
        if (!isset($types[$type])) {
            throw new coding_exception("Unsupported mod_mcp authoring prompt type: " . $type);
        }

        $args = (object)[
            "wwwroot" => $CFG->wwwroot,
            "courseid" => (int)$mcp->course,
            "cmid" => (int)$cm->id,
            "name" => $mcp->name,
            "url" => (new moodle_url("/mod/mcp/view.php", ["id" => $cm->id]))->out(false),
            "blum" => get_string("promptblum", "mcp"),
        ];
        $prompt = get_string($types[$type]["prompt"], "mcp", $args);
        if (!in_array($type, ["interactive", "page"], true)) {
            $prompt .= " " . get_string("promptpublishinstructions", "mcp");
        }
        $apireference = get_string("promptapireference", "mcp",
            "https://github.com/EduardoKrausME/moodle-mod_mcp/blob/master/.chatgpt/API.md");
        $visual = material_theme::effective((int)$mcp->course);
        $materialinstruction = $visual === null ?
            "" :
            "Reusable material visual design: " . $visual["name"] . ". Follow its visual "
            . "style for every new material: " . $visual["style"] . ". ";
        return get_string("promptinstructions", "mcp") . " " .
            $apireference . " " . $prompt . " " . self::css_instructions($PAGE->theme->name)
            . " " . $materialinstruction;
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
            $cssvariables = ["--bs-primary", "--bs-accent", "--bs-primary-rgb", "--bs-accent-rgb"];
        } else if (in_array($themename, ["degrade", "eadflix", "eadtraining", "boost_magnific"], true)) {
            $cssvariables = ["--bs-primary", "--bs-secondary", "--bs-primary-rgb", "--bs-secondary-rgb"];
        }

        $instructions = [];
        if ($cssvariables) {
            $args = (object)[
                "theme" => $themename,
                "variables" => implode(", ", $cssvariables),
                "primary" => $cssvariables[0],
                "complementary" => $cssvariables[1],
                "primaryrgb" => $cssvariables[2],
                "complementaryrgb" => $cssvariables[3],
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
     * Load the trusted SDK by URL before authored scripts without changing the stored revision.
     *
     * @param string $content Stored activity HTML.
     * @return string HTML with a trusted SDK script reference.
     */
    public static function inject_sdk(string $content): string {
        $sdkurl = new moodle_url("/mod/mcp/sdk.js");
        // A separate root rule receives the Moodle theme's computed CSS variables via the SDK bridge.
        // The sandbox cannot inherit variables from its parent document.
        $bootstrap = '<style id="mod-mcp-theme-vars">:root{}</style>';
        $bootstrap .= '<script src="' . $sdkurl->out(false) . '"></script>';
        $bootstrap .= "<style>:root{background:transparent!important;}html,body{background:transparent!important;}</style>";

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
        // The HTML is served only through the authorized, sandboxed pluginfile endpoint.
        $urlcontent = $revision ? moodle_url::make_pluginfile_url(
            context_module::instance((int)$cm->id)->id, "mod_mcp", "content",
            $revisionid, "/", "index.html"
        )->out(false) : "";
        $hasconnection = $canmanage && !$revision && self::has_active_connection((int)$USER->id);
        return [
            "name" => format_string($mcp->name),
            "hascontent" => $revision !== null,
            "url_content" => $urlcontent,
            "canmanage" => $canmanage,
            "prompt" => self::prompt($mcp, $cm),
            "promptpage" => self::prompt($mcp, $cm, "page"),
            "promptoptions" => self::prompt_options($mcp, $cm),
            "themeconfigurl" => $canmanage ? material_templates::manager_url((int)$mcp->course) : "",
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
