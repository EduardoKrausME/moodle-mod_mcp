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
 * Native Question Engine and gradebook integration tests.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\content\service as content_service;
use mod_mcp\question\attempts;
use mod_mcp\question\slots;
use question_engine;

/**
 * Verifies that student grades come from server-graded question attempts.
 *
 * @covers \mod_mcp\question\attempts
 * @covers \mod_mcp\question\slots
 */
final class assessment_test extends advanced_testcase {
    /**
     * Publish a question and grade one student's native response.
     */
    public function test_native_question_answer_is_persisted_and_graded(): void {
        global $CFG, $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();

        $qgenerator = $this->getDataGenerator()->get_plugin_generator("core_question");
        [$category, $course, $questioncategory, $questions] = $qgenerator->setup_course_and_questions();
        $activity = $this->getDataGenerator()->create_module("mcp",
            ["course" => $course->id, "name" => "Graded MCP activity"]);
        $cm = get_coursemodule_from_id("mcp", (int)$activity->cmid, 0, false, MUST_EXIST);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, "student");

        $attached = slots::attach((int)$activity->id, (int)$questions[0]->id, 1.0, (int)$USER->id);
        $this->assertSame((int)$questions[0]->id, $attached["questionid"]);
        $revision = content_service::save((int)$activity->id,
            "<!doctype html><html><body><p>Answer below.</p></body></html>", (int)$USER->id, 0);
        content_service::publish((int)$activity->id, $revision);

        $mcp = $DB->get_record("mcp", ["id" => $activity->id], "*", MUST_EXIST);
        $this->assertSame($revision, (int)$mcp->publishedrevision);

        $this->setUser($student);
        $attempt = attempts::start($mcp, $cm, (int)$student->id);
        require_once($CFG->dirroot . "/question/engine/lib.php");
        $quba = question_engine::load_questions_usage_by_activity((int)$attempt->qubaid);
        $qa = $quba->get_question_attempt(1);
        $response = $qa->get_question()->get_correct_response();
        $this->assertNotEmpty($response);

        $post = [$qa->get_control_field_name("sequencecheck") => $qa->get_sequence_check_count()];
        foreach ($response as $name => $answer) {
            $post[$qa->get_qt_field_name($name)] = $answer;
        }
        attempts::submit($mcp, $attempt, $post, true);
        $stored = $DB->get_record("mcp_attempts", ["id" => $attempt->id], "*", MUST_EXIST);
        $this->assertSame("finished", $stored->status);
        $this->assertEqualsWithDelta(10.0, (float)$stored->score, 0.001);
        $this->assertNotEmpty($stored->qubaid);
        $this->assertTrue($DB->record_exists("question_usages", ["id" => $stored->qubaid]));
    }
}
