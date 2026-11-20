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
 * Verify native question schema selection on old and modern Moodle.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp;

use advanced_testcase;
use mod_mcp\question\bank_factory;
use mod_mcp\question\legacy_bank;
use mod_mcp\question\versioned_bank;

/**
 * Tests the selection of the native question-bank adapter.
 *
 * @covers \mod_mcp\question\bank_factory::driver
 */
final class question_bank_compatibility_test extends advanced_testcase {
    public function test_driver_matches_question_storage_version(): void {
        global $CFG;
        $this->resetAfterTest();
        $expected = (int)$CFG->version < 2022041900
            ? legacy_bank::class
            : versioned_bank::class;
        $this->assertSame($expected, bank_factory::driver());
    }
}
