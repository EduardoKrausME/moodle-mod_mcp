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
 * Moodle module configuration form for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . "/course/moodleform_mod.php");

/**
 * Edit the module and its draft HTML document.
 */
class mod_mcp_mod_form extends moodleform_mod {
    /**
     * Build the editing form with one complete HTML field.
     */
    public function definition(): void {
        $mform = $this->_form;
        $mform->addElement("text", "name", get_string("name"), ["size" => 64]);
        $mform->setType("name", PARAM_TEXT);
        $mform->addRule("name", null, "required", null, "client");
        $mform->addElement("textarea", "content", get_string("content", "mcp"),
            ["rows" => 22, "cols" => 90, "spellcheck" => "false"]);
        $mform->setType("content", PARAM_RAW);
        $mform->addHelpButton("content", "content", "mcp");
        $mform->addElement("text", "grade", get_string("grade", "mcp"), ["size" => 8]);
        $mform->setType("grade", PARAM_FLOAT);
        $mform->setDefault("grade", 10);
        $this->standard_intro_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Load only the latest draft for an existing module.
     */
    public function data_preprocessing(&$defaultvalues): void {
        if (!empty($this->current->instance)) {
            global $DB;
            $mcp = $DB->get_record("mcp", ["id" => (int)$this->current->instance], "*", MUST_EXIST);
            $revision = \mod_mcp\content\service::get((int)$mcp->id, (int)$mcp->draftrevision);
            $defaultvalues["content"] = $revision ? $revision->content : "";
        }
    }
}
