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
 * Confirmed MCP configuration of server-side native question selection.
 *
 * @package mod_mcp
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\question\configuration;
use mod_mcp\question\slots;
use moodle_exception;

/**
 * The plugin is discovered by local_mcp through the mod_mcp provider.
 */
final class configure_questions extends activity_tool {
    public function get_name(): string {
        return "mcp_configure_questions";
    }

    public function get_title(): string {
        return "MCP activity: configure native question selection";
    }

    public function get_description(): string {
        return "Set all or random-subset selection and optional order shuffle, with a revision fingerprint; never publish.";
    }

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
     * Apply validation before previews and writes.
     */
    private function proposed(array $arguments): array {
        $mcp = $this->activity($arguments);
        $hash = configuration::fingerprint($mcp);
        if (!hash_equals($hash, (string)$arguments["expectedhash"])) {
            throw new moodle_exception("revisionconflict", "mcp");
        }
        $mode = (string)$arguments["questionselection"];
        $count = (int)$arguments["questionsperattempt"];
        $shuffle = $arguments["shufflequestions"] === true ? 1 : 0;
        configuration::validate($mode, $count, $shuffle);
        $available = count(slots::active((int)$mcp->id));
        if ($mode === "random" && $count > $available) {
            throw new moodle_exception("invalidquestioncount", "mcp");
        }
        return ["cmid" => (int)$arguments["cmid"], "questionselection" => $mode,
            "questionsperattempt" => $mode === "all" ? 0 : $count,
            "shufflequestions" => (bool)$shuffle, "available" => $available,
            "expectedhash" => $hash];
    }

    public function preview(array $arguments, authenticated_identity $identity): array {
        return $this->proposed($arguments) + ["published" => false];
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        global $DB;
        $this->resolve_context($arguments);
        $transaction = $DB->start_delegated_transaction();
        $proposed = $this->proposed($arguments);
        $mcp = $this->activity($arguments);
        $DB->update_record("mcp", (object)[
            "id" => $mcp->id,
            "questionselection" => $proposed["questionselection"],
            "questionsperattempt" => $proposed["questionsperattempt"],
            "shufflequestions" => $proposed["shufflequestions"] ? 1 : 0,
            "timemodified" => time(),
        ]);
        $transaction->allow_commit();
        return $proposed + ["published" => false];
    }
}
