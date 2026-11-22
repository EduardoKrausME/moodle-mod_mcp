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
 * Tests for the mod_mcp authoring prompt catalogue.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use coding_exception;
use mod_mcp\output\activity;

/**
 * Verify authoring prompt keys, localization and type validation.
 *
 * @covers \mod_mcp\output\activity
 */
final class activity_prompt_test extends \advanced_testcase {
    /**
     * All five new choices coexist with the existing page and quiz.
     */
    public function test_all_seven_authoring_types_are_registered(): void {
        $this->assertSame([
            "interactive", "page", "survey", "timeline", "case", "simulation", "custom",
        ], array_keys(activity::prompt_types()));
    }

    /**
     * Every registered option must have a label and a specialized prompt.
     */
    public function test_authoring_types_have_localized_strings(): void {
        $args = (object)[
            "wwwroot" => "https://example.org",
            "courseid" => 4,
            "cmid" => 9,
            "name" => "Example",
            "url" => "https://example.org/mod/mcp/view.php?id=9",
            "blum" => get_string("promptblum", "mcp"),
        ];
        foreach (activity::prompt_types() as $configuration) {
            $label = get_string($configuration["label"], "mcp");
            $prompt = get_string($configuration["prompt"], "mcp", $args);
            $this->assertNotEmpty($label);
            $this->assertNotEmpty($prompt);
            $this->assertStringNotContainsString("[[", $label);
            $this->assertStringNotContainsString("[[", $prompt);
            $this->assertSame(1, substr_count($prompt, $args->blum));
            $this->assertStringNotContainsString('{$a->blum}', $prompt);
        }
        $this->assertNotEmpty(get_string("promptpublishinstructions", "mcp"));
        foreach (["copyprompt", "copypromptsucceeded", "copypromptfailed"] as $key) {
            $this->assertNotEmpty(get_string($key, "mcp"));
        }
    }

    /**
     * The authoring selector must expose a copy action and an accessible status.
     */
    public function test_prompt_selector_has_copy_controls(): void {
        $template = file_get_contents(__DIR__ . "/../templates/prompt_selector.mustache");
        $this->assertNotFalse($template);
        $this->assertStringContainsString("data-copy-prompt", $template);
        $this->assertStringContainsString('aria-live="polite"', $template);
        $this->assertStringContainsString("navigator.clipboard.writeText", $template);
        $this->assertStringContainsString("document.execCommand('copy')", $template);
    }

    /**
     * An unknown type must not silently turn into a quiz.
     */
    public function test_unknown_prompt_type_is_rejected(): void {
        $this->expectException(coding_exception::class);
        activity::prompt(
            (object)["course" => 4, "name" => "Example"],
            (object)["id" => 9],
            "unsupported"
        );
    }
}
