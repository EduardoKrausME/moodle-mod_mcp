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
 * Create a Moodle question before generating any HTML.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\question\bank_service;
use local_mcp\security\authenticated_identity;
use mod_mcp\question\slots;

/**
 * Uses the existing local_mcp question-bank authoring service.
 * No second store for answer options, correct answers or fractions.
 */
final class create_question extends activity_tool {
    /**
     * get name.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_create_question";
    }

    /**
     * get title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: create bank question";
    }

    /**
     * get description.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Create a native Moodle multichoice question first; returns real question and version IDs for attaching.";
    }

    /**
     * get input schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "categoryid" => ["type" => "integer", "minimum" => 1],
            "name" => ["type" => "string", "minLength" => 1],
            "questiontext" => ["type" => "string", "minLength" => 1],
            "defaultmark" => ["type" => "number", "exclusiveMinimum" => 0],
            "answers" => ["type" => "array", "minItems" => 2, "maxItems" => 50,
                "items" => ["type" => "object", "properties" => [
                    "text" => ["type" => "string", "minLength" => 1],
                    "fraction" => ["type" => "number", "minimum" => -1, "maximum" => 1],
                    "feedback" => ["type" => "string"],
                ], "required" => ["text", "fraction"], "additionalProperties" => false]],
        ], ["cmid", "categoryid", "name", "questiontext", "answers"]);
    }

    /**
     * preview.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $mcp = $this->activity($arguments);
        slots::check_bank((int)$mcp->course, (int)$arguments["categoryid"]);
        // Never include the answer key or correctness fractions in MCP confirmation previews.
        return ["cmid" => (int)$arguments["cmid"], "categoryid" => (int)$arguments["categoryid"],
            "name" => clean_param($arguments["name"], PARAM_TEXT),
            "type" => "multichoice", "answercount" => count($arguments["answers"])];
    }

    /**
     * execute.
     *
     * @param mixed $arguments Input arguments.
     * @param mixed $identity Input identity.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        $mcp = $this->activity($arguments);
        slots::check_bank((int)$mcp->course, (int)$arguments["categoryid"]);
        // Do not permit the caller to override the qtype, status or single-choice contract.
        $args = [
            "categoryid" => (int)$arguments["categoryid"],
            "qtype" => "multichoice", "status" => "ready", "single" => true,
            "name" => $arguments["name"], "questiontext" => $arguments["questiontext"],
            "defaultmark" => (float)($arguments["defaultmark"] ?? 1),
            "answers" => $arguments["answers"],
        ];
        return bank_service::save($args, $identity);
    }
}
