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
 * Version-pinned native question references.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\question;

use context;
use context_course;
use context_module;
use moodle_exception;
use stdClass;

/**
 * Stores pointers only, never duplicates questions or alternatives.
 */
final class slots {
    /**
     * Resolve the appropriate native question bank without branching in the assessment logic.
     */
    public static function check_bank(int $courseid, int $categoryid): context {
        $driver = bank_factory::driver();
        return $driver::check_bank($courseid, $categoryid);
    }

    /**
     * Check question visibility and the caller's permissions in the selected bank.
     */
    public static function validate_question(int $questionid, ?int $courseid = null,
            ?int $userid = null): stdClass {
        $driver = bank_factory::driver();
        return $driver::validate_question($questionid, $courseid, $userid);
    }

    /**
     * List only active positions in stable sequential ordering.
     */
    public static function active(int $mcpid): array {
        global $DB;
        return array_values($DB->get_records("mcp_slots", ["mcpid" => $mcpid, "active" => 1],
            "slot ASC, id ASC"));
    }

    /**
     * Link a question to the activity, or replace a position without mutating past usages.
     *
     * @return array Real IDs consumed by the HTML author.
     */
    public static function attach(int $mcpid, int $questionid, float $mark, int $userid,
            int $replace = 0): array {
        global $DB;
        $activity = $DB->get_record("mcp", ["id" => $mcpid], "*", MUST_EXIST);
        if ($mark <= 0 || !is_finite($mark)) {
            throw new moodle_exception("invaliddata");
        }
        $question = self::validate_question($questionid, (int)$activity->course, $userid);
        $transaction = $DB->start_delegated_transaction();
        $active = self::active($mcpid);
        foreach ($active as $existing) {
            if ((int)$existing->questionid === $questionid && (!$replace ||
                    (int)$existing->slot !== $replace)) {
                throw new moodle_exception("duplicatequestion", "mcp");
            }
        }
        $slot = $replace ?: (count($active) ? max(array_map(static fn($item) => (int)$item->slot, $active)) + 1 : 1);
        if ($replace) {
            $previous = $DB->get_record("mcp_slots",
                ["mcpid" => $mcpid, "slot" => $replace, "active" => 1], "*", MUST_EXIST);
            // The old question and its recorded responses must stay in the native question bank.
            $DB->set_field("mcp_slots", "active", 0, ["id" => $previous->id]);
        } else if ($DB->record_exists("mcp_slots", ["mcpid" => $mcpid, "slot" => $slot, "active" => 1])) {
            throw new moodle_exception("invaliddata");
        }
        $slotid = $DB->insert_record("mcp_slots", (object)[
            "mcpid" => $mcpid, "slot" => $slot, "questionid" => $questionid,
            "questionversion" => (int)$question->version, "maxmark" => $mark,
            "active" => 1, "timecreated" => time(),
        ]);
        $transaction->allow_commit();
        return ["slotid" => $slotid, "slot" => $slot, "questionid" => $questionid,
            "questionversion" => (int)$question->version];
    }
}
