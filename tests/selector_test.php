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

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\question\selector;

/**
 * JSON subset and shuffle semantics.
 * @covers \mod_mcp\question\selector
 */
final class selector_test extends advanced_testcase {
    public function test_json_random_subset_is_unique_and_ordered_when_not_shuffled(): void {
        $questions = array_map(static fn($n) => ["id" => "q" . $n], range(1, 10));
        for ($i = 0; $i < 10; $i++) {
            $subset = selector::choose($questions, "random", 5, false);
            $ids = array_column($subset, "id");
            $this->assertCount(5, array_unique($ids));
            $positions = array_map(static fn($id) => (int)substr($id, 1), $ids);
            $sorted = $positions;
            sort($sorted);
            $this->assertSame($sorted, $positions);
        }
    }

    public function test_all_mode_keeps_all_json_questions(): void {
        $questions = [["id" => "q1"], ["id" => "q2"]];
        $this->assertSame($questions, selector::choose($questions, "all", 0, false));
    }
}
