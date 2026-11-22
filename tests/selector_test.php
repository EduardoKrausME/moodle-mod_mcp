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
 * Native question subset and ordering tests.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp;

use advanced_testcase;
use mod_mcp\question\selector;
use mod_mcp\question\configuration;
use moodle_exception;

/**
 * Verify server-side random selection without grading in the browser.
 *
 * @covers \mod_mcp\question\selector
 * @covers \mod_mcp\question\configuration
 */
final class selector_test extends advanced_testcase {
    /**
     * Build a fixed pool of distinct native-like question slot references.
     *
     * @return array Test slot records.
     */
    private function slots(): array {
        return array_map(static fn($i) => (object)["slot" => $i, "questionid" => 100 + $i],
            range(1, 10));
    }

    public function test_random_subset_has_five_distinct_questions(): void {
        for ($i = 0; $i < 30; $i++) {
            $chosen = selector::choose($this->slots(), "random", 5, true);
            $ids = array_map(static fn($s) => $s->questionid, $chosen);
            $this->assertCount(5, $ids);
            $this->assertCount(5, array_unique($ids));
            $this->assertEmpty(array_diff($ids, range(101, 110)));
        }
    }

    public function test_random_subset_can_preserve_bank_order(): void {
        $chosen = selector::choose($this->slots(), "random", 5, false);
        $positions = array_map(static fn($s) => $s->slot, $chosen);
        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions);
        $this->assertCount(5, $chosen);
    }

    public function test_all_mode_preserves_all_original_slots_by_default(): void {
        $chosen = selector::choose($this->slots(), "all", 0, false);
        $this->assertSame(range(1, 10), array_map(static fn($s) => $s->slot, $chosen));
    }

    public function test_more_questions_than_available_is_rejected(): void {
        $this->expectException(moodle_exception::class);
        selector::choose($this->slots(), "random", 11, false);
    }

    public function test_duplicate_question_ids_are_rejected(): void {
        $slots = $this->slots();
        $slots[9]->questionid = $slots[0]->questionid;
        $this->expectException(moodle_exception::class);
        selector::choose($slots, "random", 5, true);
    }

    public function test_random_configuration_requires_positive_count(): void {
        $this->expectException(moodle_exception::class);
        configuration::validate("random", 0, 1);
    }
}
