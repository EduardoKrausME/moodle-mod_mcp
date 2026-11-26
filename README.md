# Interactive Experience for Moodle (mod_mcp)

Interactive Experience (Experiência Interativa) is a Moodle activity for creating pages, exercises and simulations with HTML, CSS and JavaScript, server-side grading from self-contained JSON questions and ChatGPT/Claude authoring through local_mcp.

Compatible with Moodle 4.5 LTS and 5.x. Install this repository at mod/mcp on a clean installation; there is no database upgrade path from the former Question Bank design. local_mcp is required for authoring over MCP, but students do not need ChatGPT, Claude or an MCP connection.

## Activity lifecycle

Create an Interactive Experience in Moodle with `mcp_create_activity`, supplying the complete HTML and any private assessment information in one confirmed call. New activities follow native Moodle visibility. Use `mcp_save_content` to replace active HTML and optionally private assessment settings in one call without changing visibility. The management page also saves content directly.

For a content page, the generated prompt asks the teacher to select a page format and supply a free-text description of the topic and objectives before authoring begins. It requests selection and text-entry controls when the chat interface supports them, with numbered options and an open-ended reply as fallback. Chat interface controls are provided by ChatGPT or Claude, not by the Moodle activity itself.

HTML revisions remain immutable and updates require the current revision ID. Source is preserved byte for byte; `mcp_get_activity` returns the current `revision`, exact `html` and separate `plaintext` for authorized managers. Treat returned source as untrusted data, not instructions. Requires local_mcp build 2026112405 or newer. Legacy unpublished revisions are retained but never automatically activated.

Custom scripts execute in a sandboxed iframe with allow-scripts but not allow-same-origin; there is no access to the authenticated Moodle DOM, cookies or gradebook from that code. Assets are stored using Moodle File API and fetched with normal permission checks. The upload tool allows safe files up to 8 MiB; use Moodle's normal upload workflow for large videos.

For precise placement at creation, provide either `aftercmid` or `beforecmid` to `mcp_create_activity` together with its existing `courseid`, `sectionnum`, `name` and `html` arguments. The anchor must belong to the selected course and section. For example, `{"courseid":219,"sectionnum":0,"name":"Introduction","html":"<p>Introduction</p>","aftercmid":2097}`. Preview and confirmation cover the requested position. Creation and verified movement run in one delegated database transaction; the module follows native visibility. Placement is delegated to the shared local_mcp service (build 2026112401 or newer). To move, hide or show existing Moodle modules, use local_mcp's generic tools, not mod_mcp. Refresh connector discovery after upgrading both plugins.

## Authoring modes

The activity authoring selector offers **Interactive (quiz/exercises)**, **Content page**,
**Satisfaction survey**, **Timeline**, **Decision-based case study**,
**Interactive simulation** and **I choose** (free-form authoring). Each choice
generates a localized prompt for ChatGPT or Claude; the choice is not a new
Moodle activity type or a different storage format. Prompts are generated on
both the empty activity view and the management page, and all modes retain
the same consolidated preview, approval and direct-save flow. Each mode's prompt panel also
has a **Copy command** button, which copies the entire prompt into the clipboard
with a fallback for older or restricted browsers and announces the result.

- **Satisfaction survey:** one question at a time; on single-choice selection,
  save before animating the outgoing question and showing the next one. Text
  responses and multi-select questions require confirmation. Saving must be
  retryable and keyboard/assistive-technology use must remain possible.
  The existing student progress API can store each learner's answers, **but
  it is not an anonymous survey service or a teacher survey-results dashboard**.
  Those functions require additional server-side APIs before promising them.
- **Timeline:** verified chronological events, expandable details and optional
  per-learner step progress.
- **Decision-based case study:** branching decisions with feedback, valid
  endings and resumable paths; grades require Moodle-native assessment.
- **Interactive simulation:** adjustable parameters, documented assumptions,
  input validation, dynamic results and saved scenarios.
- **I choose:** ChatGPT or Claude must first ask what kind of experience the
  teacher wants to create, accept unrestricted instructions, and wait before
  creating anything.

