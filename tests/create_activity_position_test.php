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
 * Placement during MCP authoring.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use mod_mcp\authoring\material_theme;
use mod_mcp\mcp\write\create_activity;

/**
 * Exercise the shared local_mcp positioning service during creation.
 * @covers \mod_mcp\mcp\write\create_activity
 */
final class create_activity_position_test extends advanced_testcase {
    /**
     * Create before and after an existing page with complete content.
     */
    public function test_exact_position_and_visible_content(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        material_theme::save("course", (int)$course->id, "Course", "Use readable panels.");
        $a = $this->getDataGenerator()->create_module("page", ["course" => $course->id]);
        $b = $this->getDataGenerator()->create_module("page", ["course" => $course->id]);
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $tool = new create_activity();
        $this->assertTrue($tool->requires_confirmation());
        $args = ["courseid" => $course->id, "sectionnum" => 0, "html" => "<p>Content</p>", "name" => "After", "aftercmid" => $a->cmid];
        $preview = $tool->preview($args, $identity);
        $this->assertSame((int)$b->cmid, $preview["position"]["beforecmid"]);
        $this->assertSame(0, $DB->count_records("mcp", ["course" => $course->id]));
        $after = $tool->execute($args, $identity);
        $before = $tool->execute(["courseid" => $course->id, "sectionnum" => 0, "html" => "<p>Content</p>",
            "name" => "Before", "beforecmid" => $a->cmid], $identity);
        $last = $tool->execute(["courseid" => $course->id, "sectionnum" => 0, "html" => "<p>Content</p>", "name" => "Last"], $identity);
        $expected = implode(",", [$before["cmid"], $a->cmid, $after["cmid"], $b->cmid, $last["cmid"]]);
        $this->assertSame($expected, $DB->get_field("course_sections", "sequence",
            ["course" => $course->id, "section" => 0]));
        foreach ([$after, $before, $last] as $created) {
            $this->assertTrue((bool)$DB->get_field("course_modules", "visible", ["id" => $created["cmid"]]));
            $this->assertSame(1, $DB->count_records("mcp_revisions", ["mcpid" => $created["instanceid"]]));
        }
    }

    /**
     * Invalid anchor must fail before an activity can be added.
     */
    public function test_invalid_anchor_does_not_create_activity(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        material_theme::save("course", (int)$course->id, "Course", "Use readable panels.");
        $page = $this->getDataGenerator()->create_module("page", ["course" => $other->id]);
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $tool = new create_activity();
        try {
            $tool->execute(["courseid" => $course->id, "sectionnum" => 0, "html" => "<p>Content</p>",
                "name" => "Invalid", "aftercmid" => $page->cmid], $identity);
            $this->fail("Cross-course anchor accepted");
        } catch (api_exception $e) {
            $this->assertSame("activity_position_mismatch", $e->machinecode);
        }
        $this->assertSame(0, $DB->count_records("mcp", ["course" => $course->id]));
    }
}
