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
 * Unit tests for immutable MCP content revisions.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use local_mcp\protocol\write_text_normalizer;
use local_mcp\security\authenticated_identity;
use local_mcp\security\untrusted_content;
use mod_mcp\mcp\read\get_activity;
use mod_mcp\mcp\write\save_content;
use mod_mcp\content\service;
use moodle_exception;

/**
 * Checks content revisions against direct database persistence.
 *
 * @covers \mod_mcp\content\service
 */
final class content_service_test extends advanced_testcase {
    /**
     * Saving a revision replaces active HTML without deleting history.
     */
    public function test_immutable_revisions_and_optimistic_locking(): void {
        global $DB;
        $this->resetAfterTest();
        set_config("materialtheme_default", json_encode([
            "name" => "Test theme",
            "style" => "Use spacious cards and readable typography.",
        ]), "mod_mcp");
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "HTML exercise",
        ]);
        $first = service::save((int)$module->id, "<h1>First</h1>", 2, 0);
        $second = service::save((int)$module->id, "<h1>Second</h1>", 2, $first);
        $this->assertNotSame($first, $second);
        $this->assertSame("<h1>First</h1>", service::get((int)$module->id, $first)->content);
        $this->assertSame("<h1>Second</h1>", service::get((int)$module->id, $second)->content);
        $this->assertSame($second, (int)$DB->get_field("mcp", "publishedrevision", ["id" => $module->id]));
        $this->expectException(moodle_exception::class);
        service::save((int)$module->id, "<p>Conflict</p>", 2, $first);
    }

    /**
     * All source bytes survive persistence and identical resaves reuse the same revision.
     */
    public function test_preserves_source_before_saving_and_hashing(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "Source integrity",
        ]);
        $input = "\r\n<!doctype html><html><body><p>“Welcome” — it’s ‘ready’ – now.</p></body></html>\n";
        $revision = service::save((int)$module->id, $input, 2, 0);
        $stored = service::get((int)$module->id, $revision);
        $this->assertSame($input, $stored->content);
        $this->assertSame(hash("sha256", $input), $stored->sha256);
        $this->assertSame($revision, service::save((int)$module->id, $input, 2, $revision));

        // Different whitespace is different source and must create a distinct immutable revision.
        $next = service::save((int)$module->id, trim($input), 2, $revision);
        $this->assertNotSame($revision, $next);
        $this->assertSame($input, service::get((int)$module->id, $revision)->content);
    }

    /**
     * Only active content is served; legacy drafts remain stored.
     */
    public function test_revision_access_restricts_old_versions(): void {
        $activity = (object)["publishedrevision" => 15, "draftrevision" => 18];

        $this->assertTrue(service::can_access_revision($activity, 15, false));
        $this->assertTrue(service::can_access_revision($activity, 15, true));
        $this->assertFalse(service::can_access_revision($activity, 18, false));
        $this->assertFalse(service::can_access_revision($activity, 18, true));
        $this->assertFalse(service::can_access_revision($activity, 14, true));
        $this->assertFalse(service::can_access_revision($activity, 0, true));
    }

    /**
     * The WRITE normalizer, preview, storage and manager READ agree on the exact bytes.
     *
     * @covers \mod_mcp\mcp\write\save_content
     * @covers \mod_mcp\mcp\read\get_activity
     */
    public function test_mcp_source_round_trip(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->theme = "boost";
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "HTML integrity",
        ]);
        $html = "\r\n<!doctype html><html><head>"
            . '<style>.label::before{content:"—"}</style>'
            . '</head><body><p>“Olá” — ‘mundo’ –</p>'
            . '<svg viewBox="0 0 10 10"><circle cx="5" cy="5" r="2"/></svg>'
            . '<script>const message = "—";</script></body></html>' . "\n";
        $identity = new authenticated_identity($USER->id, "test", ["mcp:read", "mcp:write"]);
        $args = write_text_normalizer::normalize([
            "cmid" => (int)$module->cmid, "html" => $html, "expectedrevision" => 0,
        ]);
        $this->assertSame($html, $args["html"]);
        $writer = new save_content();
        $reader = new get_activity();
        $this->assertTrue($writer->requires_confirmation());
        $this->assertSame("mod/mcp:manage", $reader->get_required_capability());
        $preview = $writer->preview($args, $identity);
        $this->assertTrue($preview["updates_active_content"]);
        $this->assertSame(0, $DB->count_records("mcp_revisions", ["mcpid" => $module->id]));
        $saved = $writer->execute($args, $identity);
        $this->assertTrue($saved["updated"]);
        $this->assertTrue($saved["preserves_visibility"]);
        $this->assertSame(hash("sha256", $html), service::get((int)$module->id, $saved["revision"])->sha256);
        $read = $reader->execute(["cmid" => (int)$module->cmid], $identity);
        $this->assertSame($html, $read["html"]);
        $this->assertSame(untrusted_content::text($html), $read["plaintext"]);
        $this->assertStringNotContainsString("<script>", $read["plaintext"]);
        $this->assertSame($saved["revision"], $read["revision"]);
    }

    /**
     * Whitespace-only source is invalid without trimming any valid document.
     */
    public function test_rejects_blank_source(): void {
        $this->expectException(moodle_exception::class);
        service::validate_html(" \r\n\t");
    }
}
