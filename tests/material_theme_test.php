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
 * Coverage for visual material themes stored as Moodle plugin configuration.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use mod_mcp\mcp\write\create_activity;
use mod_mcp\mcp\authoring_error;
use mod_mcp\authoring\material_theme;
use mod_mcp\content\service;
use mod_mcp\mcp\authoring_guidance;
use mod_mcp\mcp\read\get_material_theme;
use mod_mcp\mcp\write\configure_material_theme;
use moodle_exception;

/**
 * Test reusable material themes and the first-use configuration requirement.
 *
 * @covers \mod_mcp\authoring\material_theme
 */
final class material_theme_test extends advanced_testcase {
    /**
     * A first authoring operation must not invent a default theme.
     */
    public function test_first_authoring_requires_registered_theme(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $this->assertNull(material_theme::effective((int)$course->id));
        $this->assertFalse(authoring_guidance::course_context((int)$course->id)["configured"]);
        try {
            material_theme::require_for_course((int)$course->id);
            $this->fail("A missing visual theme must block material creation");
        } catch (moodle_exception $e) {
            $this->assertSame("materialthemerequired", $e->errorcode);
        }
    }

    /**
     * Both scopes use config_plugins, with per-course settings taking priority.
     */
    public function test_site_and_course_config_priority_without_new_tables(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        material_theme::save("site", 0, "Site default", "Use clear sections with generous whitespace.");
        $site = material_theme::effective((int)$course->id);
        $this->assertSame("site", $site["scope"]);
        $this->assertSame("Site default", $site["name"]);
        material_theme::save("course", (int)$course->id, "Course style",
            "Use card grids and illustrated examples.");
        $override = material_theme::effective((int)$course->id);
        $this->assertSame("course", $override["scope"]);
        $this->assertSame("Course style", $override["name"]);
        $this->assertSame("Site default", material_theme::site_default()["name"]);
        $this->assertTrue($DB->record_exists("config_plugins",
            ["plugin" => "mod_mcp", "name" => "materialtheme_default"]));
        $this->assertTrue($DB->record_exists("config_plugins",
            ["plugin" => "mod_mcp", "name" => "materialtheme_course_" . $course->id]));
        $context = authoring_guidance::course_context((int)$course->id);
        $this->assertTrue($context["configured"]);
        $this->assertSame("Course style", $context["materialtheme"]["name"]);
        $this->assertStringContainsString("card grids", $context["instructions"]);
    }

    /**
     * Creating a module is distinct from generating its first HTML revision.
     */
    public function test_html_draft_is_rejected_until_material_theme_is_registered(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        try {
            service::save((int)$module->id, "<p>Draft</p>", 2, 0);
            $this->fail("HTML was accepted without a material theme");
        } catch (moodle_exception $e) {
            $this->assertSame("materialthemerequired", $e->errorcode);
        }
        $this->assertSame(0, $DB->count_records("mcp_revisions", ["mcpid" => $module->id]));
        material_theme::save("course", (int)$course->id, "Course", "Use readable panels.");
        $rev = service::save((int)$module->id, "<p>Draft</p>", 2, 0);
        $this->assertGreaterThan(0, $rev);
    }

    /**
     * Discovery advertises the bootstrap tool before content can be created.
     */
    public function test_discovery_includes_bootstrap_tool_instructions(): void {
        $this->assertStringContainsString("FIRST-USE SETUP",
            (new configure_material_theme())->get_description());
        $this->assertStringContainsString("mcp_configure_material_theme",
            (new get_material_theme())->get_description());
    }
    /**
     * Preview refuses an unconfigured course and never creates a module.
     */
    public function test_create_preview_requires_theme_and_is_read_only(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity((int)$USER->id, "test", ["mcp:read", "mcp:write"]);
        $tool = new create_activity();
        $args = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "Draft"];
        try {
            $tool->preview($args, $identity);
            $this->fail("Missing theme must prevent confirmation");
        } catch (api_exception $failure) {
            $this->assertSame("material_theme_required", $failure->machinecode);
            $this->assertSame(409, $failure->httpstatus);
            $this->assertSame("mcp_configure_material_theme", $failure->details["required_action"]);
            $this->assertStringContainsString("Configure", $failure->get_public_message());
        }
        $this->assertFalse($DB->record_exists("mcp", ["course" => $course->id]));
        material_theme::save("site", 0, "Default", "Use cards.");
        $this->assertSame("site", $tool->preview($args, $identity)["materialtheme"]["scope"]);
        material_theme::save("course", (int)$course->id, "Course", "Use panels.");
        $this->assertSame("course", $tool->preview($args, $identity)["materialtheme"]["scope"]);
        $this->assertTrue($tool->requires_confirmation());
        $this->assertFalse($DB->record_exists("mcp", ["course" => $course->id]));
        $created = $tool->execute($args, $identity);
        $cm = get_coursemodule_from_id("mcp", $created["cmid"], $course->id, false, MUST_EXIST);
        $this->assertSame(0, (int)$cm->visible);
    }

    /**
     * Invalid configuration does not prevent discovery or an approved replacement preview.
     */
    public function test_invalid_theme_recovery(): void {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity((int)$USER->id, "test", ["mcp:read", "mcp:write"]);
        set_config("materialtheme_default", "{broken", "mod_mcp");
        $this->assertStringContainsString("mcp_get_material_theme", (new create_activity())->get_description());
        $tool = new configure_material_theme();
        $args = ["scope" => "site", "name" => "Default", "style" => "Use panels."];
        $preview = $tool->preview($args, $identity);
        $this->assertTrue($preview["previous_invalid"]);
        $this->assertSame("{broken", get_config("mod_mcp", "materialtheme_default"));
        $this->assertTrue($tool->requires_confirmation());
        $tool->execute($args, $identity);
        $this->assertSame("Default", material_theme::effective((int)$course->id)["name"]);
    }

    /**
     * Expected authoring errors are actionable; unrelated native exceptions remain private.
     */
    public function test_authoring_error_mapping(): void {
        $failure = new moodle_exception("revisionconflict", "mcp");
        $this->assertSame("revision_conflict", authoring_error::translate($failure)->machinecode);
        $failure = new moodle_exception("fixedbrandcolour", "mcp");
        $this->assertSame("fixed_brand_colour", authoring_error::translate($failure)->machinecode);
        $failure = new moodle_exception("materialthemeinvalid", "mcp");
        $this->assertSame("material_theme_invalid", authoring_error::translate($failure)->machinecode);
        $failure = new moodle_exception("unexpected", "mcp");
        $this->assertSame($failure, authoring_error::translate($failure));
    }
}
