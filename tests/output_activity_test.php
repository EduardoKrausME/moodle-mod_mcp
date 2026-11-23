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
 * Tests for the construction screen connection indicators.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use context_module;
use mod_mcp\content\service;
use mod_mcp\output\activity;
use moodle_url;

/**
 * Only the current user's active OAuth connections count for the activity guide.
 *
 * @covers \mod_mcp\output\activity
 */
final class output_activity_test extends advanced_testcase {
    /**
     * SDK loads by URL before authored JavaScript without inlining its source.
     */
    public function test_sdk_is_loaded_by_url_before_authored_scripts(): void {
        $html = '<!doctype html><html><head><script>window.authored = true;</script></head><body>Hi</body></html>';
        $result = activity::inject_sdk($html);

        $sdkurl = (new moodle_url("/mod/mcp/sdk.js"))->out(false);
        $script = '<script src="' . s($sdkurl) . '"></script>';
        $sdkposition = strpos($result, $script);
        $authoredposition = strpos($result, "window.authored = true;");
        $this->assertNotFalse($sdkposition);
        $this->assertNotFalse($authoredposition);
        $this->assertLessThan($authoredposition, $sdkposition);
        $this->assertSame(0, strpos($result, "<!doctype html>"));
        $this->assertSame(1, substr_count($result, $script));
        $this->assertSame(1, substr_count($result, '<style id="mod-mcp-theme-vars">:root{}</style>'));
        $this->assertLessThan($sdkposition, strpos($result, 'id="mod-mcp-theme-vars"'));
        $this->assertStringNotContainsString('Object.defineProperty(window, "MoodleMCP"', $result);
        $this->assertStringContainsString('<script>window.authored = true;</script>', $result);
    }

    /**
     * Missing-head documents keep their doctype and load the SDK before the body.
     */
    public function test_sdk_url_injection_without_head_preserves_doctype(): void {
        $html = '<!doctype html><html lang="pt-BR"><body><p>Test</p></body></html>';
        $result = activity::inject_sdk($html);
        $this->assertSame(0, strpos($result, "<!doctype html>"));
        $this->assertLessThan(strpos($result, "<body>"), strpos($result, "<script src="));
        $this->assertSame(1, substr_count($result, (new moodle_url("/mod/mcp/sdk.js"))->out(false)));
    }

    /**
     * The iframe URL points to the immutable revision without embedding its HTML in the page.
     */
    public function test_page_provides_revision_pluginfile_url(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $cm = get_coursemodule_from_instance("mcp", $module->id);
        $html = "<!doctype html><html><body>Isolated content</body></html>";
        $revisionid = service::save((int)$module->id, $html, 2);
        $module = $DB->get_record("mcp", ["id" => $module->id], "*", MUST_EXIST);

        $preview = activity::page($module, $cm, true, true);
        $expectedurl = moodle_url::make_pluginfile_url(
            context_module::instance((int)$cm->id)->id, "mod_mcp", "content",
            $revisionid, "/", "index.html"
        )->out(false);
        $this->assertTrue($preview["hascontent"]);
        $this->assertSame($expectedurl, $preview["url_content"]);
        $this->assertArrayNotHasKey("content", $preview);
        $this->assertSame($html, service::get((int)$module->id, $revisionid)->content);

        $DB->set_field("mcp", "publishedrevision", $revisionid, ["id" => $module->id]);
        $module->publishedrevision = $revisionid;
        $published = activity::page($module, $cm, false, false);
        $this->assertTrue($published["hascontent"]);
        $this->assertSame($expectedurl, $published["url_content"]);
    }

    /**
     * The empty activity has no connection for the current user.
     */
    public function test_empty_activity_without_connection(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $cm = get_coursemodule_from_instance("mcp", $module->id);

        $data = activity::page($module, $cm, true, false);
        $this->assertFalse($data["hascontent"]);
        $this->assertFalse($data["hasconnection"]);
        $this->assertSame((int)$cm->id, $data["cmid"]);
        $this->assertSame(activity::prompt($module, $cm), $data["prompt"]);
        $this->assertSame(activity::prompt($module, $cm, "page"), $data["promptpage"]);
        $this->assertNotSame($data["prompt"], $data["promptpage"]);
        $blum = get_string("promptblum", "mcp");
        foreach ($data["promptoptions"] as $option) {
            $this->assertSame(1, substr_count($option["prompt"], $blum));
            $this->assertStringNotContainsString('{$a->blum}', $option["prompt"]);
        }
        $instructions = get_string("promptinstructions", "mcp");
        $this->assertStringContainsString("<footer>", $instructions);
        $this->assertSame(1, preg_match("/(NENHUM RODAPÉ|ANY FOOTER)/", $instructions));
        $this->assertSame(1, preg_match("/(NÃO devem ser transformadas|DO NOT turn them)/", $instructions));
        foreach (["prompt", "promptpage"] as $key) {
            $this->assertSame(0, strpos($data[$key], $instructions));
            $this->assertSame(1, substr_count($data[$key], $instructions));
            $this->assertStringContainsString("PDF", $data[$key]);
        }
        foreach (["prompt", "promptpage"] as $key) {
            $this->assertSame(1, preg_match("/(Approve|Aprovar)/", $data[$key]));
            $this->assertSame(1, preg_match("/(Reject|Rejeitar)/", $data[$key]));
        }
        // A page prompt must request both structured options and a freely typed description.
        $this->assertSame(1, preg_match("/(opções selecionáveis|selectable choices)/i", $data["promptpage"]));
        $this->assertSame(1, preg_match("/(texto livre|free-text)/i", $data["promptpage"]));
        $this->assertSame(1, preg_match("/(opções numeradas|numbered options)/i", $data["promptpage"]));
        $this->assertSame(1, preg_match("/(Aguarde as respostas|Wait for the teacher)/i", $data["promptpage"]));
        $this->assertStringContainsString("prefers-color-scheme", $data["prompt"]);
        $this->assertStringContainsString("prefers-color-scheme", $data["promptpage"]);
        $this->assertStringContainsString((string)$cm->id, $data["prompt"]);
        $this->assertStringContainsString((string)$cm->id, $data["promptpage"]);
        $this->assertArrayNotHasKey("haslocalmcp", $data);
        $this->assertSame(
            (new moodle_url("/local/mcp/new_connection.php"))->out(false),
            $data["connectionurl"]
        );
        $this->assertFalse(activity::has_active_connection(0));
    }

