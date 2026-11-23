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
 * Tests for the theme-colour save restrictions.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\content\colour_validator;
use mod_mcp\content\service;
use moodle_exception;

/**
 * @covers \mod_mcp\content\colour_validator
 */
final class colour_validator_test extends advanced_testcase {
    /**
     * Reject fixed brand colours in gradients, custom properties, inline CSS and colour functions.
     */
    public function test_rejects_fixed_colours(): void {
        $invalid = [
            '<style>.hero{background:linear-gradient(123deg,#123b3c,#145e55 67%,#208977)}</style>',
            '<div style="background-color:#145e55">Test</div>',
            '<style>.hero:hover{background:rgb(20,94,85)}</style>',
            '<style>.hero{color:hsl(170,50%,20%)}</style>',
            '<style>:root{--brand:#123b3c}</style>',
            '<style>.hero{background:linear-gradient(teal,navy)}</style>',
            '<style>.hero{background:color-mix(in srgb,var(--bs-primary),#208977)}</style>',
        ];
        foreach ($invalid as $html) {
            try {
                colour_validator::validate($html, "learnlingo");
                $this->fail("Accepted fixed brand colour in: " . $html);
            } catch (moodle_exception $exception) {
                $this->assertSame("fixedbrandcolour", $exception->errorcode);
            }
        }
    }

    /**
     * Allow palette tokens, fallbacks, semantic variables, neutral colours and URLs.
     */
    public function test_allows_palette_variables_and_neutrals(): void {
        $valid = [
            '<style>.hero{color:#fff;background:linear-gradient(123deg,' .
                'var(--bs-primary,#0d6efd),var(--bs-accent,var(--bs-primary,#0d6efd)))}</style>',
            '<style>.hero{background:color-mix(in srgb,var(--bs-primary) 70%,white)}</style>',
            '<style>.hero{box-shadow:0 2px 8px rgba(0,0,0,.5);color:rgb(255 255 255)}</style>',
            '<style>.error{color:var(--bs-danger,#dc3545)}</style>',
            '<div style="color:#222;background:var(--bs-primary,#0d6efd)">Test</div>',
            '<style>.logo{mask:url(#brand-icon);fill:currentColor}</style>',
        ];
        foreach ($valid as $html) {
            colour_validator::validate($html, "theme_learnlingo");
            $this->addToAssertionCount(1);
        }
        colour_validator::validate('<style>.hero{background:#123b3c}</style>', "boost");
        colour_validator::validate('<style>.hero{background:var(--bs-secondary,#123b3c)}</style>', "eadflix");
    }

    /**
     * Invalid input is rejected before the immutable draft revision can be updated.
     */
    public function test_save_fails_without_updating_draft(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        $CFG->theme = "learnlingo";
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $draft = service::save((int)$module->id, '<style>.hero{background:var(--bs-primary,#0d6efd)}</style>',
            2, 0);
        $count = $DB->count_records("mcp_revisions", ["mcpid" => $module->id]);
        try {
            service::save((int)$module->id, '<style>.hero{background:#123b3c}</style>', 2, $draft);
            $this->fail("Invalid HTML was persisted");
        } catch (moodle_exception $exception) {
            $this->assertSame("fixedbrandcolour", $exception->errorcode);
        }
        $this->assertSame($count, $DB->count_records("mcp_revisions", ["mcpid" => $module->id]));
        $this->assertEquals($draft, $DB->get_field("mcp", "draftrevision", ["id" => $module->id]));
    }
}
