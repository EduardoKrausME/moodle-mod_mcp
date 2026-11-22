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

namespace mod_mcp\question;

use moodle_exception;

/**
 * Preserve existing all/random/shuffle semantics, but draw from validated JSON.
 * @package mod_mcp
 */
final class selector {
    /**
     * choose for JSON assessment handling.
     *
     * @param array $questions Parameter.
     * @param string $mode Parameter.
     * @param int $count Parameter.
     * @param bool $shuffle Parameter.
     * @return array Result.
     */
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
