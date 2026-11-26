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
use mod_mcp\output\material_templates;
use mod_mcp\authoring\material_theme;

/**
 * Verify authoring prompt keys, localization and type validation.
 *
 * @covers \mod_mcp\output\activity
 */
final class activity_prompt_test extends \advanced_testcase {
    /**
     * The manager displays both scopes and permits system editing only when authorized.
     */
    public function test_material_template_manager(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $courseid = (int)$course->id;
        material_theme::save("site", 0, "System", "Use cards.");
        material_theme::save("course", $courseid, "Course", "Use panels.");
        $data = material_templates::page($courseid, false);
        $this->assertSame(["System", "Course"], array_column($data["templates"], "name"));
        $this->assertFalse($data["templates"][0]["canedit"]);
        $this->assertSame("", $data["templates"][0]["editurl"]);
        $this->assertSame(["course"], array_column($data["prompts"], "scope"));
        $this->assertTrue($data["prompts"][0]["replace"]);
        $this->assertStringContainsString("mcp_configure_material_theme", $data["prompts"][0]["prompt"]);
        $this->assertStringContainsString("scope=course", $data["prompts"][0]["prompt"]);
        $admin = material_templates::page($courseid, true);
        $this->assertSame(["site", "course"], array_column($admin["prompts"], "scope"));
        $this->assertStringContainsString("scope=site", $admin["prompts"][0]["prompt"]);
        $this->setAdminUser();
        $this->assertStringContainsString("/mod/mcp/templates.php", material_templates::manager_url($courseid));
        $this->setUser($this->getDataGenerator()->create_user());
        $this->assertSame("", material_templates::manager_url($courseid));
    }

