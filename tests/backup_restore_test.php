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
 * Activity backup and duplicate regression tests.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use backup;
use backup_controller;
use context_module;
use mod_mcp\content\service;
use mod_mcp\content\metadata;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Ensure activity duplication preserves HTML and its files.
 *
 * @covers \mod_mcp\content\service
 */
final class backup_restore_test extends advanced_testcase {
    /**
     * The Moodle backup API must accept itemid 0 assets and duplicate them.
     */
    public function test_duplicate_preserves_revisions_and_assets(): void {
        global $DB, $USER;

        $this->resetAfterTest(true);
        set_config("materialtheme_default", json_encode([
            "name" => "Test theme",
            "style" => "Use spacious cards and readable typography.",
        ]), "mod_mcp");
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('mcp', [
            'course' => $course->id,
            'name' => 'Original interactive document',

        ]);
        $sourcecontext = context_module::instance($module->cmid);
        metadata::save((int)$module->id, "quiz", [
            "selection" => "random", "count" => 1, "shuffle" => true,
            "questions" => [["id" => "q1", "text" => "2+2?", "type" => "single",
                "options" => [["id" => "a", "text" => "3"], ["id" => "b", "text" => "4"]],
                "answer" => "b", "points" => 1]],
        ]);

        // Preserve the published version even when a newer unpublished draft exists.
        $publishedhtml = '<h2>Published content for backup</h2>';
        $publishedrevision = service::save((int)$module->id, $publishedhtml, (int)$USER->id, 0);
        $DB->set_field('mcp', 'publishedrevision', $publishedrevision, ['id' => $module->id]);
        $drafthtml = '<h2>Unpublished draft for backup</h2>';
        $draftrevision = service::save((int)$module->id, $drafthtml, (int)$USER->id, $publishedrevision);
        // Simulate an installation that still contains a legacy unpublished revision.
        $DB->set_field('mcp', 'publishedrevision', $publishedrevision, ['id' => $module->id]);

        $bytes = 'MCP file area regression test';
        $fs = get_file_storage();
        $fs->create_file_from_string([
            'contextid' => $sourcecontext->id,
            'component' => 'mod_mcp',
            'filearea' => 'assets',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'illustration.png',
            'userid' => $USER->id,
        ], $bytes);

        // Match the backup settings used by Moodle's duplicate activity action.
        $backup = new backup_controller(backup::TYPE_1ACTIVITY, $module->cmid,
            backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);
        $backupid = $backup->get_backupid();
        $backup->execute_plan();
        $backup->destroy();

        $restore = new restore_controller($backupid, $course->id,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
        $this->assertTrue($restore->execute_precheck());
        $restore->execute_plan();
        $restore->destroy();

        $instances = $DB->get_records('mcp', ['course' => $course->id], 'id ASC');
        $this->assertCount(2, $instances);
        $copy = end($instances);
        $this->assertNotEquals($module->id, $copy->id);
        $this->assertNotEquals($publishedrevision, $copy->publishedrevision);
        $this->assertNotEquals($draftrevision, $copy->draftrevision);
        $this->assertSame("quiz", $copy->activitytype);
        $this->assertSame("random", json_decode($copy->informationjson, true)["selection"]);
        $this->assertCount(2, $DB->get_records('mcp_revisions', ['mcpid' => $copy->id]));
        $this->assertNotSame((int)$copy->draftrevision, (int)$copy->publishedrevision);
        $this->assertSame($publishedhtml,
            service::get((int)$copy->id, (int)$copy->publishedrevision)->content);
        $this->assertSame($drafthtml,
            service::get((int)$copy->id, (int)$copy->draftrevision)->content);

        $copycm = get_coursemodule_from_instance('mcp', $copy->id, $course->id, false, MUST_EXIST);
        $copycontext = context_module::instance($copycm->id);
        $copiedfile = $fs->get_file($copycontext->id, 'mod_mcp', 'assets', 0, '/', 'illustration.png');
        $this->assertNotFalse($copiedfile);
        $this->assertSame($bytes, $copiedfile->get_content());
    }
}
