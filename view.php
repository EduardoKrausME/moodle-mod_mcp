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
 * Render the Moodle MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\output\activity;
use mod_mcp\question\attempts;
use mod_mcp\question\slots;

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$preview = optional_param("preview", 0, PARAM_BOOL);
$cm = get_coursemodule_from_id("mcp", $id, 0, false, MUST_EXIST);
$mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
$canmanage = has_capability("mod/mcp:manage", $context);
if (!$mcp->publishedrevision && !$canmanage) {
    throw new moodle_exception("activitynotpublished", "mcp");
}
require_capability("mod/mcp:view", $context);
if ($preview && !$canmanage) {
    throw new required_capability_exception($context, "mod/mcp:manage", "nopermissions", "");
}
$PAGE->set_url(new moodle_url("/mod/mcp/view.php", ["id" => $id, "preview" => $preview]));
$PAGE->set_title(format_string($mcp->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course);
$PAGE->set_pagelayout("incourse");
$completion = new completion_info($course);
if (!$preview && !empty($mcp->publishedrevision)) {
    $completion->set_module_viewed($cm);
}

$data = activity::page($mcp, $cm, $canmanage, $preview);
$assessment = ["hasassessment" => false];
if (!$preview && $mcp->publishedrevision &&
        has_capability("mod/mcp:submit", $context) &&
        slots::active((int)$mcp->id)) {
    $attempt = attempts::own((int)$mcp->id, (int)$USER->id);
    $assessment = [
        "hasassessment" => true,
        "canstart" => !$attempt,
        "inprogress" => $attempt && $attempt->status === "inprogress",
        "finished" => $attempt && $attempt->status === "finished",
        "questions" => $attempt && $attempt->status === "inprogress" ?
            attempts::render($attempt) : [],
        "hasgrade" => $attempt && $attempt->score !== null,
        "gradelabel" => $attempt && $attempt->score !== null ?
            get_string("yourgrade", "mcp", format_float((float)$attempt->score, 2)) : "",
        "actionurl" => (new moodle_url("/mod/mcp/attempt.php"))->out(false),
        "cmid" => $cm->id,
        "sesskey" => sesskey(),
    ];
}
echo $OUTPUT->header();
if (!$data["hascontent"] && $canmanage) {
    echo $OUTPUT->render_from_template("mod_mcp/empty", $data);
} else {
    echo $OUTPUT->render_from_template("mod_mcp/activity", $data);
}
if ($assessment["hasassessment"]) {
    echo $OUTPUT->render_from_template("mod_mcp/assessment", $assessment);
}
echo $OUTPUT->footer();
