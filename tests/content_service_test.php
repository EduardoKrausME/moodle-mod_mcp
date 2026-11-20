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
}
