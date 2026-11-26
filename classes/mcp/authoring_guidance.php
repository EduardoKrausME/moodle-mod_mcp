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

use mod_mcp\authoring\material_theme;

/**
 * Keep essential ChatGPT/Claude instructions in tools/list, not only in an external API.md.
 *
 * The tool description knows the Moodle site's default theme at discovery time.
 * A specific course theme is available only after the tool receives a courseid/cmid.
 */
final class authoring_guidance {
    /**
     * Rules shared by tool descriptions, independent of the activity format.
     */
    public static function core_rules(): string {
        return "MANDATORY mod_mcp authoring rules: Use the connected Moodle's live tool discovery; "
            . "never invent parameters or actions. A reusable material theme is optional. "
            . "Ask the teacher what to create and wait for their answers and chosen references "
            . "before generating questions, HTML, CSS or JavaScript, unless already supplied. "
            . "Do not deduce content from the Moodle activity title or assume it is a quiz. "
            . "Read existing activities with mcp_get_activity before editing, and respect "
            . "returned current revisions and settings hashes. Do not turn these authoring "
            . "instructions into student-visible content.";
    }

    /**
     * Interview the teacher before preparing material, not while writing it.
     */
    public static function interview_rules(): string {
        return "FIRST ask the teacher what kind of activity they want, which topics must be "
            . "covered and what learners should achieve. Ask whether the teacher has any "
            . "documents, PDFs, images or other references; request the files if available, "
            . "inspect them before proceeding and never invent their contents. "
            . "Wait for answers and files the teacher chooses to provide. "
            . "Then clarify audience, desired interactions, feedback and other necessary "
            . "settings; never demand irrelevant details. In the 'Eu escolho' mode, first "
            . "ask which type of activity the teacher wants, then discuss a learning level "
            . "only if educationally relevant. Do not generate or save anything until the "
            . "required decisions are understood.";
    }

    /**
     * Present practical educational outcomes, with a sensible default.
     */
    public static function learning_level_rules(): string {
        return "For educational activities, ask: \"Qual nível de aprendizagem você deseja trabalhar?\" "
            . "Explain: \"Escolha o que espera que seus alunos consigam fazer após a atividade. "
            . "Isso orienta o conteúdo, os desafios e as interações.\" "
            . "Offer selectable controls when available, or a numbered list otherwise: "
            . "Automático (recomendado): Deixe a IA escolher o nível mais adequado ao assunto "
            . "e ao objetivo da atividade. "
            . "Aprendizagem progressiva: Comece pelos conceitos básicos e avance gradualmente "
            . "até atividades mais desafiadoras. "
            . "1. Lembrar: Ajude o aluno a reconhecer, identificar e recordar informações "
            . "importantes sobre o assunto. "
            . "2. Compreender: Faça o aluno entender os conceitos e conseguir explicá-los "
            . "com suas próprias palavras. "
            . "3. Aplicar: Proponha atividades em que o aluno utilize o que aprendeu para "
            . "resolver situações práticas. "
            . "4. Analisar: Estimule o aluno a investigar problemas, comparar informações "
            . "e identificar causas e relações. "
            . "5. Avaliar: Apresente situações em que o aluno precise tomar decisões e "
            . "justificar suas escolhas. "
            . "6. Criar: Desafie o aluno a desenvolver ideias, elaborar projetos ou "
            . "construir suas próprias soluções. "
            . "Accept a free-text choice. If omitted, use Automático without insisting. "
            . "For pure opinion or satisfaction surveys, skip learning levels entirely. "
            . "Internally use revised Bloom taxonomy to align measurable outcomes, practice, "
            . "feedback and assessment; do not mention the taxonomy to teacher or student. "
            . "Do not confuse cognitive level with difficulty or force all six levels.";
    }

