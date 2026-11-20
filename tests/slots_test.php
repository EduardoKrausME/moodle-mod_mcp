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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Unit tests for native assessment slot references.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\question\slots;

/**
 * Slot references belong to the activity and are initially empty.
 *
 * @covers \mod_mcp\question\slots
 */
final class slots_test extends advanced_testcase {
    /**
 * No question is fabricated or implicitly attached to an empty activity.
 */
    public function test_empty_activity_has_no_native_questions(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id, "name" => "Empty exercise",
        ]);
        $this->assertSame([], slots::active((int)$module->id));
    }
}
