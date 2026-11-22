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
use mod_mcp\content\service;
use moodle_exception;

/**
 * Checks content revisions against direct database persistence.
 *
 * @covers \mod_mcp\content\service
 */
final class content_service_test extends advanced_testcase {
    /**
     * Saving a newer draft must not change the published HTML.
     */
    public function test_immutable_revisions_and_optimistic_locking(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "HTML exercise",
        ]);
        $first = service::save((int)$module->id, "<h1>First</h1>", 2, 0);
        $second = service::save((int)$module->id, "<h1>Second</h1>", 2, $first);
        $this->assertNotSame($first, $second);
        $this->assertSame("<h1>First</h1>", service::get((int)$module->id, $first)->content);
        $this->assertSame("<h1>Second</h1>", service::get((int)$module->id, $second)->content);
        $this->assertSame(0, (int)$DB->get_field("mcp", "publishedrevision", ["id" => $module->id]));
        $this->expectException(moodle_exception::class);
        service::save((int)$module->id, "<p>Conflict</p>", 2, $first);
    }

    /**
     * Curly double/single quotes and both dash variants are replaced on ingestion.
     */
    public function test_normalizes_typographic_punctuation_before_saving_and_hashing(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "Typography normalization",
        ]);
        $input = "<!doctype html><html><body><p>“Welcome” — it’s ‘ready’ – now.</p></body></html>";
        $expected = "<!doctype html><html><body><p>\"Welcome\" - it's 'ready' - now.</p></body></html>";

        $this->assertSame($expected, service::normalize_html($input));
        $this->assertSame($expected, service::normalize_html($expected));
        $revision = service::save((int)$module->id, $input, 2, 0);
        $stored = service::get((int)$module->id, $revision);
        $this->assertSame($expected, $stored->content);
        $this->assertSame(hash("sha256", $expected), $stored->sha256);

        // The same content with straight punctuation cannot create a duplicate revision.
        $this->assertSame($revision, service::save((int)$module->id, $expected, 2, $revision));
    }

    /**
     * Drafts require management rights and historical revisions are never served.
     */
    public function test_revision_access_restricts_drafts_and_old_versions(): void {
        $activity = (object)["publishedrevision" => 15, "draftrevision" => 18];

        $this->assertTrue(service::can_access_revision($activity, 15, false));
        $this->assertTrue(service::can_access_revision($activity, 15, true));
        $this->assertFalse(service::can_access_revision($activity, 18, false));
        $this->assertTrue(service::can_access_revision($activity, 18, true));
        $this->assertFalse(service::can_access_revision($activity, 14, true));
        $this->assertFalse(service::can_access_revision($activity, 0, true));
    }

    /**
     * Conversion is literal for inline JavaScript and preserves ordinary ASCII.
     */
    public function test_normalizes_quotes_in_inline_script(): void {
        $input = "<script>const title = “Lesson”; const index = 2;</script><p>‘Example’ — next.</p>";
        $expected = "<script>const title = \"Lesson\"; const index = 2;</script><p>'Example' - next.</p>";
        $this->assertSame($expected, service::normalize_html($input));
    }
}