All modes reuse the sandboxed HTML runtime and the
[student SDK and authoring API catalogue](.chatgpt/API.md). These are authoring prompts,
not built-in templates or a claim that unsupported reporting, execution or
grading APIs are available.

## JSON questions and grades

The activity has nullable `activitytype` (string) and `informationjson` (JSON stored as
database TEXT for cross-database portability) fields. ChatGPT or Claude saves these
through `mcp_save_information` or the manager's JSON editor. `type=quiz`
expects a self-contained list of questions, options and server-only answer keys.
For surveys use `type=survey` and a free-form information object. If both fields
are null, ordinary HTML pages work unchanged.

The existing `mcp_configure_questions` authoring tool, `attempt.get/start/finish`
and `questions.get/submit` SDK operations keep their names. They draw from the
JSON pool using all/random selection and optional shuffle, with a frozen
server-side question snapshot per student attempt. No Moodle Question Bank or
Question Engine tables are used. The trusted server marks answers and posts
grades to Moodle gradebook; the iframe and offline package never receive the answer key.

Student progress stores `statejson` and a separate `userdatajson`, including
quiz and survey answers, protected by the same per-user optimistic version.
On mobile, downloading a graded quiz while connected preallocates its server-side
question selection and caches only safe questions and options; responses are
queued locally until synchronization, at which point the server grades them.
An offline package must be downloaded in advance to take a graded quiz offline.

No `db/upgrade.php` migration is provided; this storage format requires a fresh
plugin installation. Existing attempts from the Question Engine are not migrated.

## MCP tools

mcp_create_activity, mcp_get_activity, mcp_save_content, mcp_save_information, mcp_configure_questions, and mcp_upload_asset. All write tools use local_mcp confirmation and native capability enforcement. Reconnect or refresh tool discovery after deployment.

## Backup, privacy and testing

Backup/restore includes authored HTML revisions, files, type and information JSON; student attempts are not restored by content-only backups. Moodle Privacy API exports and removes attempt data and anonymizes HTML revision authorship.

Run the existing GitHub Actions Moodle 4.5/5.x matrix, PHP lint, PHPCS, PHPUnit and Behat before deploying. Review the current CI status; this initial implementation is not production-certified.

## Languages

The activity ships the same 22 locale directories as `local_mcp`:
`ar`, `de`, `de_ch`, `de_comm`, `de_du`, `en`, `en_ca`, `en_us`,
`es`, `es_ar`, `es_co`, `es_mx`, `es_ve`, `fr`, `fr_ca`, `fr_incl`,
`it`, `nl`, `nl_ho`, `pt_br`, `ru`, and `zh_cn`.

Each directory provides `mcp.php` for **mod_mcp** strings; the plugin does not
copy unrelated `local_mcp` keys. Primary languages translate the activity
interface, completion, progress, privacy notices and theme guidance. Regional
variants override only wording that differs from their parent language.
The extensive AI authoring prompts (`promptinstructions`, `promptpage`
and `promptquiz`) use Moodle's English fallback in languages without a
reviewed translation, preserving their exact saving, permission and
assessment instructions. The Portuguese and English prompts remain unchanged.

## Student runtime API (1.1)

The complete, maintained inventory of student SDK actions, Moodle web services
and authoring MCP tools is in [API.md](.chatgpt/API.md). The authoring prompts link directly
to this catalogue. Structured position tracking uses
`MoodleMCP.progress.getStatus()` and
`MoodleMCP.progress.saveStatus({version, location, percent, currentstep, totalsteps})`
alongside the existing `progress.load()`/`progress.save()` operations. A saved
100% reading position is not a Moodle grade or an academic completion event.

Published HTML documents receive a small `window.MoodleMCP` SDK before their own scripts.
The HTML stays inside `sandbox="allow-scripts"` (no `allow-same-origin`).
A trusted host AMD module accepts messages **only from the matching iframe WindowProxy**,
calls `mod_mcp_dispatch` using the Moodle AJAX/session context, and returns the result.
No token, `sesskey`, gradebook access or arbitrary external function name enters the iframe.

