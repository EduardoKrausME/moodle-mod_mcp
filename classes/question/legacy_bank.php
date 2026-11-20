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
 * Native Moodle 3.10/3.11 question bank integration.
 *
 * @package mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_mcp\question;

use context;
use context_course;
use moodle_exception;
use stdClass;

/**
 * Validates questions stored using the Moodle 3.10/3.11 question-bank schema.
 */
final class legacy_bank {
    /**
     * Prior to Moodle 4.0, questions live directly in question.category.
     */
    public static function check_bank(int $courseid, int $categoryid): context {
        global $DB;
        $contextid = $DB->get_field('question_categories', 'contextid',
            ['id' => $categoryid], MUST_EXIST);
        $ctx = context::instance_by_id((int)$contextid, MUST_EXIST);
        if ((int)$ctx->id !== (int)context_course::instance($courseid)->id) {
            throw new moodle_exception('nopermissions', 'error', '', 'question bank context');
        }
        return $ctx;
    }

    /**
     * Validate a legacy question and the requesting user's access to it.
     *
     * @param int $questionid Question ID.
     * @param int|null $courseid Course containing the question bank.
     * @param int|null $userid User whose permissions are checked.
     * @return stdClass Validated question metadata.
     */
    public static function validate_question(int $questionid, ?int $courseid = null,
            ?int $userid = null): stdClass {
        global $DB;
        $q = $DB->get_record('question', ['id' => $questionid], '*', MUST_EXIST);
        if (!empty($q->hidden) || (int)$q->parent !== 0 ||
                !in_array($q->qtype, ['multichoice', 'truefalse', 'shortanswer'], true)) {
            throw new moodle_exception('invaliddata');
        }
        $result = (object)[
            'id' => (int)$q->id, 'qtype' => $q->qtype,
            'defaultmark' => (float)$q->defaultmark,
            'version' => 1, 'status' => 'ready',
            'questioncategoryid' => (int)$q->category,
            'ownerid' => (int)$q->createdby,
        ];
        if ($courseid !== null) {
            $ctx = self::check_bank($courseid, (int)$q->category);
            if ($userid !== null &&
                    !has_capability('moodle/question:useall', $ctx, $userid) &&
                    ((int)$q->createdby !== $userid ||
                        !has_capability('moodle/question:usemine', $ctx, $userid))) {
                throw new moodle_exception('nopermissions');
            }
        }
        return $result;
    }
}
