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
 * Select server-owned questions for a single attempt.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\question;

use moodle_exception;

/**
 * Never exposes an answer key or makes selection decisions in authored HTML.
 */
final class selector {
    /**
     * Return a subset of existing version-pinned slots.
     *
     * @param array $slots Active bank slots in their original order.
     * @param string $mode 'all' or 'random'.
     * @param int $count Requested size when using random mode.
     * @param bool $shuffle Whether to shuffle presentation order.
     * @return array Selected slots, with unique question IDs.
     */
    public static function choose(array $slots, string $mode, int $count, bool $shuffle): array {
        if (!in_array($mode, ["all", "random"], true)) {
            throw new moodle_exception("invalidquestionconfiguration", "mcp");
        }
        $total = count($slots);
        if (!$total || ($mode === "random" && ($count < 1 || $count > $total))) {
            throw new moodle_exception("invalidquestioncount", "mcp");
        }
        // A question attached twice would otherwise be shown twice in the same attempt.
        $ids = array_map(static fn($slot) => (int)$slot->questionid, $slots);
        if (count(array_unique($ids)) !== $total) {
            throw new moodle_exception("duplicatequestion", "mcp");
        }
        $chosen = array_values($slots);
        if ($mode === "random") {
            // Partial Fisher-Yates: sample without replacement using a server-side CSPRNG.
            for ($i = 0; $i < $count; $i++) {
                $j = random_int($i, $total - 1);
                [$chosen[$i], $chosen[$j]] = [$chosen[$j], $chosen[$i]];
            }
            $chosen = array_slice($chosen, 0, $count);
        }
        if ($shuffle) {
            // Shuffle all selected questions, including when the mode is 'all'.
            for ($i = count($chosen) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$chosen[$i], $chosen[$j]] = [$chosen[$j], $chosen[$i]];
            }
        } else {
            usort($chosen, static fn($a, $b) => (int)$a->slot <=> (int)$b->slot);
        }
        return $chosen;
    }
}
