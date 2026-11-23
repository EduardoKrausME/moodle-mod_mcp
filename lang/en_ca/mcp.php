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
 * Language overrides for en_ca.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Only strings differing from the parent language are defined here.
$string['grade'] = 'Maximum grade';
$string['modulename'] = 'Interactive Experience';
$string['pluginname'] = 'Interactive Experience';
$string['promptcolours'] = 'MANDATORY BRAND COLOUR RULE for the {$a->theme} theme: the variables {$a->variables} are the only sources of brand colours. Do not invent an alternative palette or hardcode hex, rgb()/rgba(), hsl()/hsla() or named brand colours for hero sections, gradients and every gradient stop, buttons, links, borders, icons, cards, hover/focus states or pseudo-elements. All brand colours MUST use var({$a->primary}, #0d6efd) and var({$a->complementary}, var({$a->primary}, #0d6efd)), or local tokens derived from these variables. Valid example: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. For intermediate tones and transparency, use color-mix() with these variables. Literal colours are allowed only as fallbacks inside var() or for neutrals and semantic accessibility states, never as substitutes for theme brand colours. Before delivering the HTML, audit the entire CSS, including media queries and pseudo-elements, and replace any invented brand colours. Mod MCP passes the current theme variable values to the isolated iframe in the Moodle browser; fallbacks remain necessary for standalone previews and the app.';
