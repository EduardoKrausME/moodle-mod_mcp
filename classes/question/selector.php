<?php
// This file is part of Moodle - http://moodle.org/
// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.

namespace mod_mcp\question;

use moodle_exception;

/**
 * Preserve existing all/random/shuffle semantics, but draw from validated JSON.
 */
final class selector {
    public static function choose(array $questions, string $mode, int $count, bool $shuffle): array {
        if (!in_array($mode, ["all", "random"], true)) {
            throw new moodle_exception("invalidquestionconfiguration", "mcp");
        }
        $total = count($questions);
        if (!$total || ($mode === "random" && ($count < 1 || $count > $total))) {
            throw new moodle_exception("invalidquestioncount", "mcp");
        }
        $ids = array_map(static fn($q) => (string)$q["id"], $questions);
        if (count(array_unique($ids)) !== $total) {
            throw new moodle_exception("duplicatequestion", "mcp");
        }
        $chosen = array_values($questions);
        if ($mode === "random") {
            for ($i = 0; $i < $count; $i++) {
                $j = random_int($i, $total - 1);
                [$chosen[$i], $chosen[$j]] = [$chosen[$j], $chosen[$i]];
            }
            $chosen = array_slice($chosen, 0, $count);
        }
        if ($shuffle) {
            for ($i = count($chosen) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$chosen[$i], $chosen[$j]] = [$chosen[$j], $chosen[$i]];
            }
        } else {
            $positions = array_flip($ids);
            usort($chosen, static fn($a, $b) => $positions[$a["id"]] <=> $positions[$b["id"]]);
        }
        return $chosen;
    }
}
