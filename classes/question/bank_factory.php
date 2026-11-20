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
 * Native question bank compatibility selector.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\question;

/**
 * Selects the question-bank implementation for the installed Moodle version.
 */
final class bank_factory {
    /** @var string|null */
    private static $driver = null;

    /**
     * Return the adapter class for the installed question storage schema.
     *
     * @return string Fully qualified adapter class name.
     */
    public static function driver(): string {
        global $CFG;
        if (self::$driver === null) {
            self::$driver = (int)$CFG->version < 2022041900
                ? legacy_bank::class : versioned_bank::class;
        }
        return self::$driver;
    }
}
