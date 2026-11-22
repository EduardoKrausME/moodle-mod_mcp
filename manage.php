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
 * Authenticated publishing interface.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\content\service;
use mod_mcp\output\activity;

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$cm = get_coursemodule_from_id("mcp", $id, 0, false, MUST_EXIST);
$mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability("mod/mcp:manage", $context);
$action = optional_param("action", "", PARAM_ALPHA);
if (data_submitted() && $action === "save") {
    require_sesskey();
    $expectedrevision = required_param("revision", PARAM_INT);
    $content = required_param("content", PARAM_RAW);
    service::save((int)$mcp->id, $content, (int)$USER->id, $expectedrevision);
    redirect(new moodle_url("/mod/mcp/manage.php", ["id" => $cm->id]),
        get_string("draftsaved", "mcp"));
} else if (data_submitted() && $action === "publish") {
    require_sesskey();
    $revision = required_param("revision", PARAM_INT);
    service::publish((int)$mcp->id, $revision);
    redirect(new moodle_url("/mod/mcp/view.php", ["id" => $cm->id]),
        get_string("published", "mcp"));
}
$PAGE->set_url(new moodle_url("/mod/mcp/manage.php", ["id" => $cm->id]));
$PAGE->set_title(get_string("manageactivity", "mcp"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$draft = service::get((int)$mcp->id, (int)$mcp->draftrevision);
$data = [
    "draftcontent" => $draft ? $draft->content : "",
    "prompt" => activity::prompt($mcp, $cm),
    "promptpage" => activity::prompt($mcp, $cm, "page"),
    "promptoptions" => activity::prompt_options($mcp, $cm),
    "previewurl" => (new moodle_url("/mod/mcp/view.php", ["id" => $cm->id, "preview" => 1]))->out(false),
    "actionurl" => (new moodle_url("/mod/mcp/manage.php"))->out(false),
    "canpublish" => !empty($mcp->draftrevision),
    "revision" => (int)$mcp->draftrevision,
    "sesskey" => sesskey(),
    "cmid" => (int)$cm->id,
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template("mod_mcp/manage", $data);
echo $OUTPUT->footer();
