# mod_mcp - API catalogue

Canonical reference for the **student runtime SDK**, Moodle **web services**, and
teacher **MCP authoring tools**. The ChatGPT/Claude authoring prompt points to
this file. Implementations live in `sdk.js`, `classes/runtime/api.php`,
`classes/runtime/state.php`, `db/services.php` and `classes/mcp/provider.php`.

## 1. Student SDK: window.MoodleMCP

Published HTML receives the `window.MoodleMCP` JavaScript SDK automatically.
Every operation returns a **Promise**. The document is rendered in a sandboxed
iframe: it has no Moodle cookies, session key, native gradebook access or direct
cross-origin access. The parent bridge accepts only allowlisted requests from
the current iframe; the server resolves the user from the Moodle session and
the activity from the trusted course-module context. **Do not pass user IDs
or course-module IDs** from HTML, and do not call these SDK functions from an
unrelated page.

| Method | Parameters | Returns / behavior |
| --- | --- | --- |
| `activity.getContext()` | None | `{cmid, courseid, language, revision}` |
| `course.getActivities()` | None | `{activities: [{cmid, name, modname, section, url}]}`; Moodle filters visibility |
| `course.getNavigation()` | None | `{previous, next}`; each entry is an activity object or `null` |
| `completion.get()` | None | `{enabled, mode, state, completed, canrequest}` from native Moodle completion |
| `completion.complete()` | None | Requests completion only when allowed; requires `mod/mcp:submit` |
| `progress.load()` | None | `{version, location, data, completed}`; generic per-user resume data |
| `progress.save(state)` | `{version, location, data}` | Saves the generic state; returns updated snapshot; requires submit |
| `progress.getStatus()` | None | `{version, location, percent, currentstep, totalsteps, status, moodlecompleted}` |
| `progress.saveStatus(status)` | `{version, location?, percent, currentstep?, totalsteps?}` | Saves structured position, keeps unrelated generic data and returns updated status; requires submit |
| `attempt.get()` | None | `{hasquestions, status, canstart, canfinish}`; submit capability required |
| `attempt.start()` | None | Starts the single native attempt, selecting its server-controlled question set |
| `attempt.finish()` | None | Finalizes the native attempt through Question Engine |
| `questions.get()` | None | `{attempt, questions}`; safe native question markup for an active attempt |
| `questions.submit(responses, finish)` | `responses` is a map of native question fields to string values; `finish` optional boolean | Submits to Question Engine; does **not** grade in HTML |
| `tracking.record(event)` | `{type, action, object, result}` | Records one bounded interaction, progress or navigation event |
| `tracking.summary()` | None | `{summary: [{type, action, total, participants}]}`; manage capability required |

### Structured progress and activity status

The structured API persists a learner's current location, percentage and step
counts in the existing `mcp_state` record. The `percent` field is an integer
from 0 to 100, `currentstep` and `totalsteps` are integers with
`0 <= currentstep <= totalsteps <= 1000000`; omitted step counts default to
zero. `location` is a short stable identifier (at most 255 bytes), e.g.
`chapter-2/step-3`. When omitted from `saveStatus`, the previous location is
preserved. The `version` is mandatory and must come from the most recent read
or save. The server rejects outdated versions rather than overwriting another
tab's changes. Generic and structured saves share the **same** version.

`status` is `notstarted`, `inprogress`, or `finished` (when the stored
percentage reaches 100). It refers only to the **reading/interaction journey**;
`moodlecompleted` and `completion.get()` report actual Moodle completion.
Saving 100% **never** changes grade, finalizes a question attempt, or bypasses
native completion rules. A newly published content revision resets the reading
position for that revision; activity-wide academic completion is tracked
separately. Changes are scoped to the currently authenticated student.

Example in the authored HTML:

