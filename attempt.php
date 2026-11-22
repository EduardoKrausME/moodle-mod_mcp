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
 * Moodle authenticated assessment actions.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\question\attempts;

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$action = required_param("action", PARAM_ALPHA);
$cm = get_coursemodule_from_id("mcp", $id, 0, false, MUST_EXIST);
$mcp = $DB->get_record("mcp", ["id" => $cm->instance], "*", MUST_EXIST);
$course = get_course($cm->course);
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability("mod/mcp:view", $context);
require_capability("mod/mcp:submit", $context);
if (empty($mcp->publishedrevision)) {
    throw new moodle_exception("activitynotpublished", "mcp");
}
if (($_SERVER["REQUEST_METHOD"] ?? "") !== "POST") {
    throw new moodle_exception("invalidrequest");
}
require_sesskey();
$attempt = attempts::own((int)$mcp->id, (int)$USER->id);
if ($action === "start") {
    if ($attempt) {
        throw new moodle_exception("invaliddata");
    }
    attempts::start($mcp, $cm, (int)$USER->id);
} else if ($action === "save" || $action === "finish") {
    if (!$attempt) {
        throw new moodle_exception("invaliddata");
    }
    // Never accept a client score or correctness declaration.
    $responses = $_POST["responses"] ?? [];
    if (!is_array($responses)) {
        throw new moodle_exception("invaliddata");
    }
    attempts::submit($mcp, $attempt, $responses, $action === "finish");
} else {
    throw new moodle_exception("invaliddata");
}
redirect(new moodle_url("/mod/mcp/view.php", ["id" => $id]));