    /**
     * All five new choices coexist with the existing page and quiz.
     */
    public function test_all_seven_authoring_types_are_registered(): void {
        $this->assertSame([
            "page", "interactive", "survey", "timeline", "case", "simulation", "custom",
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
        foreach (["copyprompt", "copypromptsucceeded", "copypromptfailed"] as $key) {
            $this->assertNotEmpty(get_string($key, "mcp"));
        }
    }

    /**
     * Each authoring mode has localized guidance and its matching bundled illustration.
     */
    public function test_authoring_modes_have_descriptions_and_images(): void {
        foreach (activity::prompt_types() as $type => $configuration) {
            $this->assertArrayHasKey("description", $configuration);
            $description = get_string($configuration["description"], "mcp");
            $this->assertNotEmpty($description);
            $this->assertStringNotContainsString("[[", $description);
            $this->assertGreaterThan(120, \core_text::strlen($description));
            $this->assertMatchesRegularExpression('/[?؟？]/u', $description);
            $this->assertFileExists(__DIR__ . "/../pix/material_types/{$type}.png");
        }

        $this->assertNotEmpty(get_string("promptimagealt", "mcp", "Example"));
        $freedom = get_string("promptfreedom", "mcp");
        $this->assertStringContainsString("<strong>", $freedom);
        $this->assertStringContainsString("</strong>", $freedom);
        $this->assertGreaterThan(100, \core_text::strlen($freedom));
        $template = file_get_contents(__DIR__ . "/../templates/prompt_selector.mustache");
        $this->assertNotFalse($template);
        foreach (["{{description}}", "{{imageurl}}", "{{imagealt}}",
                  'class="mod-mcp-prompt-intro"', "{{#str}}promptfreedom, mcp{{/str}}",
                  'id="mcp-prompt-{{type}}-hint-{{cmid}}"',
                  'aria-describedby="mcp-prompt-{{type}}-desc-{{cmid}} ' .
                      'mcp-prompt-{{type}}-freedom-{{cmid}} mcp-prompt-{{type}}-hint-{{cmid}}"',
                 ] as $expected) {
            $this->assertStringContainsString($expected, $template);
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
     * Prompt choices use accessible tabs and do not show the redundant helper text.
     */
    public function test_prompt_selector_uses_accessible_tabs(): void {
        $template = file_get_contents(__DIR__ . "/../templates/prompt_selector.mustache");
        $this->assertNotFalse($template);
        foreach (['class="nav nav-tabs', 'role="tablist"', 'role="tab"', 'role="tabpanel"',
                  'aria-selected="false"', 'aria-controls=', 'aria-labelledby=',
                  "ArrowRight", "ArrowLeft", "Home", "End"] as $expected) {
            $this->assertStringContainsString($expected, $template);
        }
        $this->assertStringContainsString("activateTab(choices[0], false)", $template);
        $this->assertStringNotContainsString("promptselecthelp", $template);
        $this->assertStringNotContainsString("aria-pressed", $template);
    }

    /**
     * Visual tab and content panel wrappers remain present with scoped styles.
     */
    public function test_prompt_selector_uses_connected_tab_styling(): void {
        $template = file_get_contents(__DIR__ . "/../templates/prompt_selector.mustache");
        $styles = file_get_contents(__DIR__ . "/../styles.css");
        $this->assertNotFalse($template);
        $this->assertNotFalse($styles);
        $this->assertStringContainsString('class="mod-mcp-prompt-tabs"', $template);
        $this->assertStringContainsString('class="mod-mcp-prompt-content"', $template);
        $this->assertStringContainsString(".mod-mcp-prompt-tabs .nav-link.active", $styles);
        $this->assertStringContainsString("border-bottom-color: var(--bs-body-bg", $styles);
        $this->assertStringContainsString("overflow-x: auto", $styles);
        $this->assertStringContainsString("--bs-primary", $styles);
        $this->assertStringContainsString(":focus-visible", $styles);
    }

    /**
     * All translation overrides must preserve the colour placeholders defined in English.
     */
    public function test_theme_colour_placeholders_are_consistent_across_locales(): void {
        $englishfile = file_get_contents(__DIR__ . "/../lang/en/mcp.php");
        $this->assertNotFalse($englishfile);
        $pattern = '/^\$string\[\x27promptcolours\x27\] = .*;$/m';
        $this->assertSame(1, preg_match($pattern, $englishfile, $englishmatches));
        $original = $englishmatches[0];
        $placeholders = [
            '{$a->theme}',
            '{$a->variables}',
            '{$a->primary}',
            '{$a->complementary}',
        ];
        $files = glob(__DIR__ . "/../lang/*/mcp.php");
        $this->assertNotFalse($files);
        $checked = 0;
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $this->assertNotFalse($content);
            if (preg_match($pattern, $content, $matches) !== 1) {
                // Regional languages may inherit the string from their parent language.
                continue;
            }
            foreach ($placeholders as $placeholder) {
                $this->assertSame(
                    substr_count($original, $placeholder),
                    substr_count($matches[0], $placeholder),
                    basename(dirname($file)) . ": " . $placeholder
                );
            }
            $checked++;
        }
        $this->assertGreaterThanOrEqual(12, $checked);
    }

    /**
     * Main language packs translate all authoring choices rather than showing English fallbacks.
     */
    public function test_main_locales_translate_authoring_descriptions(): void {
        $requiredkeys = [
            "promptcolours",
            "promptfreedom",
            "promptimagealt",
            "promptselectcase",
            "promptselectcustom",
            "promptselectsimulation",
            "promptselectsurvey",
            "promptselecttimeline",
            "promptdescriptioncase",
            "promptdescriptioncustom",
            "promptdescriptioninteractive",
            "promptdescriptionpage",
            "promptdescriptionsimulation",
            "promptdescriptionsurvey",
            "promptdescriptiontimeline",
        ];
        foreach (["ar", "de", "es", "es_mx", "fr", "it", "nl", "ru", "zh_cn"] as $locale) {
            $content = file_get_contents(__DIR__ . "/../lang/{$locale}/mcp.php");
            $this->assertNotFalse($content);
            foreach ($requiredkeys as $key) {
                $this->assertStringContainsString(
                    "\$string['{$key}'] = ",
                    $content,
                    "{$locale}: {$key}"
                );
            }
        }
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
