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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

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
        global $CFG, $DB;
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
        $DB->set_field("mcp", "publishedrevision", 1, ["id" => $mcp->id]);
        set_coursemodule_visible((int)$cm->id, 1);
        rebuild_course_cache((int)$course->id, true);
        $this->setUser($student);
        $navigation = api::navigation($cm);
        $this->assertNull($navigation["previous"]);
        $this->assertSame((int)$pagecm->id, $navigation["next"]["cmid"]);
    }
}
