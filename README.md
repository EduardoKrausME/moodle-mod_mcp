# Moodle MCP Activity (mod_mcp)

Interactive Moodle activity supporting HTML, CSS and JavaScript in a single document, with native question-bank grading and ChatGPT/Claude authoring through local_mcp.

Compatible with Moodle 4.5 LTS and 5.x. Install this repository at mod/mcp and upgrade Moodle. local_mcp is required for authoring over MCP, but students do not need ChatGPT, Claude or an MCP connection.

## Activity lifecycle

Create an MCP activity using Moodle or \`mcp_create_activity\`. It starts hidden and has an automatically generated ChatGPT/Claude prompt containing the course ID, cmid and URL. Save a complete HTML document using \`mcp_save_content\`, preview with the normal view URL, then publish explicitly through \`mcp_publish\` or the Moodle management page.

All HTML revisions are immutable and checked with an expected revision ID. Published students continue to see the previous published revision while a new draft is in progress.

Custom scripts execute in a sandboxed iframe with allow-scripts but not allow-same-origin; there is no access to the authenticated Moodle DOM, cookies or gradebook from that code. Assets are stored using Moodle File API and fetched with normal permission checks. The upload tool allows safe files up to 8 MiB; use Moodle's normal upload workflow for large videos.

## Native question bank and grades

MCP tools create native multiple-choice questions using the local_mcp question-bank service, returning their real question IDs and versions. Existing ready true/false and short-answer questions can also be attached. The mod_mcp slot table pins concrete question IDs and versions; replacing a question marks only the older slot link inactive for future attempts, preserving prior Question Engine attempts.

Student questions are rendered in the trusted Moodle assessment area separately from the sandboxed HTML, and submission goes to the server. The module uses question_engine for attempt state, persistence and marking, and grade_update for the native gradebook. One attempt per student is currently supported. The JavaScript in authored HTML never supplies grades, answers or gabaritos. For advanced question behavior, grade review and manual grading, use Moodle's existing mod_quiz.

## MCP tools

mcp_create_activity, mcp_get_activity, mcp_save_content, mcp_create_question, mcp_attach_question, mcp_upload_asset and mcp_publish. All write tools use local_mcp confirmation and native capability enforcement. Reconnect or refresh tool discovery after deployment.

## Backup, privacy and testing

Backup/restore includes authored HTML revisions, files and slots; student attempts are intentionally not restored by this initial version. Question references are restored only when the destination backup provides native question ID mappings. Moodle Privacy API exports and removes attempt data and anonymizes HTML revision authorship.

Run the existing GitHub Actions Moodle 4.5/5.x matrix, PHP lint, PHPCS, PHPUnit and Behat before deploying. Review the current CI status; this initial implementation is not production-certified.

## Student runtime API (1.1)

Published HTML documents receive a small \`window.MoodleMCP\` SDK before their own scripts.
The HTML stays inside \`sandbox="allow-scripts"\` (no \`allow-same-origin\`).
A trusted host AMD module accepts messages **only from the matching iframe WindowProxy**,
calls \`mod_mcp_dispatch\` using the Moodle AJAX/session context, and returns the result.
No token, \`sesskey\`, gradebook access or arbitrary external function name enters the iframe.

Example, within the authored document:

\`\`\`javascript
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
\`\`\`

Available APIs:
- \`activity.getContext()\`, \`course.getActivities()\`, \`course.getNavigation()\`;
- \`completion.get()\`, \`completion.complete()\`;
- \`progress.load()\`, \`progress.save({version, location, data})\` with optimistic locking;
- \`attempt.get()\`, \`attempt.start()\`, \`attempt.finish()\`;
- \`questions.get()\`, \`questions.submit(responses, finish)\` for simple native question
  types. Returned question markup comes from the Moodle Question Engine, never from authored HTML.
  Complex question types may require the original trusted Moodle assessment form;
- \`tracking.record({type, action, object, result})\` with a limit of 2,000 events per
  student/activity, and \`tracking.summary()\` restricted to teachers who can manage the activity.

Automatic interactive completion requires the teacher to enable the custom rule in
activity completion settings. Manual completion uses Moodle's own manual completion mode;
native view/grade completion conditions are never bypassed. A graded attempt must be
finished before interactive completion is accepted.

Progress is revision-aware, belongs exclusively to the logged-in user, and is capped
at 12 KiB of JSON. The optional xAPI-inspired telemetry is *not* a grading or evidence
system. Author-supplied JSON is untrusted and never updates the Moodle gradebook.

Neither student progress nor tracking events are copied by course backup/restore.
The Moodle Privacy API exports and deletes these records. Course editing and publishing
remain the responsibility of \`local_mcp\` with explicit teacher confirmation.

The CI matrix targets Moodle 4.5 and Moodle 5.3, PHP lint/PHPCS, PHPUnit and JavaScript
bridge tests. The repository must not be deployed without reviewing CI and completing
integration tests in a real Moodle instance.