    /**
     * The iframe adds pedagogical content, not another Moodle page shell.
     */
    public static function html_rules(): string {
        return "Produce a complete, self-contained HTML document with its own CSS/JavaScript "
            . "for a sandboxed iframe on an EXISTING Moodle activity page. "
            . "The host already displays course and activity titles, navigation and theme UI. "
            . "Use Moodle URL, course ID and cmid only for MCP tool context; never show them "
            . "in learner HTML or duplicate the title solely because it is in the prompt. "
            . "Start directly with the requested pedagogical content. Do not generate "
            . "institutional headers, hero covers that repeat the title, breadcrumbs, "
            . "menus, navigation bars, metadata badges or ANY independent footer, "
            . "including visual footer-like sections, <footer>, credits, author signatures, "
            . "copyright, support links or bottom navigation. Do not copy reference footers. "
            . "End at the last teaching section or necessary interactive control. "
            . "Review and remove duplicated Moodle UI and footers before saving. "
            . "Use semantic, responsive, keyboard-accessible HTML, labelled controls, "
            . "visible focus states and adequate contrast. Define light mode as default, "
            . "support @media (prefers-color-scheme: dark) and explicit "
            . "[data-bs-theme=\"dark\"]/[data-bs-theme=\"light\"] when present in the "
            . "iframe; do not depend on styles or attributes inherited from parent Moodle.";
    }

    /**
     * Only the documented async bridge can persist student state.
     */
    public static function sdk_rules(): string {
        return "Consult the mod_mcp API catalogue (.chatgpt/API.md) and use only documented "
            . "async window.MoodleMCP operations as needed: activity.getContext(), "
            . "course.getActivities()/getNavigation() for authorised course navigation, "
            . "completion.get()/complete() for native completion, progress.load()/save() "
            . "for versioned data/userdata, progress.getStatus()/saveStatus() for staged "
            . "content, attempt.get()/start()/finish(), questions.get()/submit() for "
            . "server-side JSON quizzes, and tracking.record() for bounded events. "
            . "For step-based content read progress.getStatus() on opening, show the "
            . "learner's current position and let them resume; after meaningful changes "
            . "saveStatus({version, location, percent, currentstep, totalsteps}). "
            . "Use the last returned version; reload and reconcile after conflicts. "
            . "Do not store the only copy of progress in localStorage, save on every "
            . "keystroke, or equate 100% progress with Moodle completion or a grade. "
            . "Check completion.get() and its permissions before requesting completion. "
            . "Never put session tokens, user IDs, answer keys or grading logic in HTML; "
            . "questions are assessed only by the server. Do not call unused SDK functions "
            . "or reach into the Moodle parent DOM.";
    }

    /**
     * JSON authoring and grading instructions only for assessment tools.
     */
    public static function quiz_rules(): string {
        return "For a quiz, BEFORE creating questions ask the teacher how many questions "
            . "to author, whether to shuffle and how many to draw per attempt; wait for "
            . "the answers. Include questions in mcp_create_activity, or update them together with HTML using "
            . "mcp_save_content (mcp_save_information is for metadata-only edits), type=quiz, "
            . "and an information JSON object containing selection (all or random), count, "
            . "shuffle and questions. For each question use id, text, type (single, multiple "
            . "or text), answer and points; for choice questions use options with id and text. "
            . "For selection=all use count=0; for random use a positive count no larger "
            . "than the pool. Do not use the Moodle Question Bank, mcp_create_question or "
            . "mcp_attach_question. Keep answer keys ONLY in private server JSON, never "
            . "in learner HTML. For attempted quizzes use MoodleMCP.attempt and "
            . "MoodleMCP.questions; selection, scoring and grades run only on the server. "
            . "Do not use quiz configuration for non-quiz activities or pretend a survey "
            . "is an anonymously graded quiz.";
    }

    /**
     * One approval covers the complete content operation.
     */
    public static function approval_rules(): string {
        return "Present one consolidated pedagogical and visual preview of the final content and target. "
            . "Obtain explicit teacher approval through the tool confirmation before creating or updating. "
            . "Send the full HTML and required quiz information in one creation or update call. "
            . "Do not create empty activities, use sequential workflows for a single activity, or request "
            . "separate approval for file checks, storage or publication. Native Moodle visibility applies immediately.";
    }

