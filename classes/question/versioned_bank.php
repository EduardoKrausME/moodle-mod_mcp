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

/**
 * Stores pointers only, never duplicates questions or alternatives.
 */
final class versioned_bank {
    /**
     * Resolve the Moodle category context to an allowed bank in this course.
     */
    public static function check_bank(int $courseid, int $categoryid): context {
        global $DB;
        $contextid = (int)$DB->get_field("question_categories", "contextid",
            ["id" => $categoryid], MUST_EXIST);
        $ctx = context::instance_by_id($contextid, MUST_EXIST);
        $coursectx = context_course::instance($courseid);
        if ($ctx->id === $coursectx->id) {
            return $ctx;
        }
        // Moodle 5.x may use question bank modules for the same course.
        if ($ctx->contextlevel === CONTEXT_MODULE) {
            $bankcm = get_coursemodule_from_id("", (int)$ctx->instanceid, 0, false, IGNORE_MISSING);
            if ($bankcm && (int)$bankcm->course === $courseid) {
                return $ctx;
            }
        }
        throw new moodle_exception("nopermissions", "error", "", "question bank context");
    }

    /**
     * Validate an actual ready question VERSION, never the unstable latest alias.
     */
    public static function validate_question(int $questionid, ?int $courseid = null,
            ?int $userid = null): \stdClass {
        global $DB;
        $row = $DB->get_record_sql(
            "SELECT q.id, q.qtype, q.defaultmark, v.version, v.status,
                    e.questioncategoryid, e.ownerid
               FROM {question} q
               JOIN {question_versions} v ON v.questionid = q.id
               JOIN {question_bank_entries} e ON e.id = v.questionbankentryid
              WHERE q.id = :questionid",
            ["questionid" => $questionid], MUST_EXIST);
        if ($row->status !== "ready" || !in_array($row->qtype,
                ["multichoice", "truefalse", "shortanswer"], true)) {
            throw new moodle_exception("invaliddata");
        }
        if ($courseid !== null) {
            $ctx = self::check_bank($courseid, (int)$row->questioncategoryid);
            if ($userid !== null &&
                    !has_capability("moodle/question:useall", $ctx, $userid) &&
                    ((int)$row->ownerid !== $userid ||
                        !has_capability("moodle/question:usemine", $ctx, $userid))) {
                throw new moodle_exception("nopermissions");
            }
        }
        return $row;
    }

}
