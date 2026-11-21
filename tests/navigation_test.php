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
 * Tests for native activity settings navigation.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/mcp/lib.php');

/**
 * Check that the management page is a native navigation item for authorised users only.
 *
 * @coversNothing
 */
final class navigation_test extends advanced_testcase {
    /**
     * Managers see the tab with the course module ID; students do not.
     */
    public function test_management_tab_requires_manage_capability(): void {
        global $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('mcp', ['course' => $course->id]);
        $cmrecord = get_coursemodule_from_instance('mcp', $module->id, $course->id, false, MUST_EXIST);
        $cm = get_fast_modinfo($course)->get_cm($cmrecord->id);

        $PAGE->set_url(new \moodle_url('/mod/mcp/view.php', ['id' => $cm->id]));
        $PAGE->set_course($course);
        $PAGE->set_cm($cm, $course);

        $settingsnav = $this->getMockBuilder(\settings_navigation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get_page'])
            ->getMock();
        $settingsnav->method('get_page')->willReturn($PAGE);

        $managernode = \navigation_node::create('Activity', null, \navigation_node::TYPE_SETTING);
        $managernode->add('Edit settings', new \moodle_url('/course/modedit.php'),
            \navigation_node::TYPE_SETTING, null, 'modedit');
        \mcp_extend_settings_navigation($settingsnav, $managernode);

        $link = $managernode->get('mod_mcp_manage', \navigation_node::TYPE_SETTING);
        $this->assertInstanceOf(\navigation_node::class, $link);
        $this->assertSame(
            (new \moodle_url('/mod/mcp/manage.php', ['id' => $cm->id]))->out(false),
            $link->action->out(false)
        );
        $this->assertSame(['modedit', 'mod_mcp_manage'], $managernode->get_children_key_list());

        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $studentnode = \navigation_node::create('Activity', null, \navigation_node::TYPE_SETTING);
        \mcp_extend_settings_navigation($settingsnav, $studentnode);
        $this->assertFalse($studentnode->get('mod_mcp_manage', \navigation_node::TYPE_SETTING));
    }
}
