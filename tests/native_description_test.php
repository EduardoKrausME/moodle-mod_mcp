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
        $preview = $tool->preview($args, $identity);
        $this->assertArrayNotHasKey("introformat", $preview["description"]);
        $created = $tool->execute($args, $identity);
        $cm = get_coursemodule_from_id("mcp", $created["cmid"], 0, false, MUST_EXIST);
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame(2, (int)$cm->completion);
        $this->assertSame(1, (int)$cm->completionview);
        $this->assertSame(0, (int)$cm->visible);
        $this->assertSame("Initial description", $mcp->intro);
        $this->assertSame((int)FORMAT_HTML, (int)$mcp->introformat);
        $this->assertSame(1, (int)$cm->showdescription);
        $this->assertNull($cm->completiongradeitemnumber);
        $this->assertSame(description::snapshot($cm, $mcp), $created["description"]);
        $this->assertArrayNotHasKey("introformat", $created["description"]);
        $this->assertStringContainsString("Initial description", get_fast_modinfo($course)->get_cm($cm->id)->content);
        $edit = new update_description();
        $input = ["cmid" => (int)$cm->id, "intro" => "New description",
            "expected_description_sha256" => description::snapshot($cm, $mcp)["sha256"]];
        $this->assertTrue($edit->requires_confirmation());
        $editpreview = $edit->preview($input, $identity);
        $this->assertArrayNotHasKey("introformat", $editpreview["after"]);
        $result = $edit->execute($input, $identity);
        $this->assertArrayNotHasKey("introformat", $result["description"]);
        $this->assertSame("New description", $result["description"]["intro"]);
        $this->assertTrue($result["description"]["showdescription"]);
        $this->assertStringContainsString("New description", get_fast_modinfo($course)->get_cm($cm->id)->content);
        $updated = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame("New description", $updated->intro);
        $this->assertSame((int)FORMAT_HTML, (int)$updated->introformat);
        $this->assertSame($mcp->draftrevision, $updated->draftrevision);
        $this->assertSame($mcp->publishedrevision, $updated->publishedrevision);
        $aftercm = get_coursemodule_from_id("mcp", $cm->id, 0, false, MUST_EXIST);
        $this->assertSame((int)$cm->completion, (int)$aftercm->completion);
        $this->assertSame((int)$cm->completionview, (int)$aftercm->completionview);
        $this->assertSame($mcp->grade, $updated->grade);
        $this->expectException(\local_mcp\exception\api_exception::class);
        $edit->execute($input, $identity);
    }

    /**
     * Reject unsupported initial completion and preserve legacy calls.
     *
     * @return void
     */
    public function test_initial_completion_validation(): void {
        global $CFG;
        $this->resetAfterTest();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 1]);
        $this->assertNull(description::creation([], $course)["completion"]);
        $this->assertSame(1, description::creation(["completion" => 1], $course)["completion"]);
        $this->assertFalse(description::creation(["completion" => 0], $course)["requireview"]);
        $this->expectException(\local_mcp\exception\api_exception::class);
        description::creation(["completion" => 2], $course);
    }

    /**
     * Legacy and explicit completion settings must create hidden activities in either course mode.
     */
    public function test_creation_with_completion_enabled_and_disabled(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $tool = new create_activity();
        foreach ([0, 1] as $enabled) {
            $course = $this->getDataGenerator()->create_course(["enablecompletion" => $enabled]);
            $cases = [[], ["completion" => 0]];
            if ($enabled) {
                $cases[] = ["completion" => 1];
                $cases[] = ["completion" => 2, "requireview" => true];
            }
            foreach ($cases as $index => $options) {
                $args = ["courseid" => (int)$course->id, "sectionnum" => 0,
                    "name" => "Material " . $index] + $options;
                $before = $DB->count_records("mcp", ["course" => $course->id]);
                $preview = $tool->preview($args, $identity);
                $this->assertArrayNotHasKey("introformat", $preview["description"]);
                $this->assertSame($options["completion"] ?? null, $preview["description"]["completion"]);
                $this->assertSame($before, $DB->count_records("mcp", ["course" => $course->id]));
                $created = $tool->execute($args, $identity);
                $cm = get_coursemodule_from_id("mcp", $created["cmid"], 0, false, MUST_EXIST);
                $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
                $this->assertSame($before + 1, $DB->count_records("mcp", ["course" => $course->id]));
                $this->assertSame(0, (int)$cm->visible);
                $this->assertSame("", $mcp->intro);
                $this->assertSame((int)FORMAT_HTML, (int)$mcp->introformat);
                $this->assertFalse((bool)$cm->showdescription);
                $this->assertSame(description::snapshot($cm, $mcp), $created["description"]);
                $this->assertSame(0, (int)$mcp->draftrevision);
                $this->assertSame(0, (int)$mcp->publishedrevision);
                if (isset($options["completion"]) || !$enabled) {
                    $this->assertSame($options["completion"] ?? 0, (int)$cm->completion);
                    $this->assertSame($options["requireview"] ?? false, (bool)$cm->completionview);
                    $this->assertNull($cm->completiongradeitemnumber);
                    $this->assertFalse((bool)$mcp->completioninteractive);
                }
            }
        }
    }

    /**
     * Invalid completion must be rejected before writing any activity.
     */
    public function test_disabled_completion_rejects_initial_tracking(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 0]);
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $tool = new create_activity();
        $sequence = $DB->get_field("course_sections", "sequence", ["course" => $course->id, "section" => 0]);
        foreach ([["completion" => 1], ["completion" => 2, "requireview" => true]] as $options) {
            $args = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "Invalid"] + $options;
            foreach (["preview", "execute"] as $method) {
                try {
                    $tool->$method($args, $identity);
                    $this->fail("Disabled completion accepted");
                } catch (\local_mcp\exception\api_exception $e) {
                    $this->assertSame("invalid_initial_completion", $e->machinecode);
                }
                $this->assertSame(0, $DB->count_records("mcp", ["course" => $course->id]));
                $this->assertSame($sequence, $DB->get_field("course_sections", "sequence",
                    ["course" => $course->id, "section" => 0]));
            }
        }
    }

    /**
     * Native descriptions accept safe HTML, hide the storage format and retain concurrency protection.
     */
    public function test_html_description_contract(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $identity = new authenticated_identity($USER->id, "test", ["mcp:write"]);
        $create = new create_activity();
        $edit = new update_description();
        foreach ([$create, $edit] as $tool) {
            $this->assertArrayNotHasKey("introformat", $tool->get_input_schema()["properties"]);
        }
        $args = ["courseid" => (int)$course->id, "sectionnum" => 0, "name" => "HTML",
            "intro" => '<p onclick="alert(1)">Safe <strong>description</strong></p>', "showdescription" => true];
        $preview = $create->preview($args, $identity);
        $this->assertArrayNotHasKey("introformat", $preview["description"]);
        $this->assertStringContainsString("<strong>description</strong>", $preview["description"]["intro"]);
        $this->assertStringNotContainsString("onclick", $preview["description"]["intro"]);
        $created = $create->execute($args, $identity);
        $cm = get_coursemodule_from_id("mcp", $created["cmid"], 0, false, MUST_EXIST);
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame((int)FORMAT_HTML, (int)$mcp->introformat);
        $this->assertSame($preview["description"]["intro"], $mcp->intro);
        $this->assertArrayNotHasKey("introformat", $created["description"]);
        $read = new \mod_mcp\mcp\read\get_activity();
        $current = $read->execute(["cmid" => (int)$cm->id], $identity)["description"];
        $this->assertSame($created["description"], $current);
        // A legacy format change must invalidate an earlier hash even though the format is private.
        $DB->set_field("mcp", "introformat", FORMAT_PLAIN, ["id" => $mcp->id]);
        $legacy = $read->execute(["cmid" => (int)$cm->id], $identity)["description"];
        $this->assertArrayNotHasKey("introformat", $legacy);
        $this->assertNotSame($current["sha256"], $legacy["sha256"]);
        $input = ["cmid" => (int)$cm->id, "intro" => '<p onmouseover="alert(1)"><em>Updated</em></p>',
            "expected_description_sha256" => $legacy["sha256"]];
        $plan = $edit->preview($input, $identity);
        $this->assertArrayNotHasKey("introformat", $plan["before"]);
        $this->assertArrayNotHasKey("introformat", $plan["after"]);
        $this->assertStringContainsString("<em>Updated</em>", $plan["after"]["intro"]);
        $this->assertStringNotContainsString("onmouseover", $plan["after"]["intro"]);
        $result = $edit->execute($input, $identity);
        $updated = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $this->assertSame((int)FORMAT_HTML, (int)$updated->introformat);
        $this->assertSame($plan["after"]["intro"], $updated->intro);
        $this->assertArrayNotHasKey("introformat", $result["description"]);
    }
}