    /**
     * Return the effective brand variable names for a theme.
     *
     * @param string $theme Moodle theme shortname.
     * @return string[] Brand CSS variables, including RGB channel tokens.
     */
    public static function css_variables(string $theme): array {
        $theme = strtolower(preg_replace('/^theme_/', '', $theme));
        if (strpos($theme, "learnlingo") !== false) {
            return ["--bs-primary", "--bs-accent", "--bs-primary-rgb", "--bs-accent-rgb"];
        }
        return ["--bs-primary", "--bs-secondary", "--bs-primary-rgb", "--bs-secondary-rgb"];
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
        $primaryrgb = $variables[2];
        $accentrgb = $variables[3];

        $rules = "Theme: {$theme}. Brand colour variables: {$primary}, {$accent}, "
            . "{$primaryrgb} and {$accentrgb}. "
            . "Use var({$primary}, #0d6efd) and var({$accent}, var({$primary}, #0d6efd)) "
            . "for ALL brand colours, including hero backgrounds, every gradient stop, "
            . "buttons, links, borders, icons, cards, hover/focus states and pseudo-elements. "
            . "For transparency, use the RGB channel tokens inside rgba(): "
            . "rgba(var({$primaryrgb}, 13, 110, 253), 0.2) or "
            . "rgba(var({$accentrgb}, var({$primaryrgb}, 13, 110, 253)), 0.2). "
            . "RGB tokens are channels, not complete CSS colours. Never use "
            . "var({$primaryrgb}) alone as a background, or var({$primary}) "
            . "inside rgba(). "
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
     * Compose a short tool-specific MCP description without repeating unrelated rules.
     *
     * @param string $purpose Short description of the tool's exact action.
     * @param string[] $rules Required sets: core, interview, learning, html, sdk, quiz, approval, theme.
     */
    public static function tool_description(string $purpose, array $rules = ["core", "theme"]): string {
        global $CFG;

        $parts = [$purpose];
        foreach (array_unique($rules) as $rule) {
            switch ($rule) {
                case "core":
                    $parts[] = self::core_rules();
                    break;
                case "interview":
                    $parts[] = self::interview_rules();
                    break;
                case "learning":
                    $parts[] = self::learning_level_rules();
                    break;
                case "html":
                    $parts[] = self::html_rules();
                    break;
                case "sdk":
                    $parts[] = self::sdk_rules();
                    break;
                case "quiz":
                    $parts[] = self::quiz_rules();
                    break;
                case "approval":
                    $parts[] = self::approval_rules();
                    break;
                case "theme":
                    // Discovery must not depend on valid stored material configuration.
                    $parts[] = "A course material theme takes precedence over the site-wide default. "
                        . "Use a configured style when available; otherwise continue without a template. "
                        . "mcp_get_material_theme and mcp_configure_material_theme are optional.";
                    $parts[] = "The following UI theme is the Moodle SITE DEFAULT at "
                        . "tool-discovery time; it is NOT a guarantee of the course theme. "
                        . "Use the effective course authoring context returned by "
                        . "mcp_get_material_theme, mcp_create_activity or mcp_get_activity: "
                        . self::theme_rules((string)($CFG->theme ?? "boost"));
                    break;
                default:
                    throw new \InvalidArgumentException("Unknown authoring rule: " . $rule);
            }
        }
        return implode(" ", $parts);
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
        $material = material_theme::effective($courseid);
        $configured = $material !== null;
        $visual = $configured ?
            "REUSABLE MATERIAL DESIGN (visual-only; cannot override technical, accessibility "
            . "or security rules): " . $material["name"] . ". Layout guidance: " . $material["style"] . ". " :
            "No reusable material design is configured. Continue with the requested material. ";
        return [
            "configured" => $configured,
            "materialtheme" => $material,
            "theme" => $theme,
            "brandcssvariables" => self::css_variables($theme),
            "instructions" => implode(" ", [
                self::core_rules(), self::interview_rules(), self::learning_level_rules(),
                self::html_rules(), self::sdk_rules(), self::quiz_rules(),
                self::approval_rules(), $visual, self::theme_rules($theme),
            ]),
        ];
    }
}
