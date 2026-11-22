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
 * Native question selection configuration and concurrency fingerprint.
 *
 * @package mod_mcp
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\question;

use moodle_exception;
use stdClass;

/**
 * A single source of truth for form and MCP configurations.
 */
final class configuration {
    /**
     * Reject invalid selection settings before persisting or starting an attempt.
     */
    public static function validate(string $mode, int $count, int $shuffle): void {
        if (!in_array($mode, ["all", "random"], true) || !in_array($shuffle, [0, 1], true) ||
                $count < 0 || ($mode === "random" && $count < 1)) {
            throw new moodle_exception("invalidquestionconfiguration", "mcp");
        }
    }

    /**
     * Produce an optimistic concurrency token, including available question versions.
     */
    public static function fingerprint(stdClass $mcp): string {
        $slots = array_map(static fn($s) => [
            (int)$s->slot, (int)$s->questionid, (int)$s->questionversion, (float)$s->maxmark,
        ], slots::active((int)$mcp->id));
        return hash("sha256", json_encode([
            (int)$mcp->id, (int)$mcp->timemodified,
            (string)$mcp->questionselection, (int)$mcp->questionsperattempt,
            (int)$mcp->shufflequestions, $slots,
        ]));
    }
}
