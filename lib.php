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
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Required Moodle activity callbacks, delegated to services.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

/** Feature support required by Moodle. */
function mcp_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        default:
            return null;
    }
}

/** Insert a Moodle activity instance. */
function mcp_add_instance($data, $mform = null) {
    global $DB, $USER;
    $now = time();
    $record = (object)[
        "course" => (int)$data->course,
        "name" => $data->name,
        "intro" => $data->intro ?? "",
        "introformat" => $data->introformat ?? FORMAT_HTML,
        "grade" => max(0, (float)($data->grade ?? 10)),
        "draftrevision" => 0, "publishedrevision" => 0,
        "timecreated" => $now, "timemodified" => $now,
    ];
    $id = $DB->insert_record("mcp", $record);
    if (trim((string)($data->content ?? "")) !== "") {
        \mod_mcp\content\service::save((int)$id, (string)$data->content, (int)$USER->id);
    }
    $record->id = $id;
    mcp_grade_item_update($record);
    return $id;
}

/** Update activity metadata and draft without publishing. */
function mcp_update_instance($data, $mform = null) {
    global $DB, $USER;
    $mcp = $DB->get_record("mcp", ["id" => $data->instance], "*", MUST_EXIST);
    $mcp->name = $data->name;
    $mcp->intro = $data->intro ?? "";
    $mcp->introformat = $data->introformat ?? FORMAT_HTML;
    $mcp->grade = max(0, (float)($data->grade ?? 10));
    $mcp->timemodified = time();
    $DB->update_record("mcp", $mcp);
    if (isset($data->content) && trim((string)$data->content) !== "") {
        \mod_mcp\content\service::save((int)$mcp->id, (string)$data->content, (int)$USER->id);
    }
    mcp_grade_item_update($mcp);
    return true;
}

/** Delete activity records, not shared native question bank questions. */
function mcp_delete_instance($id) {
    global $DB;
    $record = $DB->get_record("mcp", ["id" => $id]);
    if (!$record) {
        return false;
    }
    foreach ($DB->get_records("mcp_attempts", ["mcpid" => $id]) as $attempt) {
        if ($attempt->qubaid) {
            question_engine::delete_questions_usage_by_activity((int)$attempt->qubaid);
        }
    }
    $DB->delete_records("mcp_attempts", ["mcpid" => $id]);
    $DB->delete_records("mcp_slots", ["mcpid" => $id]);
    $DB->delete_records("mcp_revisions", ["mcpid" => $id]);
    $DB->delete_records("mcp", ["id" => $id]);
    $DB->delete_records("grade_items", ["itemmodule" => "mcp", "iteminstance" => $id]);
    return true;
}

/** Update a gradebook item, not individual grades from the browser. */
function mcp_grade_item_update($mcp, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . "/gradelib.php");
    $params = ["itemname" => $mcp->name, "gradetype" => GRADE_TYPE_VALUE,
        "grademax" => (float)$mcp->grade, "grademin" => 0];
    if ((float)$mcp->grade <= 0) {
        $params["gradetype"] = GRADE_TYPE_NONE;
    }
    return grade_update("mod/mcp", (int)$mcp->course, "mod", "mcp",
        (int)$mcp->id, 0, $grades, $params);
}

/** Recalculate grades only from finished server-side attempts. */
function mcp_update_grades($mcp, $userid = 0, $nullifnone = true) {
    \mod_mcp\grade\service::update($mcp, (int)$userid);
}

/** File access always requires the activity's native Moodle context. */
function mcp_pluginfile($course, $cm, $context, $filearea, $args,
        $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_MODULE || $filearea !== "assets") {
        return false;
    }
    require_login($course, true, $cm);
    require_capability("mod/mcp:view", $context);
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? "/" . implode("/", $args) . "/" : "/";
    $file = get_file_storage()->get_file($context->id, "mod_mcp", "assets",
        $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/** Moodle course contents support. */
function mcp_get_coursemodule_info($coursemodule) {
    global $DB;
    $record = $DB->get_record("mcp", ["id" => $coursemodule->instance], "id, name, intro, introformat");
    if (!$record) {
        return false;
    }
    $info = new cached_cm_info();
    $info->name = $record->name;
    return $info;
}

