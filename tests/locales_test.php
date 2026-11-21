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
 * Tests for the supported language directories.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;

/**
 * Keeps supported locale directories aligned with the local_mcp plugin.
 *
 * @coversNothing
 */
final class locales_test extends advanced_testcase {
    /**
     * All supported languages and regional variations have a valid PHP language file.
     */
    public function test_language_directories_match_local_mcp(): void {
        $expected = [
            "ar", "de", "de_ch", "de_comm", "de_du", "en", "en_ca", "en_us",
            "es", "es_ar", "es_co", "es_mx", "es_ve", "fr", "fr_ca", "fr_incl",
            "it", "nl", "nl_ho", "pt_br", "ru", "zh_cn",
        ];
        $langdir = dirname(__DIR__) . "/lang";
        $actual = array_values(array_filter(scandir($langdir), static function(string $entry) use ($langdir): bool {
            return $entry !== "." && $entry !== ".." && is_dir($langdir . "/" . $entry);
        }));
        sort($actual);
        $this->assertSame($expected, $actual);

        foreach ($expected as $locale) {
            $path = $langdir . "/" . $locale . "/mcp.php";
            $this->assertFileExists($path);
            $content = file_get_contents($path);
            $this->assertStringContainsString("defined('MOODLE_INTERNAL') || die;", $content);
        }
    }

    /**
     * Main translations expose the component name and common prompt instructions.
     */
    public function test_primary_language_files_contain_translated_interface(): void {
        $primary = ["ar", "de", "en", "es", "es_mx", "fr", "it", "nl", "pt_br", "ru", "zh_cn"];
        foreach ($primary as $locale) {
            $content = file_get_contents(dirname(__DIR__) . "/lang/" . $locale . "/mcp.php");
            foreach (["modulename", "pluginname", "promptcolours", "promptdarkmode"] as $key) {
                $this->assertStringContainsString("$" . "string['" . $key . "']", $content);
            }
        }
    }
}