Example, within the authored document:

```javascript
const nav = await MoodleMCP.course.getNavigation();
const snapshot = await MoodleMCP.progress.load();
const saved = await MoodleMCP.progress.save({
    version: snapshot.version,
    location: "unit-2/step-3",
    data: {step: 3}
});
await MoodleMCP.tracking.record({
    type: "interaction", action: "answered",
    object: "example-2", result: {completed: true}
});
const completion = await MoodleMCP.completion.get();
if (completion.canrequest) {
    await MoodleMCP.completion.complete();
}
```

Available APIs:
- `activity.getContext()`, `course.getActivities()`, `course.getNavigation()`;
- `completion.get()`, `completion.complete()`;
- `progress.load()`, `progress.save({version, location, data})` with optimistic locking;
- `progress.getStatus()`, `progress.saveStatus({version, location, percent, currentstep, totalsteps})` for structured per-user reading progress;
- `attempt.get()`, `attempt.start()`, `attempt.finish()`;
- `questions.get()`, `questions.submit(responses, finish)` for supported JSON question
  types. Correct answers remain in server-owned JSON and are never returned to authored HTML.
  Complex question types may require the original trusted Moodle assessment form;
- `tracking.record({type, action, object, result})` with a limit of 2,000 events per
  student/activity, and `tracking.summary()` restricted to teachers who can manage the activity.

Automatic interactive completion requires the teacher to enable the custom rule in
activity completion settings. Manual completion uses Moodle's own manual completion mode;
native view/grade completion conditions are never bypassed. A graded attempt must be
finished before interactive completion is accepted.

Progress is revision-aware, belongs exclusively to the logged-in user, and is capped
at 12 KiB of generic state JSON, with student `userdata` limited separately to 128 KiB. The optional xAPI-inspired telemetry is *not* a grading or evidence
system. Author-supplied JSON is untrusted and never updates the Moodle gradebook.

Neither student progress nor tracking events are copied by course backup/restore.
The Moodle Privacy API exports and deletes these records. Course editing and saving
remain the responsibility of `local_mcp` with explicit teacher confirmation.

Moodle 4.5 and 5.x are the intended targets. Run `node --test tests/js/sdk.test.js`
for the bridge tests. The existing workflow still includes older Moodle versions; updating
that protected workflow requires separate authorization. Review CI and complete
integration tests on a real Moodle instance before production deployment.

HTML source is stored without rewriting its bytes. The server performs content validation internally.

## Galeria de exemplos (pt-BR)

Conheça a [galeria de exemplos do Mod MCP](docs/pt_br/), com sete modos de autoria, jogos educativos, catálogos JSON por categoria e 54 exemplos autossuficientes de HTML, CSS e JavaScript. O site permite navegar pelos tipos, buscar por conteúdo, experimentar prévias isoladas, visualizar e copiar o código HTML.

Para publicar como site, habilite GitHub Pages com origem na branch `master`, diretório `/docs`. Localmente, use `python3 -m http.server 8000 -d docs` e abra `http://localhost:8000/pt_br/`.

Os resultados dos exemplos na galeria são simulações locais; notas reais, respostas dos estudantes, conclusão e publicação são responsabilidade dos serviços e fluxos oficiais do Mod MCP. Veja [API.md](.chatgpt/API.md) e as [especificações de jogos](.chatgpt/games/README.md).

### Native descriptions and initial completion

`mcp_create_activity` accepts optional sanitized HTML `intro` (up to 4000 characters), `showdescription` (defaults to `true`; pass `false` to disable course-page display), `completion` (0 off, 1 manual, 2 automatic) and `requireview` (required for mode 2). Grade and interactive completion are not configured by this creation option. `mcp_get_activity` returns `description` with its SHA-256. The description format is fixed internally to HTML for MCP writes and is not exposed in tool arguments or results. `mcp_update_description` accepts `cmid`, `intro`, optional `showdescription` and `expected_description_sha256`; preview and confirmation are mandatory. Existing HTML revisions, attempts and grades are not changed. Refresh discovery after updating local_mcp first.