```javascript
async function resumeLesson() {
    const status = await MoodleMCP.progress.getStatus();
    // Render the existing page/step here, using status.location.
    return status;
}

async function saveLessonStep(lastStatus, step, total) {
    try {
        return await MoodleMCP.progress.saveStatus({
            version: lastStatus.version,
            location: "step-" + step,
            percent: Math.round(step * 100 / total),
            currentstep: step,
            totalsteps: total
        });
    } catch (error) {
        // A conflicting update from another tab must be reloaded and reconciled.
        const latest = await MoodleMCP.progress.getStatus();
        throw new Error("Progress changed elsewhere; reload the interface (v" +
            latest.version + ")");
    }
}
```

Update the local `lastStatus` with the returned status after every successful
save. Call these operations after meaningful step transitions rather than on
every scroll, keystroke or animation frame. Do not use `localStorage` as the
only storage for a student's progress. Old content using generic
`progress.load()/save()` remains supported.

`progress.save({version, location, data})` replaces the generic `data` object,
whereas `progress.saveStatus(...)` merges its reserved status record into the
current generic data while enforcing the same version check. Do not write the
reserved `_modmcpstatus` property directly. The full JSON state is limited to
12 KiB. Invalid values and conflicting versions reject the Promise.

### Permissions, assessments and telemetry

`completion.complete()` only works under configured native/manual/custom
completion rules. For activities with native questions, a required attempt
must finish before custom interactive completion is accepted.
`questions.get()` returns only question rendering appropriate for supported
native types; complex question behavior belongs in Moodle's trusted form.
Teachers manage grades and edits through native Moodle APIs, not browser HTML.

Tracking accepts types `interaction`, `progress`, `navigation`, action
names matching `[a-z][a-z0-9_]*` (max 64 chars), object IDs up to 255
bytes, result JSON up to 4 KiB, and up to 2,000 events per student/activity.
`tracking.summary()` exposes aggregate counts to activity managers only.

## 2. Moodle web-service functions

Declared in `db/services.php`:

| Moodle function | Purpose | Parameters |
| --- | --- | --- |
| `mod_mcp_dispatch` | Authenticated allowlisted SDK bridge, also available to the official mobile service | `cmid`, `action`, `payload` (JSON object string), `preview` (optional boolean) |
| `mod_mcp_get_mobile_content` | Read-only published HTML with injected SDK and authorised asset URLs for the official Moodle mobile service | `cmid` |

`mod_mcp_dispatch` returns `{json: "<serialized result>"}`; it checks
login, module visibility and permissions, and limits action names and payload
sizes. It never accepts client-controlled user identity. Preview mode (activity
managers only) permits the read actions
`activity.getContext`, `course.getActivities`, `course.getNavigation`,
`completion.get`, `progress.load`, `progress.getStatus`, `attempt.get`;
writes are denied. Published activity access is required otherwise.

Mobile clients must provide their own trusted bridge for an injected sandbox
SDK (or call the declared web service with their authenticated context).
Fetching mobile content alone does not make `postMessage` work inside
a standalone WebView. Offline writes and synchronization are **not** provided
by these web services.

## 3. Teacher authoring tools via local_mcp

The `mod_mcp\mcp\provider` registers the following tools, discovered by
ChatGPT or Claude (using the MCP protocol). Tool availability and exact JSON
schemas must be read from the connected Moodle's live tool discovery. Do not
invent parameters or invoke writing tools without their required confirmation.

| Tool | Use |
| --- | --- |
| `mcp_create_activity` | Create an activity instance |
| `mcp_get_activity` | Read the instance, configuration and revisions |
| `mcp_save_content` | Save a new immutable HTML draft |
| `mcp_create_question` | Create a supported native question |
| `mcp_attach_question` | Attach an actual native question ID/version |
| `mcp_configure_questions` | Choose all questions or a random subset and optionally shuffle order |
| `mcp_upload_asset` | Add an authorised activity asset |
| `mcp_publish` | Publish an approved draft |

These are *authoring* tools and not methods on `window.MoodleMCP`.
Question selection, grading, native attempt state, visibility and publication
remain under Moodle server control.
