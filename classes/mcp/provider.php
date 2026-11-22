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
 * Auto-discovered Moodle MCP provider.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp;

use local_mcp\extension\read_provider_interface;
use local_mcp\extension\write_provider_interface;

/**
 * Separate permissions and confirmations come from the existing local_mcp server.
 */
final class provider implements read_provider_interface, write_provider_interface {
    /**
     * Return the MCP tools or operation result.
     *
     * @return array READ tools.
     */
    public function get_read_tools(): array {
        return [new read\get_activity()];
    }

    /**
     * Return the MCP tools or operation result.
     *
     * @return array WRITE tools.
     */
    public function get_write_tools(): array {
        return [
            new write\create_activity(),
            new write\save_content(),
            new write\save_information(),
            new write\upload_asset(),
            new write\publish(),
        ];
    }
}
