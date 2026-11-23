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
 * MCP tool description and theme context tests.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\mcp\authoring_guidance;
use mod_mcp\mcp\read\get_activity;
use mod_mcp\mcp\write\create_activity;
use mod_mcp\mcp\write\publish;
use mod_mcp\mcp\write\save_content;
use mod_mcp\mcp\write\save_information;

/**
 * Required pedagogical and technical rules must be visible in tool discovery.
 *
 * @covers \mod_mcp\mcp\authoring_guidance
 */
final class mcp_authoring_guidance_test extends advanced_testcase {
    /**
     * The site default theme is included directly in every relevant description.
     */
    public function test_tool_descriptions_include_rules_and_site_theme(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->theme = "learnlingo";
        $tools = [
            new create_activity(), new get_activity(), new save_content(),
            new save_information(), new publish(),
        ];
        foreach ($tools as $tool) {
            $description = $tool->get_description();
            foreach ([
                "Bloom", "mcp_get_activity", "window.MoodleMCP", "mcp_save_information",
                "JSON", "server", "approval", "Theme: learnlingo", "--bs-primary",
                "--bs-accent", "--bs-primary-rgb", "--bs-accent-rgb", "rgba(",
                "gradient", "Do NOT invent", "SITE DEFAULT",
            ] as $required) {
                $this->assertStringContainsString($required, $description,
                    $tool->get_name() . ": " . $required);
            }
            $this->assertStringNotContainsString("Theme: boost.", $description);
        }

        $CFG->theme = "eadflix";
        $description = (new create_activity())->get_description();
        $this->assertStringContainsString("Theme: eadflix.", $description);
        $this->assertStringContainsString("--bs-secondary", $description);
        $this->assertStringContainsString("--bs-primary-rgb", $description);
        $this->assertStringContainsString("--bs-secondary-rgb", $description);
        $this->assertStringContainsString("rgba(var(--bs-secondary-rgb", $description);
        $this->assertStringNotContainsString("--bs-accent", $description);
    }

    /**
     * Course-specific theme overrides the site default in the authoring context.
     */
    public function test_context_prefers_course_theme_when_enabled(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->theme = "boost";
        $CFG->allowcoursethemes = 1;
        $course = $this->getDataGenerator()->create_course();
        $DB->set_field("course", "theme", "learnlingo", ["id" => $course->id]);

        $context = authoring_guidance::course_context((int)$course->id);
        $this->assertSame("learnlingo", $context["theme"]);
        $this->assertSame(["--bs-primary", "--bs-accent", "--bs-primary-rgb", "--bs-accent-rgb"],
            $context["brandcssvariables"]);
        $this->assertStringContainsString("Bloom", $context["instructions"]);
        $this->assertStringContainsString("Theme: learnlingo.", $context["instructions"]);

        $CFG->allowcoursethemes = 0;
        $context = authoring_guidance::course_context((int)$course->id);
        $this->assertSame("boost", $context["theme"]);
        $this->assertSame(["--bs-primary", "--bs-secondary", "--bs-primary-rgb", "--bs-secondary-rgb"],
            $context["brandcssvariables"]);
    }

    /**
     * Themes with no course override use the current Moodle site setting.
     */
    public function test_site_theme_rules_change_without_rewriting_descriptions(): void {
        global $CFG;

        $this->resetAfterTest();
        $CFG->theme = "theme_learnlingo";
        $this->assertSame(["--bs-primary", "--bs-accent", "--bs-primary-rgb", "--bs-accent-rgb"],
            authoring_guidance::css_variables($CFG->theme));
        $this->assertStringContainsString("var(--bs-accent",
            authoring_guidance::tool_description("Create activity."));
        $this->assertStringContainsString("rgba(var(--bs-accent-rgb",
            authoring_guidance::tool_description("Create activity."));

        $CFG->theme = "boost_magnific";
        $this->assertStringContainsString("var(--bs-secondary",
            authoring_guidance::tool_description("Create activity."));
        $this->assertStringContainsString("rgba(var(--bs-secondary-rgb",
            authoring_guidance::tool_description("Create activity."));
    }
}