    /**
     * Colour variables follow the active theme, while dark mode is always required.
     */
    public function test_css_guidance_uses_theme_variables_and_dark_mode(): void {
        foreach (["degrade", "eadflix", "eadtraining", "boost_magnific",
                  "theme_degrade", "theme_eadflix", "theme_eadtraining", "theme_boost_magnific"] as $theme) {
            $guidance = activity::css_instructions($theme);
            $this->assertStringContainsString(
                preg_replace("/^theme_/", "", $theme), $guidance);
            $this->assertStringContainsString("--bs-primary", $guidance);
            $this->assertStringContainsString("--bs-secondary", $guidance);
            $this->assertStringNotContainsString("--bs-accent", $guidance);
            $this->assertStringContainsString("prefers-color-scheme", $guidance);
        }

        foreach (["learnlingo", "theme_learnlingo", "custom_learnlingo_dark"] as $theme) {
            $guidance = activity::css_instructions($theme);
            $this->assertStringContainsString(
                preg_replace("/^theme_/", "", $theme), $guidance);
            $this->assertStringContainsString("--bs-primary", $guidance);
            $this->assertStringContainsString("--bs-accent", $guidance);
            $this->assertStringNotContainsString("--bs-secondary", $guidance);
            $this->assertStringContainsString("prefers-color-scheme", $guidance);
        }

        $guidance = activity::css_instructions("boost");
        $this->assertStringNotContainsString("--bs-primary", $guidance);
        $this->assertStringNotContainsString("--bs-secondary", $guidance);
        $this->assertStringNotContainsString("--bs-accent", $guidance);
        $this->assertStringContainsString("prefers-color-scheme", $guidance);
    }

    /**
     * Theme brand colours must also control gradients and hover states, not just plain backgrounds.
     */
    public function test_css_guidance_forbids_invented_brand_gradients(): void {
        foreach (["learnlingo" => "--bs-accent", "eadflix" => "--bs-secondary"] as $theme => $accent) {
            $guidance = activity::css_instructions($theme);
            $expected = "background: linear-gradient(123deg, var(--bs-primary, #0d6efd), " .
                "var({$accent}, var(--bs-primary, #0d6efd)));";
            $this->assertStringContainsString($expected, $guidance);
            $this->assertStringContainsString("color-mix()", $guidance);
            $this->assertSame(1, preg_match("/(NÃO invente|Do not invent)/i", $guidance));
            $this->assertSame(1, preg_match("/(revise TODO o CSS|audit the entire CSS)/i", $guidance));
        }
    }

    /**
     * Connections must belong to this user and have active grant and client states.
     */
    public function test_only_enabled_nonrevoked_own_connections_count(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $now = time();

        $clientid = $DB->insert_record("local_mcp_oauth_client", (object)[
            "clientid" => "mod_mcp_output_test",
            "name" => "Test client",
            "redirecturis" => '["https://example.test/callback"]',
            "enabled" => 1,
            "clienttype" => "public",
            "dynamic" => 0,
            "timecreated" => $now,
            "timemodified" => $now,
        ]);
        $connectionid = $DB->insert_record("local_mcp_connection", (object)[
            "userid" => $user->id,
            "clientid" => $clientid,
            "scopes" => "mcp:read mcp:write",
            "enabled" => 1,
            "timecreated" => $now,
        ]);

        $this->assertTrue(activity::has_active_connection((int)$user->id));
        $this->assertFalse(activity::has_active_connection((int)$other->id));

        $DB->set_field("local_mcp_connection", "enabled", 0, ["id" => $connectionid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));

        $DB->set_field("local_mcp_connection", "enabled", 1, ["id" => $connectionid]);
        $DB->set_field("local_mcp_connection", "revokedat", $now, ["id" => $connectionid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));

        $DB->set_field("local_mcp_connection", "revokedat", null, ["id" => $connectionid]);
        $DB->set_field("local_mcp_oauth_client", "enabled", 0, ["id" => $clientid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));
    }
}
