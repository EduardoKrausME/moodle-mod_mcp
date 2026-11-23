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
 * Authoring rules advertised directly by mod_mcp tools.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\mcp;

/**
 * Keep essential ChatGPT/Claude instructions in tools/list, not only in an external API.md.
 *
 * The tool description knows the Moodle site's default theme at discovery time.
 * A specific course theme is available only after the tool receives a courseid/cmid.
 */
final class authoring_guidance {
    /**
     * Shared, mandatory authoring rules for all supported activity types.
     */
    public static function core_rules(): string {
        return "MANDATORY mod_mcp authoring rules: First ask the teacher for the activity type, topic, "
            . "target audience, learning goal, desired interactions, feedback and reference material; "
            . "wait for answers before generating or writing content. Do not presume the activity is a quiz. "
            . "Ask for a suitable learning level based on revised Bloom taxonomy: automatic (default), "
            . "progressive, remember, understand, apply, analyze, evaluate or create; adapt the questions "
            . "to the learning goal, not only difficulty. Do NOT ask for Bloom levels in pure opinion "
            . "or satisfaction surveys. Respect the teacher's freely described activity type. "
            . "Produce self-contained, responsive, keyboard-accessible HTML with inline CSS/JS, "
            . "readable contrast and light/dark modes; do not depend on parent iframe DOM or CSS inheritance. "
            . "Use only documented window.MoodleMCP actions: activity.getContext, course.getActivities, "
            . "course.getNavigation, completion.get/complete, progress.load/save/getStatus/saveStatus, "
            . "attempt.get/start/finish, questions.get/submit and tracking.record/summary; "
            . "do not invent APIs, tool parameters, grades, user IDs or course-module IDs. "
            . "Use versioned SDK progress, not localStorage as the only student progress store; "
            . "do not confuse progress=100% with Moodle completion. Graded quiz questions and answers "
            . "belong in private JSON via mcp_save_information and are assessed by the server, "
            . "never through the Moodle question bank or JavaScript grading inside HTML. "
            . "Never expose answer keys in learner HTML. Do not add an independent page footer. "
            . "Read the existing activity with mcp_get_activity before editing; respect returned "
            . "draft revisions and settings hashes. Keep drafts hidden until explicit teacher approval "
            . "and use the connector's preview/confirmation/publish workflow. "
            . "These requirements are included here so reading a separate API.md is not required.";
    }

    /**
     * Return the effective brand variable names for a theme.
     *
     * @param string $theme Moodle theme shortname.
     * @return string[] Brand CSS variables.
     */
    public static function css_variables(string $theme): array {
        $theme = strtolower(preg_replace('/^theme_/', '', $theme));
        if (strpos($theme, "learnlingo") !== false) {
            return ["--bs-primary", "--bs-accent"];
        }
        return ["--bs-primary", "--bs-secondary"];
    }

    /**
     * Build palette guidance using the actual theme name (not hard-coded LearnLingo rules).
     *
     * @param string $theme Moodle theme shortname.
     */
    public static function theme_rules(string $theme): string {
        $theme = strtolower(preg_replace('/^theme_/', '', $theme));
        $variables = self::css_variables($theme);
        $primary = $variables[0];
        $accent = $variables[1];

        $rules = "Theme: {$theme}. Brand colour variables: {$primary} and {$accent}. "
            . "Use var({$primary}, #0d6efd) and var({$accent}, var({$primary}, #0d6efd)) "
            . "for ALL brand colours, including hero backgrounds, every gradient stop, "
            . "buttons, links, borders, icons, cards, hover/focus states and pseudo-elements. "
            . "Do NOT invent a separate palette or use fixed hex/rgb/hsl/named brand colours. "
            . "Only neutral and semantic accessibility colours, and literal fallbacks inside "
            . "var(), are permitted. Derive intermediate shades with color-mix() of these "
            . "variables. Example: background: linear-gradient(123deg, "
            . "var({$primary}, #0d6efd), var({$accent}, var({$primary}, #0d6efd))); "
            . "The iframe is isolated; the Moodle bridge supplies the current CSS variables, "
            . "and the fallbacks support standalone or mobile previews. "
            . "Implement accessible light/dark styles, including explicit data-bs-theme and "
            . "prefers-color-scheme when applicable.";
        return $rules;
    }

    /**
     * Compose the MCP tools/list description. The site theme may differ from a course theme.
     *
     * @param string $purpose Short description of the tool's exact action.
     */
    public static function tool_description(string $purpose): string {
        global $CFG;

        $theme = (string)($CFG->theme ?? "boost");
        return $purpose . " " . self::core_rules() . " "
            . "The following theme is the Moodle SITE DEFAULT at tool-discovery time; "
            . "it is NOT a guarantee of the course theme. After mcp_create_activity or "
            . "mcp_get_activity returns authoring context, use its effective course theme "
            . "and CSS variables as the authority: " . self::theme_rules($theme);
    }

    /**
     * Return the effective course theme and mandatory instructions once courseid is known.
     *
     * @param int $courseid Real Moodle course ID.
     * @return array Theme and authoring rules for this course.
     */
    public static function course_context(int $courseid): array {
        global $CFG, $DB;

        $theme = (string)($CFG->theme ?? "boost");
        if (!empty($CFG->allowcoursethemes)) {
            $coursetheme = $DB->get_field("course", "theme", ["id" => $courseid]);
            if (is_string($coursetheme) && $coursetheme !== "") {
                $theme = $coursetheme;
            }
        }

        return [
            "theme" => $theme,
            "brandcssvariables" => self::css_variables($theme),
            "instructions" => self::core_rules() . " " . self::theme_rules($theme),
        ];
    }
}
