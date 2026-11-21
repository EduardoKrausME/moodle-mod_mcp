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
 * Regression coverage for the separate content management screen.
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

/**
 * HTML authoring must not be embedded in the activity settings form.
 *
 * @covers \\mod_mcp_mod_form
 */
final class manage_content_test extends advanced_testcase {
    /**
     * Existing modules link to manage.php by course-module ID rather than instance ID.
     */
    public function test_edit_settings_links_to_manager_without_html_field(): void {
        global $COURSE, $PAGE;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $COURSE = $course;
        $PAGE->set_course($course);
        $module = $this->getDataGenerator()->create_module('mcp', ['course' => $course->id]);
        $cm = get_coursemodule_from_id('mcp', $module->cmid, 0, false, MUST_EXIST);
        [$cm, , , $data, $section] = get_moduleinfo_data($cm, $course);


        $form = new \mod_mcp_mod_form($data, (int)$section->section, $cm, $course);
        $form->set_data($data);
        $html = $form->render();

        $url = (new \moodle_url('/mod/mcp/manage.php', ['id' => $cm->id]))->out(false);
        $this->assertStringContainsString($url, $html);
        $this->assertStringContainsString(get_string('editcontent', 'mcp'), $html);
        $this->assertStringNotContainsString('name="content"', $html);
    }

    /**
     * HTML with script and closing textarea tags must remain escaped within the editor.
     */
    public function test_manager_template_escapes_html_document_in_textarea(): void {
        global $OUTPUT;

        $this->resetAfterTest();
        $this->setAdminUser();
        $document = '<html><body><textarea>Example</textarea><script>let value = 1;</script></body></html>';
        $rendered = $OUTPUT->render_from_template('mod_mcp/manage', [
            'draftcontent' => $document,
            'actionurl' => (new \moodle_url('/mod/mcp/manage.php'))->out(false),
            'previewurl' => (new \moodle_url('/mod/mcp/view.php', ['id' => 42, 'preview' => 1]))->out(false),
            'cmid' => 42,
            'revision' => 23,
            'sesskey' => sesskey(),
            'canpublish' => true,
            'prompt' => 'Build activity',
            'promptpage' => 'Build page',
        ]);

        $this->assertStringContainsString('name="action" value="save"', $rendered);
        $this->assertStringContainsString('name="revision" value="23"', $rendered);
        $this->assertStringContainsString('name="content"', $rendered);
        $this->assertStringContainsString('&lt;/textarea&gt;', $rendered);
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
        $this->assertStringNotContainsString($document, $rendered);
    }
}
