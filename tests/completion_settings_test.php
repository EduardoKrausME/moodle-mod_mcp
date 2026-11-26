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
 * Regression tests for native activity completion settings.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;

defined("MOODLE_INTERNAL") || die();

defined("MOODLE_INTERNAL") || die();

global $CFG;
require_once($CFG->dirroot . "/mod/mcp/lib.php");

/**
 * Verify that activity callbacks respect native completion settings.
 *
 * @covers ::mcp_update_instance
 * @covers ::mcp_get_coursemodule_info
 */
final class completion_settings_test extends advanced_testcase {
    /**
     * Omitted or locked custom rules must survive metadata updates.
     */
    public function test_partial_and_locked_updates_preserve_rule(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 1]);
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id,
            "grade" => 0,
            "completion" => COMPLETION_TRACKING_AUTOMATIC,
            "completionview" => 1,
            "completioninteractive" => 1,
        ]);
        $cm = get_coursemodule_from_instance("mcp", $module->id, $course->id, false, MUST_EXIST);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, "student");
        $completionid = $DB->insert_record("course_modules_completion", (object)[
            "coursemoduleid" => $cm->id,
            "userid" => $student->id,
            "completionstate" => COMPLETION_COMPLETE,
            "viewed" => 1,
            "timemodified" => time(),
        ]);
        $original = $DB->get_record("course_modules_completion", ["id" => $completionid], "*", MUST_EXIST);
        $metadata = ["instance" => $module->id, "name" => $module->name];
        foreach ([[], ["completionunlocked" => 1], ["completionunlocked" => 0, "completioninteractive" => 0],
                ["completioninteractive" => 0]] as $options) {
            mcp_update_instance((object)($metadata + $options));
            $this->assertEquals(1, $DB->get_field("mcp", "completioninteractive", ["id" => $module->id]));
            $this->assertEquals($original,
                $DB->get_record("course_modules_completion", ["id" => $completionid], "*", MUST_EXIST));
        }
        $after = get_coursemodule_from_id("mcp", $cm->id, 0, false, MUST_EXIST);
        $this->assertEquals(COMPLETION_TRACKING_AUTOMATIC, $after->completion);
        $this->assertEquals(1, $after->completionview);
    }

    /**
     * An explicit unlocked update can disable and re-enable the custom rule.
     */
    public function test_unlocked_updates_can_change_rule(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 1]);
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id,
            "grade" => 0,
            "completion" => COMPLETION_TRACKING_AUTOMATIC,
            "completionview" => 1,
            "completioninteractive" => 1,
        ]);
        foreach ([0, 1] as $rule) {
            mcp_update_instance((object)[
                "instance" => $module->id,
                "name" => $module->name,
                "completionunlocked" => 1,
                "completioninteractive" => $rule,
            ]);
            $this->assertEquals($rule, $DB->get_field("mcp", "completioninteractive", ["id" => $module->id]));
        }
    }

    /**
     * Only automatic completion exposes module-specific rules.
     */
    public function test_custom_rules_are_exposed_only_for_automatic_completion(): void {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = 1;
        $course = $this->getDataGenerator()->create_course(["enablecompletion" => 1]);
        $module = $this->getDataGenerator()->create_module("mcp", [
            "course" => $course->id,
            "grade" => 0,
            "completion" => COMPLETION_TRACKING_AUTOMATIC,
            "completionview" => 1,
            "completioninteractive" => 1,
        ]);
        $cm = get_coursemodule_from_instance("mcp", $module->id, $course->id, false, MUST_EXIST);
        foreach ([COMPLETION_TRACKING_NONE, COMPLETION_TRACKING_MANUAL, COMPLETION_TRACKING_AUTOMATIC] as $mode) {
            $cm->completion = $mode;
            $info = mcp_get_coursemodule_info($cm);
            $customdata = (array)$info->customdata;
            if ($mode === COMPLETION_TRACKING_AUTOMATIC) {
                $this->assertSame(["completioninteractive" => 1], $customdata["customcompletionrules"]);
            } else {
                $this->assertArrayNotHasKey("customcompletionrules", $customdata);
            }
        }
    }
}
