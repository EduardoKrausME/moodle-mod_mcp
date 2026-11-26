# mod_mcp - API catalogue

Canonical reference for the **student runtime SDK**, Moodle **web services**, and
teacher **MCP authoring tools**. The ChatGPT/Claude authoring prompt points to
this file. Implementations live in `sdk.js`, `classes/runtime/api.php`,
`classes/runtime/state.php`, `db/services.php` and `classes/mcp/provider.php`.

## 1. Student SDK: window.MoodleMCP

Published HTML receives the `window.MoodleMCP` JavaScript SDK automatically.
In the Moodle browser, the host bridge also copies the computed CSS custom
properties from the iframe element into a dedicated `:root` stylesheet in
the sandbox, prioritising `--bs-primary`, `--bs-accent`, `--bs-primary-rgb`
and `--bs-accent-rgb` when present, followed by other inherited theme variables.
For themes without these tokens, the bridge also transmits `--bs-secondary`
and other computed custom properties. The values follow the active theme and its explicit
light/dark mode; this transfer does not grant DOM access to the parent.
Use `var(--bs-primary, #0d6efd)` in authored CSS. Standalone previews and
mobile hosts without this bridge still need those fallbacks.
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
| `progress.load()` | None | `{version, location, data, userdata, completed}`; generic progress and separate student data |
| `progress.save(state)` | `{version, location, data, userdata?}` | Saves the generic state; returns updated snapshot; requires submit |
| `progress.getStatus()` | None | `{version, location, percent, currentstep, totalsteps, status, moodlecompleted}` |
| `progress.saveStatus(status)` | `{version, location?, percent, currentstep?, totalsteps?}` | Saves structured position, keeps unrelated generic data and returns updated status; requires submit |
| `attempt.get()` | None | `{hasquestions, status, canstart, canfinish}`; submit capability required |
| `attempt.start()` | None | Starts one JSON question attempt with server-side all/random/shuffle selection |
| `attempt.finish()` | None | Server-grades the saved JSON answers and records a Moodle grade |
| `questions.get()` | None | `{attempt, questions}`; safe structured JSON questions and HTML fallback |
| `questions.submit(responses, finish)` | `responses` maps question IDs to option IDs/text/arrays; `finish` optional boolean | Saves JSON answers and, when finished, grades on the server |
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
native completion rules. A new active content revision resets the reading
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
reserved `_modmcpstatus` property directly. The generic `data` state is limited to 12 KiB, and the separate `userdata`
object is limited to 128 KiB. Invalid values and conflicting versions reject the Promise.

### JSON metadata, quiz selection and privacy

`activity.getContext()` additionally returns `type` and `information`.
`type` is a nullable free-form identifier such as `quiz`, `survey`,
`timeline`, `case` or `simulation`; `information` is nullable JSON.

Example private author JSON for `type=quiz` (never put the answer key in HTML):

```json
{
  "selection": "random",
  "count": 2,
  "shuffle": true,
  "questions": [
    {"id":"q1","text":"2 + 2?","type":"single","points":1,
      "options":[{"id":"a","text":"3"},{"id":"b","text":"4"}],"answer":"b"},
    {"id":"q2","text":"Capital of France?","type":"text","points":1,"answer":"Paris"},
    {"id":"q3","text":"Prime numbers?","type":"multiple","points":2,
      "options":[{"id":"a","text":"2"},{"id":"b","text":"4"},{"id":"c","text":"5"}],
      "answer":["a","c"]}
  ]
}
```

`mcp_save_information` saves this document after confirmation; `mcp_configure_questions`
keeps its original arguments but updates `selection`, `count`, `shuffle`
inside the JSON. The published quiz's full question definitions are copied
into a private attempt at start. Neither the student SDK nor
`mod_mcp_get_mobile_content` exposes `answer` or `points`.

