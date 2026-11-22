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
 * Web service declarations for iframe AJAX bridge.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    "mod_mcp_dispatch" => [
        "classname" => "mod_mcp\\external\\dispatch",
        "methodname" => "execute",
        "description" => "Perform allowlisted student actions on the current MCP module.",
        "type" => "write",
        "ajax" => true,
        "capabilities" => "mod/mcp:view",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    "mod_mcp_get_mobile_content" => [
        "classname" => "mod_mcp\\external\\mobile_content",
        "methodname" => "get",
        "description" => "Return the published interactive experience and its assets to the authenticated mobile app.",
        "type" => "read",
        "services" => [MOODLE_OFFICIAL_MOBILE_SERVICE],
        "ajax" => false,
        "capabilities" => "mod/mcp:view",
    ],
];
