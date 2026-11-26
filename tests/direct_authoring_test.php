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
 * Direct activity authoring regression tests.
 *
 * @package    mod_mcp
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use local_mcp\security\authenticated_identity;
use mod_mcp\content\service;
use mod_mcp\mcp\provider;
use mod_mcp\mcp\write\create_activity;
use mod_mcp\mcp\write\save_content;

/**
 * Tests complete creation, current revisions and native visibility.
 *
 * @covers \mod_mcp\mcp\write\create_activity
 * @covers \mod_mcp\mcp\write\save_content
 * @covers \mod_mcp\content\service
 */
final class direct_authoring_test extends advanced_testcase {
    /**
     * Complete creation requires only one write; subsequent saves never unhide the activity.
     */
    public function test_create_and_edit_preserve_visibility(): void {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/course/lib.php");
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity($USER->id, "test", ["mcp:read", "mcp:write"]);
        $create = new create_activity();
        $html = "<html><body><h1>Complete content</h1></body></html>";
        $args = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "Complete", "html" => $html];
        $this->assertContains("html", $create->get_input_schema()["required"]);
        $this->assertTrue($create->preview($args, $identity)["visible_by_default"]);
        $this->assertFalse($DB->record_exists("mcp", ["course" => $course->id]));
        $created = $create->execute($args, $identity);
        $this->assertTrue($created["visible"]);
        $this->assertSame($html, service::get($created["instanceid"], $created["revision"])->content);
        set_coursemodule_visible($created["cmid"], 0);
        $updated = (new save_content())->execute([
            "cmid" => $created["cmid"], "html" => "<p>Updated</p>", "expectedrevision" => $created["revision"],
        ], $identity);
        $this->assertFalse((bool)$DB->get_field("course_modules", "visible", ["id" => $created["cmid"]]));
        $this->assertSame($updated["revision"],
            (int)$DB->get_field("mcp", "publishedrevision", ["id" => $created["instanceid"]]));
        $this->assertSame($html, service::get($created["instanceid"], $created["revision"])->content);
    }

    /**
     * Invalid content or missing quiz information leaves no empty module behind.
     */
    public function test_invalid_creation_has_no_side_effects(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $create = new create_activity();
        $base = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "Invalid"];
        $sequence = $DB->get_field("course_sections", "sequence", ["course" => $course->id, "section" => 0]);
        foreach ([["html" => " "], ["html" => "<p>Quiz</p>", "type" => "quiz"]] as $invalid) {
            try {
                $create->execute($base + $invalid, $identity);
                $this->fail("Invalid content accepted");
            } catch (\local_mcp\exception\api_exception $e) {
                $this->assertFalse($DB->record_exists("mcp", ["course" => $course->id]));
                $this->assertSame($sequence,
                    $DB->get_field("course_sections", "sequence", ["course" => $course->id, "section" => 0]));
            }
        }
    }

    /**
     * Discovery no longer exposes the separate publication tool.
     */
    public function test_no_publication_tool(): void {
        $names = array_map(static fn($tool) => $tool->get_name(), (new provider())->get_write_tools());
        $this->assertNotContains("mcp_publish", $names);
    }
}
