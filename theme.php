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
 * Material theme configuration screen for teachers and site administrators.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mcp\authoring\material_theme;

require_once(__DIR__ . "/../../config.php");
$courseid = required_param("courseid", PARAM_INT);
$scope = optional_param("scope", "course", PARAM_ALPHA);
if (!in_array($scope, ["site", "course"], true)) {
    throw new moodle_exception("invalidparameter");
}
$course = get_course($courseid);
require_login($course);
$context = $scope === "site" ? context_system::instance() : context_course::instance($courseid);
$capability = $scope === "site" ? "moodle/site:config" : "moodle/course:manageactivities";
require_capability($capability, $context);

if (data_submitted()) {
    require_sesskey();
    material_theme::save($scope, $courseid, required_param("name", PARAM_TEXT),
        required_param("style", PARAM_TEXT));
    redirect(new moodle_url("/mod/mcp/theme.php", ["courseid" => $courseid, "scope" => $scope]),
        get_string("materialthemesaved", "mcp"));
}
$PAGE->set_url(new moodle_url("/mod/mcp/theme.php", ["courseid" => $courseid, "scope" => $scope]));
$PAGE->set_title(get_string("materialthemetitle", "mcp"));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$current = $scope === "site" ? material_theme::site_default() : material_theme::course_override($courseid);

echo $OUTPUT->header();
echo html_writer::tag("h2", get_string("materialthemetitle", "mcp"));
echo html_writer::tag("p", get_string("materialthemehelp", "mcp"));
echo html_writer::tag("p", get_string("materialthemescope" . $scope, "mcp"));
if ($scope === "course" && has_capability("moodle/site:config", context_system::instance())) {
    echo html_writer::link(new moodle_url("/mod/mcp/theme.php",
        ["courseid" => $courseid, "scope" => "site"]),
        get_string("materialthemescope_site_link", "mcp"),
        ["class" => "btn btn-outline-secondary mb-3"]);
}
echo html_writer::start_tag("form", ["method" => "post", "action" => $PAGE->url->out(false)]);
echo html_writer::empty_tag("input", ["type" => "hidden", "name" => "sesskey", "value" => sesskey()]);
echo html_writer::empty_tag("input", ["type" => "hidden", "name" => "courseid", "value" => $courseid]);
echo html_writer::empty_tag("input", ["type" => "hidden", "name" => "scope", "value" => $scope]);
echo html_writer::start_div("mb-3");
echo html_writer::tag("label", get_string("materialthemename", "mcp"),
    ["for" => "mod-mcp-material-name", "class" => "form-label"]);
echo html_writer::empty_tag("input", ["type" => "text", "class" => "form-control",
    "id" => "mod-mcp-material-name", "name" => "name", "required" => "required",
    "maxlength" => "160", "value" => $current["name"] ?? ""]);
echo html_writer::end_div();
echo html_writer::start_div("mb-3");
echo html_writer::tag("label", get_string("materialthemestyle", "mcp"),
    ["for" => "mod-mcp-material-style", "class" => "form-label"]);
echo html_writer::tag("textarea", s($current["style"] ?? ""), [
    "class" => "form-control", "id" => "mod-mcp-material-style", "name" => "style",
    "rows" => "8", "required" => "required", "maxlength" => "3000",
]);
echo html_writer::end_div();
echo html_writer::tag("button", get_string("savechanges", "moodle"),
    ["class" => "btn btn-primary", "type" => "submit"]);
echo " ";
echo html_writer::link(new moodle_url("/course/view.php", ["id" => $courseid]),
    get_string("cancel", "moodle"), ["class" => "btn btn-outline-secondary"]);
echo html_writer::end_tag("form");
echo $OUTPUT->footer();
