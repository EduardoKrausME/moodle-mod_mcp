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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * List visible MCP modules in a course.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

$id = required_param("id", PARAM_INT);
$course = get_course($id);
require_login($course);
$PAGE->set_url(new moodle_url("/mod/mcp/index.php", ["id" => $id]));
$PAGE->set_context(context_course::instance($id));
$PAGE->set_pagelayout("incourse");
$PAGE->set_title(get_string("modulenameplural", "mcp"));
$PAGE->set_heading(format_string($course->fullname));
$items = [];
foreach (get_fast_modinfo($course)->get_instances_of("mcp") as $cm) {
    if ($cm->uservisible) {
        $items[] = [
            "name" => format_string($cm->name),
            "url" => (new moodle_url("/mod/mcp/view.php", ["id" => $cm->id]))->out(false),
        ];
    }
}
echo $OUTPUT->header();
echo $OUTPUT->render_from_template("mod_mcp/index", ["activities" => $items]);
echo $OUTPUT->footer();
