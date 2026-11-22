<?php
// This file is part of Moodle - http://moodle.org/
// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\content\metadata;
use moodle_exception;

/**
 * Existing API name and schema; stores all/random/shuffle in information JSON.
 */
final class configure_questions extends activity_tool {
    public function get_name(): string {
        return "mcp_configure_questions";
    }

    public function get_title(): string {
        return "MCP activity: configure JSON question selection";
    }

    public function get_description(): string {
        return "Choose all or a random subset and optionally shuffle question order. " .
            "Operates on JSON questions, not Moodle Question Bank. Confirmation required.";
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

    public function preview(array $arguments, authenticated_identity $identity): array {
        $proposed = $this->proposed($arguments);
        unset($proposed["information"]);
        return $proposed + ["published" => false];
    }

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
