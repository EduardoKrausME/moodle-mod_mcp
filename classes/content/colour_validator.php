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
 * Strict theme-colour policy for authored HTML.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\content;

use moodle_exception;

/**
 * Rejects literal brand colours in CSS, without changing user HTML.
 */
final class colour_validator {
    /** @var string[] Theme families requiring CSS variables. */
    private const THEMES = ["learnlingo", "degrade", "eadflix", "eadtraining", "boost_magnific"];

    /** @var string[] Non-neutral CSS colour keywords. */
    private const NAMED = [
        "aliceblue", "antiquewhite", "aqua", "aquamarine", "azure", "beige", "bisque",
        "blanchedalmond", "blue", "blueviolet", "brown", "burlywood", "cadetblue",
        "chartreuse", "chocolate", "coral", "cornflowerblue", "cornsilk", "crimson",
        "cyan", "darkblue", "darkcyan", "darkgoldenrod", "darkgreen", "darkkhaki",
        "darkmagenta", "darkolivegreen", "darkorange", "darkorchid", "darkred",
        "darksalmon", "darkseagreen", "darkslateblue", "darkturquoise", "darkviolet",
        "deeppink", "deepskyblue", "dodgerblue", "firebrick", "floralwhite",
        "forestgreen", "fuchsia", "gold", "goldenrod", "greenyellow", "honeydew",
        "hotpink", "indianred", "indigo", "ivory", "khaki", "lavender",
        "lavenderblush", "lawngreen", "lemonchiffon", "lightblue", "lightcoral",
        "lightcyan", "lightgoldenrodyellow", "lightgreen", "lightpink",
        "lightsalmon", "lightseagreen", "lightskyblue", "lightsteelblue",
        "lightyellow", "lime", "limegreen", "linen", "magenta", "maroon",
        "mediumaquamarine", "mediumblue", "mediumorchid", "mediumpurple",
        "mediumseagreen", "mediumslateblue", "mediumspringgreen", "mediumturquoise",
        "mediumvioletred", "midnightblue", "mintcream", "mistyrose", "moccasin",
        "navajowhite", "navy", "oldlace", "olive", "olivedrab", "orange",
        "orangered", "orchid", "palegoldenrod", "palegreen", "paleturquoise",
        "palevioletred", "papayawhip", "peachpuff", "peru", "pink", "plum",
        "powderblue", "purple", "rebeccapurple", "red", "rosybrown", "royalblue",
        "saddlebrown", "salmon", "sandybrown", "seagreen", "seashell", "sienna",
        "skyblue", "slateblue", "springgreen", "steelblue", "tan", "teal",
        "thistle", "tomato", "turquoise", "violet", "wheat", "yellow", "yellowgreen",
    ];

    /**
     * Validate <style> blocks and HTML style attributes against the active theme.
     *
     * @param string $html Document being saved.
     * @param string $theme Theme shortname.
     */
    public static function validate(string $html, string $theme): void {
        $theme = strtolower(preg_replace('/^theme_/i', '', $theme));
        if (!in_array($theme, self::THEMES, true)) {
            return;
        }
        $styles = [];
        if (preg_match_all('~<style\b[^>]*>(.*?)</style\s*>~is', $html, $matches)) {
            $styles = $matches[1];
        }
        if (preg_match_all('~\bstyle\s*=\s*(["\x27])(.*?)\1~is', $html, $matches)) {
            $styles = array_merge($styles, $matches[2]);
        }
        foreach ($styles as $style) {
            $style = preg_replace('~/\*.*?\*/~s', '', $style);
            preg_match_all('/(?:^|[;{}])\s*([a-zA-Z_-][a-zA-Z0-9_-]*)\s*:\s*([^;{}]+)/',
                $style, $declarations, PREG_SET_ORDER);
            foreach ($declarations as $declaration) {
                $property = strtolower($declaration[1]);
                if (in_array($property, ["content", "font", "font-family", "src"], true)) {
                    continue;
                }
                $value = self::mask_safe_functions($declaration[2]);
                $value = preg_replace('/"[^"]*"|\x27[^\x27]*\x27/s', '', $value);
                $literal = self::literal_colour($value);
                if ($literal === null) {
                    continue;
                }
                $accent = $theme === "learnlingo" ? "--bs-accent" : "--bs-secondary";
                throw new moodle_exception("fixedbrandcolour", "mcp", "", (object)[
                    "colour" => $literal, "property" => $property,
                    "primary" => "--bs-primary", "accent" => $accent,
                ]);
            }
        }
    }

    /**
     * Mask URL fragments and var() fallbacks, preserving other CSS expressions.
     */
    private static function mask_safe_functions(string $value): string {
        $output = "";
        $length = strlen($value);
        for ($i = 0; $i < $length;) {
            $head = strtolower(substr($value, $i, 4));
            if ($head !== "var(" && $head !== "url(") {
                $output .= $value[$i++];
                continue;
            }
            $depth = 1;
            $comma = null;
            $end = null;
            for ($j = $i + 4; $j < $length; $j++) {
                if ($value[$j] === "(") {
                    $depth++;
                } else if ($value[$j] === ")") {
                    if (--$depth === 0) {
                        $end = $j;
                        break;
                    }
                } else if ($value[$j] === "," && $depth === 1 && $comma === null) {
                    $comma = $j;
                }
            }
            if ($end === null) {
                $output .= $value[$i++];
                continue;
            }
            if ($head === "url(") {
                $output .= str_repeat(" ", $end - $i + 1);
            } else if ($comma !== null) {
                $output .= substr($value, $i, $comma - $i) .
                    str_repeat(" ", $end - $comma + 1);
            } else {
                $output .= substr($value, $i, $end - $i + 1);
            }
            $i = $end + 1;
        }
        return $output;
    }

    /**
     * Detect fixed non-neutral hexadecimal, functional or named colours.
     */
    private static function literal_colour(string $value): ?string {
        if (preg_match_all('/#[0-9a-f]{3,8}\b/i', $value, $hexes)) {
            foreach ($hexes[0] as $hex) {
                $digits = strtolower(substr($hex, 1));
                $gray = (strlen($digits) === 3 || strlen($digits) === 4) ?
                    ($digits[0] === $digits[1] && $digits[1] === $digits[2]) :
                    (substr($digits, 0, 2) === substr($digits, 2, 2) &&
                        substr($digits, 2, 2) === substr($digits, 4, 2));
                if (!$gray) {
                    return $hex;
                }
            }
        }
        if (preg_match_all('/\b(?:rgba?|hsla?|hwb|lab|lch|oklab|oklch|color)\s*\([^)]*\)/i',
                $value, $functions)) {
            foreach ($functions[0] as $function) {
                if (stripos($function, "var(") !== false) {
                    continue;
                }
                $gray = false;
                if (preg_match('/^rgba?\(\s*(\d+(?:\.\d+)?%?)\s*[, ]\s*' .
                        '(\d+(?:\.\d+)?%?)\s*[, ]\s*(\d+(?:\.\d+)?%?)/i', $function, $parts)) {
                    $gray = $parts[1] === $parts[2] && $parts[2] === $parts[3];
                } else if (preg_match('/^hsla?\(\s*[-.\d]+(?:deg|rad|turn)?\s*[, ]\s*0(?:\.0+)?%/i',
                        $function)) {
                    $gray = true;
                }
                if (!$gray) {
                    return $function;
                }
            }
        }
        if (preg_match('/(?<![-\w])(?:' . implode("|", self::NAMED) . ')\b/i',
                $value, $match)) {
            return $match[0];
        }
        return null;
    }
}
