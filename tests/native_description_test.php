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
 * Native description and completion regression tests.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use local_mcp\security\authenticated_identity;
use mod_mcp\mcp\write\create_activity;
use mod_mcp\mcp\write\update_description;
use mod_mcp\mcp\description;

/**
 * Regression coverage for native metadata without authored revision changes.
 * @covers \mod_mcp\mcp\description
 * @covers \mod_mcp\mcp\write\create_activity
 * @covers \mod_mcp\mcp\write\update_description
 */
final class native_description_test extends advanced_testcase {
    /**
     * Create with description and automatic view completion; then edit only the description.
     */
    public function test_description_and_initial_completion(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 1]);
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $tool = new create_activity();
        $args = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "Material",
            "intro" => "Initial description", "showdescription" => true, "completion" => 2, "requireview" => true];
        $tool->preview($args, $identity);
        $created = $tool->execute($args, $identity);
        $cm = get_coursemodule_from_id("mcp", $created["cmid"], 0, false, MUST_EXIST);
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame(2, (int)$cm->completion);
        $this->assertSame(1, (int)$cm->completionview);
        $this->assertSame(0, (int)$cm->visible);
        $this->assertSame("Initial description", $mcp->intro);
        $this->assertSame(FORMAT_PLAIN, (int)$mcp->introformat);
        $this->assertSame(1, (int)$cm->showdescription);
        $this->assertNull($cm->completiongradeitemnumber);
        $this->assertSame(description::snapshot($cm, $mcp), $created["description"]);
        $this->assertStringContainsString("Initial description", get_fast_modinfo($course)->get_cm($cm->id)->content);
        $edit = new update_description();
        $input = ["cmid" => (int)$cm->id, "intro" => "New description",
            "expected_description_sha256" => description::snapshot($cm, $mcp)["sha256"]];
        $this->assertTrue($edit->requires_confirmation());
        $result = $edit->execute($input, $identity);
        $this->assertSame("New description", $result["description"]["intro"]);
        $this->assertTrue($result["description"]["showdescription"]);
        $this->assertStringContainsString("New description", get_fast_modinfo($course)->get_cm($cm->id)->content);
        $updated = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame("New description", $updated->intro);
        $this->assertSame($mcp->draftrevision, $updated->draftrevision);
        $this->assertSame($mcp->publishedrevision, $updated->publishedrevision);
        $this->expectException(\local_mcp\exception\api_exception::class);
        $edit->execute($input, $identity);
    }
}
