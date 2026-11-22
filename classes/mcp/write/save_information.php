<?php
// This file is part of Moodle - http://moodle.org/
// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.

namespace mod_mcp\mcp\write;

use local_mcp\security\authenticated_identity;
use mod_mcp\content\metadata;

/**
 * ChatGPT/Claude write tool; the information JSON never goes through a question bank.
 */
final class save_information extends activity_tool {
    public function get_name(): string {
        return "mcp_save_information";
    }

    public function get_title(): string {
        return "MCP activity: save type and JSON information";
    }

    public function get_description(): string {
        return "Save nullable type and JSON information, including quiz questions and answer keys. " .
            "Use mcp_get_activity to obtain expectedhash before writing. Confirmation required.";
    }

    public function get_input_schema(): array {
        return $this->object_schema([
            "cmid" => ["type" => "integer", "minimum" => 1],
            "type" => ["type" => ["string", "null"], "maxLength" => 40],
            "information" => ["type" => ["object", "null"]],
            "expectedhash" => ["type" => "string", "pattern" => "^[a-f0-9]{64}$"],
        ], ["cmid", "type", "information", "expectedhash"]);
    }

    public function preview(array $arguments, authenticated_identity $identity): array {
        $mcp = $this->activity($arguments);
        $hash = metadata::fingerprint($mcp);
        if (!hash_equals($hash, (string)$arguments["expectedhash"])) {
            throw new \moodle_exception("revisionconflict", "mcp");
        }
        $json = metadata::validate($arguments["type"], $arguments["information"]);
        return [
            "cmid" => (int)$arguments["cmid"], "type" => $arguments["type"],
            "questions" => $arguments["type"] === "quiz" ?
                count($arguments["information"]["questions"] ?? []) : 0,
            "information_sha256" => hash("sha256", (string)$json),
            "bytes" => strlen((string)$json), "expectedhash" => $hash,
            "published_content_affected" => !empty($mcp->publishedrevision),
        ];
    }

    public function execute(array $arguments, authenticated_identity $identity): array {
        $this->resolve_context($arguments);
        $mcp = $this->activity($arguments);
        $hash = metadata::save((int)$mcp->id, $arguments["type"],
            $arguments["information"], $arguments["expectedhash"]);
        return ["cmid" => (int)$arguments["cmid"], "type" => $arguments["type"],
            "settingshash" => $hash];
    }
}
