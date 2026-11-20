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
 * Test data generator for MCP activities.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

/**
 * Create real mod_mcp instances for Moodle PHPUnit and Behat tests.
 */
class mod_mcp_generator extends testing_module_generator {
    /**
     * Create one test activity through the Moodle course module generator.
     *
     * @param array|stdClass|null $record Requested module data.
     * @param array|null $options Module creation options.
     * @return stdClass Newly created activity instance.
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object)(array)$record;
        if (empty($record->name)) {
            $record->name = "MCP test activity";
        }
        if (!isset($record->grade)) {
            $record->grade = 10;
        }
        if (!isset($record->intro)) {
            $record->intro = "";
        }
        return parent::create_instance($record, $options);
    }
}
