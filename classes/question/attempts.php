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
 * Server-only question engine and assessment attempts.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\question;

use context_module;
use core\lock\lock_config;
use mod_mcp\grade\service;
use moodle_exception;
use question_bank;
use question_display_options;
use question_engine;
use stdClass;

/**
 * Uses core question usages for response persistence, marking and history.
 */
final class attempts {
    /**
     * Ensure the Question Engine is initialized in both Moodle 4.5 and 5.x.
     */
    private static function engine(): void {
        global $CFG;
        require_once($CFG->dirroot . "/question/engine/lib.php");
    }

    /**
     * Begin one server-owned attempt after the student explicitly requests it.
     */
    public static function start(stdClass $mcp, stdClass $cm, int $userid): stdClass {
        global $DB;
        self::engine();
        $ctx = context_module::instance((int)$cm->id);
        if (!$mcp->publishedrevision || !has_capability("mod/mcp:submit", $ctx, $userid)) {
            throw new moodle_exception("nopermissions");
        }
        // Serialize starts for one student to prevent parallel duplicate attempts.
        $factory = lock_config::get_lock_factory("mod_mcp");
        $lock = $factory->get_lock("attempt_" . $mcp->id . "_" . $userid, 10);
        if (!$lock) {
            throw new moodle_exception("invaliddata");
        }
        try {
            return self::start_locked($mcp, $cm, $userid);
        } finally {
            $lock->release();
        }
    }

    /**
     * Start under a per-user lock so the attempt count remains authoritative.
     */
    private static function start_locked(stdClass $mcp, stdClass $cm, int $userid): stdClass {
        global $DB;
        // One attempt by default: avoid parallel double-starts and grade farming.
        if ($DB->record_exists("mcp_attempts", ["mcpid" => $mcp->id, "userid" => $userid])) {
            throw new moodle_exception("invaliddata");
        }
        $slots = slots::active((int)$mcp->id);
        if (!$slots) {
            throw new moodle_exception("questionnotconfigured", "mcp");
        }
        $transaction = $DB->start_delegated_transaction();
        $ctx = context_module::instance((int)$cm->id);
        $quba = question_engine::make_questions_usage_by_activity("mod_mcp", $ctx);
        $quba->set_preferred_behaviour("deferredfeedback");
        $selection = selector::choose($slots, (string)($mcp->questionselection ?? "all"),
            (int)($mcp->questionsperattempt ?? 0), !empty($mcp->shufflequestions));
        foreach ($selection as $index => $slot) {
            // Validate the pinned version before beginning a new attempt.
            $validated = slots::validate_question((int)$slot->questionid);
            if ((int)$validated->version !== (int)$slot->questionversion) {
                throw new moodle_exception("invalidquestionconfiguration", "mcp");
            }
            $question = question_bank::load_question((int)$slot->questionid);
            // Native usage slots are sequential after subset selection, regardless of bank slot.
            $nativeslot = $quba->add_question($question, (float)$slot->maxmark);
            if ($nativeslot !== $index + 1) {
                throw new moodle_exception("invaliddata");
            }
        }
        $quba->start_all_questions(null, time(), $userid);
        question_engine::save_questions_usage_by_activity($quba);
        $now = time();
        $id = $DB->insert_record("mcp_attempts", (object)[
            "mcpid" => $mcp->id, "userid" => $userid, "attemptnum" => 1,
            "qubaid" => $quba->get_id(), "status" => "inprogress",
            "score" => null, "timecreated" => $now, "timemodified" => $now,
        ]);
        $transaction->allow_commit();
        return $DB->get_record("mcp_attempts", ["id" => $id], "*", MUST_EXIST);
    }

    /**
     * Get the student's own attempt, never accept the user ID from a browser request.
     */
    public static function own(int $mcpid, int $userid): ?stdClass {
        global $DB;
        return $DB->get_record("mcp_attempts", ["mcpid" => $mcpid, "userid" => $userid]) ?: null;
    }

    /**
     * Persist the native form response and optionally finish, calculating the grade on server.
     */
    public static function submit(stdClass $mcp, stdClass $attempt, array $post,
                                  bool $finish): void {
        global $DB;
        self::engine();
        if ($attempt->status !== "inprogress") {
            throw new moodle_exception("submissionsclosed", "mcp");
        }
        $transaction = $DB->start_delegated_transaction();
        $quba = question_engine::load_questions_usage_by_activity((int)$attempt->qubaid);
        $quba->process_all_actions(time(), $post);
        if ($finish) {
            $quba->finish_all_questions(time());
        }
        question_engine::save_questions_usage_by_activity($quba);
        $now = time();
        $fields = (object)["id" => $attempt->id, "timemodified" => $now];
        if ($finish) {
            $mark = $quba->get_total_mark();
            $maxmark = 0.0;
            foreach ($quba->get_slots() as $slot) {
                $maxmark += (float)$quba->get_question_attempt($slot)->get_max_mark();
            }
            $fields->status = "finished";
            // Null is important: a manually-graded response must not become zero.
            $fields->score = ($mark === null || $maxmark <= 0) ?
                null : round(max(0, min((float)$mcp->grade, (float)$mark / $maxmark * (float)$mcp->grade)), 5);
        }
        $DB->update_record("mcp_attempts", $fields);
        $transaction->allow_commit();
        if ($finish) {
            service::update($mcp, (int)$attempt->userid);
        }
    }

    /**
     * Render question inputs through the native trusted renderer.
     * The untrusted HTML iframe cannot write to the gradebook or inspect correct answers.
     */
    public static function render(stdClass $attempt): array {
        self::engine();
        $quba = question_engine::load_questions_usage_by_activity((int)$attempt->qubaid);
        $options = new question_display_options();
        $options->readonly = $attempt->status !== "inprogress";
        $options->correctness = question_display_options::HIDDEN;
        $options->rightanswer = question_display_options::HIDDEN;
        $options->feedback = question_display_options::HIDDEN;
        $questions = [];
        foreach ($quba->get_slots() as $slot) {
            $questions[] = ["html" => $quba->render_question($slot, $options, (string)$slot)];
        }
        return $questions;
    }
}
