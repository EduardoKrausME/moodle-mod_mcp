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
 * JSON activity assessments and metadata.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\content\metadata;
use mod_mcp\content\service as content_service;
use mod_mcp\question\attempts;

/** JSON assessment never uses Question Engine.
 * @covers \mod_mcp\question\attempts
 * @package mod_mcp
 */
final class assessment_test extends advanced_testcase {
    /**
     * test_json_grading_and_private_answers in the JSON activity API.
     *
     * @return mixed Result.
     */
    public function test_json_grading_and_private_answers(): void {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $mcp = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id, "grade" => 10]);
        $cm = get_coursemodule_from_instance("mcp", $mcp->id);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user((int)$student->id, (int)$course->id, "student");
        $json = ["selection" => "random", "count" => 1, "shuffle" => true,
            "questions" => [
                ["id" => "q1", "text" => "2+2?", "type" => "single", "points" => 1,
                    "options" => [["id" => "a", "text" => "3"], ["id" => "b", "text" => "4"]],
                    "answer" => "b"],
                ["id" => "q2", "text" => "2+3?", "type" => "single", "points" => 1,
                    "options" => [["id" => "a", "text" => "5"], ["id" => "b", "text" => "8"]],
                    "answer" => "a"],
            ]];
        metadata::save((int)$mcp->id, "quiz", $json);
        $revision = content_service::save((int)$mcp->id, "<p>Quiz</p>", (int)$USER->id, 0);
        content_service::publish((int)$mcp->id, $revision);
        $activity = $DB->get_record("mcp", ["id" => $mcp->id], "*", MUST_EXIST);
        $this->assertArrayNotHasKey("answer", metadata::public_information($activity)["questions"][0]);
        $this->setUser($student);
        $attempt = attempts::start($activity, $cm, (int)$student->id);
        $selected = json_decode($attempt->selectedjson, true);
        $this->assertCount(1, $selected);
        $this->assertArrayNotHasKey("answer", attempts::questions($attempt)[0]);
        $qid = $selected[0]["id"];
        attempts::submit($activity, $attempt, [$qid => $selected[0]["answer"]], true);
        $stored = $DB->get_record("mcp_attempts", ["id" => $attempt->id], "*", MUST_EXIST);
        $this->assertSame("finished", $stored->status);
        $this->assertEqualsWithDelta(10.0, (float)$stored->score, 0.001);
        $state = $DB->get_record("mcp_state", ["mcpid" => $mcp->id,
            "userid" => $student->id], "*", MUST_EXIST);
        $this->assertSame($selected[0]["answer"],
            json_decode($state->userdatajson, true)["quiz"]["answers"][$qid]);
    }

    /**
     * test_nullable_activity_information in the JSON activity API.
     *
     * @return mixed Result.
     */
    public function test_nullable_activity_information(): void {
        $this->assertNull(metadata::validate(null, null));
        $this->assertSame("{}", metadata::validate("custom", []));
        $this->assertNotNull(metadata::validate("survey", ["questions" => [
            ["id" => "s1", "text" => "Satisfied?"]]]));
    }

    /**
     * test_invalid_random_configuration_is_rejected in the JSON activity API.
     *
     * @return mixed Result.
     */
    public function test_invalid_random_configuration_is_rejected(): void {
        $this->expectException(\moodle_exception::class);
        metadata::validate("quiz", ["selection" => "random", "count" => 2,
            "questions" => [["id" => "q1", "text" => "test", "answer" => "x",
                "options" => [["id" => "x", "text" => "Yes"],
                    ["id" => "y", "text" => "No"]]]]]);
    }
}
