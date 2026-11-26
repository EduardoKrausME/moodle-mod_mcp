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
 * Runtime API, user state and privacy boundaries.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\runtime\api;
use mod_mcp\runtime\state;
use moodle_exception;
use required_capability_exception;

/**
 * Exercise the native database and permission boundaries.
 * @covers \mod_mcp\runtime\state
 * @covers \mod_mcp\runtime\api
 */
final class runtime_api_test extends advanced_testcase {
    /**
     * State resumes for the owning user, but content belongs to one revision.
     */
    public function test_progress_is_owned_versioned_and_revision_aware(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        $first = state::load((int)$mcp->id, (int)$alice->id, 23);
        $this->assertSame(0, $first["version"]);
        $this->assertSame([], $first["data"]);

        $saved = state::save((int)$mcp->id, (int)$alice->id, 23, [
            "version" => 0,
            "location" => "unit-2",
            "data" => ["step" => 4],
        ]);
        $this->assertSame(1, $saved["version"]);
        $this->assertSame("unit-2", $saved["location"]);
        $this->assertSame(["step" => 4], $saved["data"]);
        $this->assertSame(0, state::load((int)$mcp->id, (int)$bob->id, 23)["version"]);

        state::complete((int)$mcp->id, (int)$alice->id, 23);
        $revised = state::load((int)$mcp->id, (int)$alice->id, 24);
        $this->assertSame("", $revised["location"]);
        $this->assertSame([], $revised["data"]);
        $this->assertTrue($revised["completed"]);
        $this->assertSame(1, $DB->count_records("mcp_state", ["mcpid" => $mcp->id]));
    }

    /**
     * Version conflicts must not silently overwrite progress.
     */
    public function test_progress_conflict_rejects_an_older_browser(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        state::save((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "location" => "a", "data" => [],
        ]);
        $this->expectException(moodle_exception::class);
        state::save((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "location" => "b", "data" => [],
        ]);
    }

    /**
     * Structured progress retains custom state and belongs only to the current student.
     */
    public function test_structured_progress_status_preserves_state_and_user_scope(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        $this->assertSame("notstarted",
            state::status((int)$mcp->id, (int)$alice->id, 7, false)["status"]);

        state::save((int)$mcp->id, (int)$alice->id, 7, [
            "version" => 0, "location" => "intro", "data" => ["answer" => "draft"],
        ]);
        state::save_status((int)$mcp->id, (int)$alice->id, 7, [
            "version" => 1, "location" => "chapter-2",
            "percent" => 40, "currentstep" => 2, "totalsteps" => 5,
        ]);
        $status = state::status((int)$mcp->id, (int)$alice->id, 7, false);
        $this->assertSame("inprogress", $status["status"]);
        $this->assertSame(40, $status["percent"]);
        $this->assertSame(2, $status["currentstep"]);
        $this->assertSame(5, $status["totalsteps"]);
        $this->assertSame("chapter-2", $status["location"]);
        $this->assertSame(2, $status["version"]);
        $this->assertFalse($status["moodlecompleted"]);
        $this->assertSame("draft",
            state::load((int)$mcp->id, (int)$alice->id, 7)["data"]["answer"]);
        $this->assertSame("notstarted",
            state::status((int)$mcp->id, (int)$bob->id, 7, false)["status"]);
        $this->assertSame(0,
            state::status((int)$mcp->id, (int)$bob->id, 7, false)["percent"]);
    }

    /**
     * Saving a finished reading position cannot forge Moodle completion.
     */
    public function test_status_revision_reset_and_native_completion_separation(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        state::save_status((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "location" => "end",
            "percent" => 100, "currentstep" => 3, "totalsteps" => 3,
        ]);
        $status = state::status((int)$mcp->id, (int)$user->id, 1, false);
        $this->assertSame("finished", $status["status"]);
        $this->assertFalse($status["moodlecompleted"]);
        $newrevision = state::status((int)$mcp->id, (int)$user->id, 2, false);
        $this->assertSame("notstarted", $newrevision["status"]);
        $this->assertSame(0, $newrevision["percent"]);
        $this->assertSame("", $newrevision["location"]);
        state::complete((int)$mcp->id, (int)$user->id, 1);
        $this->assertTrue(state::status((int)$mcp->id, (int)$user->id, 1, true)["moodlecompleted"]);
    }

    /**
     * Do not accept percentages, steps or stale writes that violate the contract.
     */
    public function test_structured_progress_status_rejects_stale_versions(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        state::save_status((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "percent" => 20, "currentstep" => 1, "totalsteps" => 5,
        ]);
        $this->expectException(moodle_exception::class);
        state::save_status((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "percent" => 60, "currentstep" => 3, "totalsteps" => 5,
        ]);
    }

    /**
     * Invalid progress cannot affect the user's existing saved data.
     */
    public function test_structured_progress_status_rejects_invalid_steps(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $user = $this->getDataGenerator()->create_user();
        $this->expectException(moodle_exception::class);
        state::save_status((int)$mcp->id, (int)$user->id, 1, [
            "version" => 0, "percent" => 101, "currentstep" => 6, "totalsteps" => 5,
        ]);
    }

    /**
     * Limit telemetry, expose only aggregates and keep user boundaries intact.
     */
    public function test_tracking_summary_and_user_separation(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $alice = $this->getDataGenerator()->create_user();
        $bob = $this->getDataGenerator()->create_user();
        foreach ([$alice, $bob] as $user) {
            state::track((int)$mcp->id, (int)$user->id, 1, [
                "type" => "interaction", "action" => "answered",
                "object" => "step-1", "result" => ["ok" => true],
            ]);
        }
        $summary = state::summary((int)$mcp->id);
        $this->assertCount(1, $summary);
        $this->assertSame(2, $summary[0]["total"]);
        $this->assertSame(2, $summary[0]["participants"]);
        $this->assertArrayNotHasKey("userid", $summary[0]);
    }

    /**
     * A learner must not access the teacher's aggregate report.
     */
    public function test_student_cannot_read_tracking_summary(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $cm = get_coursemodule_from_instance("mcp", $mcp->id);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$student->id, (int)$course->id, "student");
        $this->setUser($student);
        $record = $DB->get_record("mcp", ["id" => $mcp->id], "*", MUST_EXIST);
        $this->expectException(required_capability_exception::class);
        api::run($record, $cm, "tracking.summary", []);
    }

    /**
     * Build navigation only from the user's accessible activity order.
     */
    public function test_navigation_uses_accessible_course_modules(): void {
        global $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $cm = get_coursemodule_from_instance("mcp", $mcp->id);
        $page = $this->getDataGenerator()->create_module("page", [
            "course" => $course->id, "name" => "Next resource",
            "content" => "Hello",
        ]);
        $pagecm = get_coursemodule_from_instance("page", $page->id);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$student->id, (int)$course->id, "student");
        set_coursemodule_visible((int)$cm->id, 1);
        rebuild_course_cache((int)$course->id, true);
        $this->setUser($student);
        $navigation = api::navigation($cm);
        $this->assertNull($navigation["previous"]);
        $this->assertSame((int)$pagecm->id, $navigation["next"]["cmid"]);
    }
}
