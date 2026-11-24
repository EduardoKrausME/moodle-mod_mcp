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
 * JSON activity assessments and metadata.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\content\metadata;
use moodle_exception;

/**
 * Existing API name and schema; stores all/random/shuffle in information JSON.
 * @package mod_mcp
 */
final class configure_questions extends activity_tool {
    /**
     * get name for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return "mcp_configure_questions";
    }

    /**
     * get title for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return "MCP activity: configure JSON question selection";
    }

    /**
     * get description for JSON assessment handling.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return "Configure an existing quiz JSON question pool, not the Moodle Question Bank. "
            . "Before changing the settings, ask the teacher whether to shuffle questions "
            . "and how many to draw per attempt. Set selection=all with count=0 or "
            . "selection=random with a count from 1 to the available question total. "
            . "Read mcp_get_activity for the current settings hash. Confirmation required.";
    }

    /**
     * get input schema for JSON assessment handling.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "questionselection" => ["type" => "string", "enum" => ["all", "random"]],
            "questionsperattempt" => ["type" => "integer", "minimum" => 0],
            "shufflequestions" => ["type" => "boolean"],
            "expectedhash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
        ], ["cmid", "questionselection", "questionsperattempt", "shufflequestions", "expectedhash"]);
    }

    /**
     * proposed for JSON assessment handling.
     *
     * @param array $arguments Parameter.
     * @return array Result.
     */
    private function proposed(array $arguments): array {
        $mcp = $this->activity($arguments);
        if ($mcp->activitytype !== "quiz") {
            throw new moodle_exception("questionnotconfigured", "mcp");
        }
        $hash = metadata::fingerprint($mcp);
        if (!hash_equals($hash, (string)$arguments["expectedhash"])) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        $info = metadata::information($mcp);
        $available = count($info["questions"] ?? []);
        $mode = $arguments["questionselection"];
        $count = (int)$arguments["questionsperattempt"];
        $shuffle = $arguments["shufflequestions"] === true;
        if (!in_array($mode, ["all", "random"], true) || $count < 0 ||
                ($mode === "random" && ($count < 1 || $count > $available))) {
            throw new moodle_exception("invalidquestioncount", "mcp");
        }
        $info["selection"] = $mode;
        $info["count"] = $mode === "all" ? 0 : $count;
        $info["shuffle"] = $shuffle;
        metadata::validate("quiz", $info);
        return ["cmid" => (int)$arguments["cmid"], "questionselection" => $mode,
            "questionsperattempt" => $info["count"], "shufflequestions" => $shuffle,
            "available" => $available, "expectedhash" => $hash, "information" => $info];
    }

    /**
     * preview for JSON assessment handling.
     *
     * @param array $arguments Parameter.
     * @param authenticated_identity $identity Parameter.
     * @return array Result.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $proposed = $this->proposed($arguments);
        unset($proposed["information"]);
        return $proposed + ["published" => false];
    }

    /**
     * execute for JSON assessment handling.
     *
     * @param array $arguments Parameter.
     * @param authenticated_identity $identity Parameter.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        $proposed = $this->proposed($arguments);
        $mcp = $this->activity($arguments);
        $hash = metadata::save((int)$mcp->id, "quiz",
            $proposed["information"], $arguments["expectedhash"]);
        unset($proposed["information"]);
        $proposed["settingshash"] = $hash;
        return $proposed + ["published" => false];
    }
}
