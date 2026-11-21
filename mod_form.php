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
 * Moodle module configuration form for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/course/moodleform_mod.php");

/**
 * Edit module settings; HTML drafts are managed on the dedicated page.
 */
class mod_mcp_mod_form extends moodleform_mod {
    /**
     * Build the activity settings and provide the content management link when editing.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement("text", "name", get_string("name"), ["size" => 64]);
        $mform->setType("name", PARAM_TEXT);
        $mform->addRule("name", null, "required", null, "client");
        // The HTML editor belongs to manage.php, not the Moodle settings form.
        if (!empty($this->current->instance) && $this->_cm && !empty($this->_cm->id) &&
                has_capability("mod/mcp:manage", $this->context)) {
            $url = new moodle_url("/mod/mcp/manage.php", ["id" => $this->_cm->id]);
            $link = html_writer::link($url, get_string("editcontent", "mcp"),
                ["class" => "btn btn-secondary"]);
            $mform->addElement("static", "managecontent", "", $link);
        }
        $this->standard_intro_elements();
        // Use Moodle's native grade group for type, maximum points, category and grade to pass.
        $this->standard_grading_coursemodule_elements();
        // New activities are ungraded unless the teacher explicitly enables points.
        $mform->setDefault("grade", 0);
        $this->standard_coursemodule_elements();
        // Newly created activities must remain hidden until explicit publication.
        if (empty($this->current->instance)) {
            $mform->setDefault("visible", 0);
        }
        $this->add_action_buttons();
    }

    /**
     * The assessment engine uses numeric points, not Moodle grade scales.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (isset($data["grade"]) && (float)$data["grade"] < 0) {
            $errors["grade"] = get_string("gradescalenotsupported", "mcp");
        }
        return $errors;
    }

    /**
     * Enable the custom rule only when configured by the teacher.
     */
    public function add_completion_rules() {
        $name = "completioninteractive" . $this->get_suffix();
        $this->_form->addElement("checkbox", $name, "",
            get_string("completioninteractive", "mcp"));
        return [$name];
    }

    /**
     * Check the completed custom-rule form input.
     */
    public function completion_rule_enabled($data) {
        return !empty($data["completioninteractive" . $this->get_suffix()]);
    }

    /**
     * An unchecked rule must be explicitly cleared on update.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $name = "completioninteractive" . $this->get_suffix();
            if (empty($data->{$name}) ||
                    (int)($data->{"completion" . $this->get_suffix()} ?? 0) !== COMPLETION_TRACKING_AUTOMATIC) {
                $data->{$name} = 0;
            }
        }
    }

}
