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
 * Required Moodle activity callbacks, delegated to services.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\grade\service;
use mod_mcp\output\activity;

/**
 * Feature support required by Moodle.
 * @param $feature
 * @return int|string|true|null
 */
function mcp_supports($feature) {
    // Feature purpose is unavailable in older Moodle versions.
    if (defined("FEATURE_MOD_PURPOSE") && $feature === FEATURE_MOD_PURPOSE) {
        return MOD_PURPOSE_INTERACTIVECONTENT;
    }

    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_OTHER;
        default:
            return null;
    }
}

/**
 * Record a real learner view through Moodle's native completion API.
 * Called by local_kopere_mobile_mod_make_view after its capability check.
 *
 * @param stdClass $mcp Activity instance.
 * @param stdClass $course Course.
 * @param stdClass $cm Course module.
 * @param context_module $context Module context.
 */
function mcp_view($mcp, $course, $cm, $context) {
    require_login($course, true, $cm);
    require_capability("mod/mcp:view", $context);
    if (empty($mcp->publishedrevision)) {
        throw new moodle_exception("activitynotpublished", "mcp");
    }
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Insert a Moodle activity instance.
 * @param $data
 * @param null $mform
 * @return bool|int
 * @throws dml_exception
 * @throws moodle_exception
 */
function mcp_add_instance($data, $mform = null) {
    global $DB, $USER;
    $now = time();
    $record = (object)[
        "course" => (int)$data->course,
        "name" => $data->name,
        "intro" => $data->intro ?? "",
        "introformat" => $data->introformat ?? FORMAT_HTML,
        "grade" => max(0, (float)($data->grade ?? 0)),
        "activitytype" => null,
        "informationjson" => null,
        "completioninteractive" => empty($data->completioninteractive) ? 0 : 1,
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

/**
 * Update activity metadata and draft without publishing.
 * @param $data
 * @param null $mform
 * @return true
 * @throws dml_exception
 * @throws moodle_exception
 */
function mcp_update_instance($data, $mform = null) {
    global $DB, $USER;
    $mcp = $DB->get_record("mcp", ["id" => $data->instance], "*", MUST_EXIST);
    $mcp->name = $data->name;
    $mcp->intro = $data->intro ?? "";
    $mcp->introformat = $data->introformat ?? FORMAT_HTML;
    if ((float)$mcp->grade !== max(0, (float)($data->grade ?? $mcp->grade)) &&
            $DB->record_exists("mcp_attempts", ["mcpid" => $mcp->id])) {
        throw new moodle_exception("invaliddata");
    }
    $mcp->grade = max(0, (float)($data->grade ?? $mcp->grade));
    $mcp->completioninteractive = empty($data->completioninteractive) ? 0 : 1;
    $mcp->timemodified = time();
    $DB->update_record("mcp", $mcp);
    if (isset($data->content) && trim((string)$data->content) !== "") {
        \mod_mcp\content\service::save((int)$mcp->id, (string)$data->content, (int)$USER->id);
    }
    mcp_grade_item_update($mcp);
    return true;
}

/**
 * Delete activity records, not shared native question bank questions.
 * @param $id
 * @return bool
 * @throws dml_exception
 */
function mcp_delete_instance($id) {
    global $CFG, $DB;
    $record = $DB->get_record("mcp", ["id" => $id]);
    if (!$record) {
        return false;
    }
    $DB->delete_records("mcp_attempts", ["mcpid" => $id]);
    $DB->delete_records("mcp_state", ["mcpid" => $id]);
    $DB->delete_records("mcp_tracking", ["mcpid" => $id]);
    $DB->delete_records("mcp_revisions", ["mcpid" => $id]);
    $DB->delete_records("mcp", ["id" => $id]);
    mcp_grade_item_delete($record);
    return true;
}

/**
 * Delete the grade item through Moodle gradebook, never directly through SQL.
 * @param $mcp
 * @return int
 */
function mcp_grade_item_delete($mcp) {
    global $CFG;
    require_once($CFG->libdir . "/gradelib.php");
    return grade_update("mod/mcp", (int)$mcp->course, "mod", "mcp",
        (int)$mcp->id, 0, null, ["deleted" => 1]);
}

/**
 * Update a gradebook item, not individual grades from the browser.
 */
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

/**
 * Recalculate grades only from finished server-side attempts.
 * @param $mcp
 * @param int $userid
 * @param bool $nullifnone
 */
function mcp_update_grades($mcp, $userid = 0, $nullifnone = true) {
    service::update($mcp, (int)$userid);
}

/**
 * File access always requires the activity's native Moodle context.
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 * @return false|void
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 * @throws required_capability_exception
 */
function mcp_pluginfile($course, $cm, $context, $filearea, $args,
        $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel !== CONTEXT_MODULE || !in_array($filearea, ["assets", "content"], true)) {
        return false;
    }
    require_login($course, true, $cm);
    require_capability("mod/mcp:view", $context);

    if ($filearea === "content") {
        // This virtual file does not exist in file storage: the immutable revision is in the database.
        if (count($args) !== 2 || !ctype_digit((string)$args[0]) ||
                (int)$args[0] <= 0 || $args[1] !== "index.html") {
            return false;
        }
        $revisionid = (int)$args[0];
        $mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
        $canmanage = has_capability("mod/mcp:manage", $context);
        if (!\mod_mcp\content\service::can_access_revision($mcp, $revisionid, $canmanage)) {
            return false;
        }
        $revision = \mod_mcp\content\service::get((int)$mcp->id, $revisionid);
        if (!$revision) {
            return false;
        }

        // A URL can be opened outside the iframe. Enforce an opaque origin even on direct navigation.
        // Never combine allow-scripts with allow-same-origin for authored HTML.
        header("Content-Security-Policy: sandbox allow-scripts");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: no-referrer");
        $html = activity::inject_sdk($revision->content);
        send_file($html, "index.html", 0, 0, true, false, "text/html; charset=utf-8");
        return;
    }

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

/**
 * Moodle course contents support.
 * @param $coursemodule
 * @return cached_cm_info|false
 * @throws dml_exception
 */
function mcp_get_coursemodule_info($coursemodule) {
    global $DB;
    $record = $DB->get_record("mcp", ["id" => $coursemodule->instance], "id, name, intro, introformat, completioninteractive");
    if (!$record) {
        return false;
    }
    $info = new cached_cm_info();
    $info->name = $record->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro("mcp", $record, $coursemodule->id, false);
    }
    $info->customdata = [
        "customcompletionrules" => [
            "completioninteractive" => (int)$record->completioninteractive,
        ],
    ];
    return $info;
}

/**
 * Add the management page to Moodle's activity navigation.
 *
 * @param settings_navigation $settingsnav Settings navigation for this page.
 * @param navigation_node $mcpnode Navigation node for this activity.
 */
function mcp_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $mcpnode) {
    $cm = $settingsnav->get_page()->cm;
    if (!$cm || $cm->modname !== "mcp" || !has_capability("mod/mcp:manage", $cm->context)) {
        return;
    }

    // Keep the management tab next to the native activity settings tab.
    $keys = $mcpnode->get_children_key_list();
    $editindex = array_search("modedit", $keys, true);
    $beforekey = $editindex === false ? ($keys[0] ?? null) : ($keys[$editindex + 1] ?? null);

    $node = navigation_node::create(
        get_string("manageactivity", "mcp"),
        new moodle_url("/mod/mcp/manage.php", ["id" => $cm->id]),
        navigation_node::TYPE_SETTING,
        null,
        "mod_mcp_manage"
    );
    $mcpnode->add_node($node, $beforekey);
}
