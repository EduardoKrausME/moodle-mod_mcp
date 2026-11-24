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
 * Manage reusable material templates in the current course.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\output\material_templates;

require_once(__DIR__ . "/../../config.php");

$courseid = required_param("courseid", PARAM_INT);
$course = get_course($courseid);
require_login($course);
$context = context_course::instance($courseid);
require_capability("moodle/course:manageactivities", $context);

$PAGE->set_url(new moodle_url("/mod/mcp/templates.php", ["courseid" => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string("materialtemplatesmanage", "mcp"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_pagelayout("incourse");

$data = material_templates::page($courseid, has_capability("moodle/site:config", context_system::instance()));
echo $OUTPUT->header();
echo $OUTPUT->render_from_template("mod_mcp/material_templates", $data);
echo $OUTPUT->footer();
