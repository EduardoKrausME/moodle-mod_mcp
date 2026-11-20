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