`questions.get()` returns structured questions with `id`, `text`,
`type`, `options`, and a server-rendered `html` fallback while an attempt is
in progress. `questions.submit({q1: "b", q3: ["a","c"]}, false)` saves the
answers; `finish=true` grades on the server. `attempt.finish()` grades saved
answers. Only one attempt is supported by default. JSON questions never go
through the Moodle Question Bank.

The versioned `progress.load()` snapshot now includes a separate `userdata`
object; `progress.save({version, location, data, userdata})` persists it
independently of the generic `data` object. Surveys should keep their answers
inside `userdata.survey`. JSON quiz answers also appear in `userdata.quiz`.
This is per-user data and not an anonymous survey.

On mobile, the server preallocates the random selection during the user's
explicit download while online; the app stores only sanitized questions, then
queues offline submissions for authenticated synchronization. The browser
never grades itself, and the correct answer key is never downloaded.

Tracking accepts `interaction`, `progress` or `navigation` events with
bounded payloads; `tracking.summary()` is manager-only.

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
writes are denied. Access to the activity's active content is required otherwise.

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

Tool descriptions include only the rules relevant to each operation: pedagogical
interview and learning levels for creation/reading, iframe HTML/SDK/colour rules
for complete HTML, private quiz JSON rules for metadata, and approval rules for
complete creation and content updates. The complete, course-specific authoring context is returned by
`mcp_get_material_theme`, `mcp_create_activity` and `mcp_get_activity`, avoiding
a full manual in every discovered tool description. A reusable material theme is optional and is not required to create or update an activity. The activity prompt selector links to the template manager in a new window, where teachers can view the site and current course styles and copy a scope-specific template creation prompt. Site editing requires site configuration permission. The existing storage supports one template per scope. Without an MCP connection,
the copy-and-paste prompt still needs the applicable standalone HTML/SDK/theme
rules because no tool discovery or authoring action is available.

| Tool | Use |
| --- | --- |
| `mcp_get_material_theme` | Optionally read the effective course or site material theme and authoring context |
| `mcp_configure_material_theme` | Optionally register a reusable course or site material style after confirmation |
| `mcp_create_activity` | Create a complete activity with required `html`, optional `type` and private `information`; optionally specify `aftercmid` or `beforecmid` in the selected course and section |
| `mcp_get_activity` | Read the active revision, configuration, HTML and plaintext |
| `mcp_save_content` | Replace active HTML using `expectedrevision`; preserve visibility |
| `mcp_save_information` | Set nullable activity type and JSON information, including quiz keys |
| `mcp_configure_questions` | Set all/random/shuffle inside the quiz information JSON |
| `mcp_upload_asset` | Add an authorised activity asset |

These are *authoring* tools and not methods on `window.MoodleMCP`.
Question selection, grading, JSON attempt state, native visibility remain
under Moodle server control.

### Authoring source integrity

`mcp_create_activity` accepts required `courseid`, `sectionnum`, `name`, and `html`, with optional `type` and `information` for private quiz configuration. The server validates the complete content and creates it in a single confirmed write. `mcp_save_content` updates the active immutable revision directly; optionally provide `type`, `information` and `expectedhash` together to update private assessment settings in the same confirmed operation. `mcp_get_activity` returns `revision`, `html`, and `plaintext`; use `revision` as `expectedrevision`. Native visibility is never changed by content edits. Legacy unpublished source is retained in history without automatic activation. No manual file integrity or publication step is required. Use local_mcp build 2026112405 or newer.

`mcp_create_activity` also accepts sanitized HTML `intro`, `showdescription` (defaults to `true`; pass `false` to disable course-page display), `completion` (0/1/2) and `requireview` (automatic view only). `mcp_update_description(cmid, intro, expected_description_sha256, showdescription?)` edits the native description without altering revisions or academic settings. HTML is the fixed internal format for MCP writes; no format argument or result field is exposed. Read `description.sha256` from `mcp_get_activity` first; the WRITE requires explicit preview and confirmation.
