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
 * Native grading settings tests for MCP activities.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/mcp/mod_form.php');
require_once($CFG->dirroot . '/mod/mcp/lib.php');

/**
 * Grade configuration must use the Moodle grade picker rather than a second text field.
 *
 * @covers \\mod_mcp_mod_form
 */
final class grade_form_test extends advanced_testcase {
    /**
     * Only one maximum points field is rendered, within the native grade component.
     */
    public function test_form_uses_native_modgrade_without_separate_grade_field(): void {
        global $COURSE, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $COURSE = $course;
        $PAGE->set_course($course);
        $module = $this->getDataGenerator()->create_module('mcp', [
            'course' => $course->id,
            'grade' => 25,
        ]);

        $cm = get_coursemodule_from_instance('mcp', $module->id, $course->id, false, MUST_EXIST);
        [$cm, , , $data, $section] = get_moduleinfo_data($cm, $course);

        $form = new \mod_mcp_mod_form($data, (int)$section->section, $cm, $course);
        $form->set_data($data);
        $html = $form->render();

        $this->assertStringContainsString('modstandardgrade', $html);
        $this->assertSame(1, substr_count($html, 'name="grade[modgrade_type]"'));
        $this->assertSame(1, substr_count($html, 'name="grade[modgrade_point]"'));
        $this->assertSame(0, preg_match('/<input\\b[^>]*\\sname="grade"/i', $html));
    }

    /**
     * Numeric grade values remain supported in the activity and its native gradebook item.
     */
    public function test_point_grade_remains_consistent_with_gradebook(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('mcp', [
            'course' => $course->id,
            'grade' => 35,
        ]);

        $itemconditions = [
            'itemtype' => 'mod',
            'itemmodule' => 'mcp',
            'iteminstance' => $module->id,
            'itemnumber' => 0,
            'courseid' => $course->id,
        ];

        $this->assertEquals(35, $DB->get_field('mcp', 'grade', ['id' => $module->id]));
        $this->assertEquals(35, $DB->get_field('grade_items', 'grademax', $itemconditions));

        mcp_update_instance((object)[
            'instance' => $module->id,
            'name' => $module->name,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'grade' => 70,
            'completioninteractive' => 0,
        ]);

        $this->assertEquals(70, $DB->get_field('mcp', 'grade', ['id' => $module->id]));
        $this->assertEquals(70, $DB->get_field('grade_items', 'grademax', $itemconditions));
    }
}
