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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Native gradebook integration for finished attempts.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\grade;

use stdClass;

/**
 * Never accepts client-supplied grade values.
 */
final class service {
    /**
     * Push the best finished native attempt into Moodle gradebook.
     */
    public static function update(stdClass $mcp, int $userid = 0): void {
        global $DB;
        $params = ["mcpid" => (int)$mcp->id, "status" => "finished"];
        $where = "mcpid = :mcpid AND status = :status";
        if ($userid) {
            $where .= " AND userid = :userid";
            $params["userid"] = $userid;
        }
        $attempts = $DB->get_records_select("mcp_attempts", $where, $params, "userid ASC, score DESC");
        $grades = [];
        foreach ($attempts as $attempt) {
            if ($attempt->score !== null && !array_key_exists($attempt->userid, $grades)) {
                $grades[$attempt->userid] = (object)[
                    "userid" => (int)$attempt->userid,
                    "rawgrade" => max(0, min((float)$mcp->grade, (float)$attempt->score)),
                ];
            }
        }
        mcp_grade_item_update($mcp, $grades ?: null);
    }
}
