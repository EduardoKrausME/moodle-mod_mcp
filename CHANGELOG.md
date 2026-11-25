# Changelog

This file documents verified published GitHub releases. The initial release is described from its published tag and tagged source or release notes; there is no earlier published GitHub release to compare.

## Unreleased

- Normalize native description format to integer for creation and updates, retaining strict persisted-settings verification and rollback. Cover legacy and explicit completion settings in enabled and disabled courses; fix Code Checker findings.

- Cover legacy creation defaults, initial completion validation and preservation of academic fields during native description edits.

- Fix native description creation being overwritten by the core intro editor; verify persisted initial completion and description before commit. Correct trusted Mustache URL output and extend course-page description tests.

- Add native plain-text descriptions and optional view/manual completion on creation; update descriptions independently with optimistic concurrency. Display descriptions on the course page and preserve authored revisions and academic data.

- Preserve authored HTML, CSS, JavaScript, Unicode and whitespace byte for byte in immutable revisions. Preview and save return the exact size and SHA-256; manager reads return full HTML and a separate plaintext extract. Requires local_mcp build 2026112405. Added end-to-end source integrity regression coverage.

- Added optional aftercmid and beforecmid to mcp_create_activity, using the shared local_mcp position service with prevalidation and transactional creation.
- Added regression coverage for exact placement, hidden state, invalid anchors and backward-compatible creation; requires local_mcp build 2026112401.

- Return actionable structured errors for missing or malformed material themes, revision conflicts and known authoring validation failures while retaining native UI exceptions.
- Keep tool discovery independent of stored material theme validity and allow authorized, confirmed replacement of malformed theme definitions.
- Added regression coverage for read-only previews, hidden creation, template precedence, recovery and safe error mapping. Deploy with the updated local_mcp and refresh MCP tool discovery.

## 1.2.2 - 2026-11-24

- Added reusable site and course material themes, with configuration tools and a management page; content authoring requires an effective material theme.
- Enforced theme colour variables for supported theme families, including primary/accent RGB tokens in the sandbox bridge, and rejected hard-coded brand colours when saving content.
- Scoped authoring guidance to individual MCP operations and expanded localized authoring instructions.
- Added click-to-select-and-copy prompts and floating copy confirmation, with interaction tests.
- Expanded and audited the 15 Portuguese interactive custom examples and their regression tests.

**Comparison:** [1.2.1...1.2.2](https://github.com/EduardoKrausME/moodle-mod_mcp/compare/1.2.1...1.2.2)

## 1.2.1 - 2026-11-23

- Introduced activity type and JSON information fields, JSON-backed quiz question configuration and user assessment state, replacing the previous native question-bank authoring tools.
- Added student runtime API and state services, Moodle web services, and SDK integration for embedded interactive content.
- Expanded the teacher authoring documentation with game, timeline and custom interactive activity examples and prompts.

**Comparison:** [1.0.1...1.2.1](https://github.com/EduardoKrausME/moodle-mod_mcp/compare/1.0.1...1.2.1)

## 1.0.1 - 2026-11-21

- Fixed hidden draft handling, preview completion and concurrent assessment start behavior.
- Added Moodle legacy question-bank adapters and compatibility CI coverage.
- Added an interactive-versus-content authoring prompt selector and Moodle MCP connection guidance on the activity page.

**Comparison:** [1.0.0...1.0.1](https://github.com/EduardoKrausME/moodle-mod_mcp/compare/1.0.0...1.0.1)

## 1.0.0 - 2026-11-20

- Initial published MCP Moodle activity supporting HTML, CSS and JavaScript content in a sandboxed iframe, with explicit draft preview and publication.
- Included revision-aware content editing and integration with ChatGPT or Claude through the optional local MCP authoring tools.
- Added native Moodle question-bank and Question Engine integration for graded assessments, with pinned question versions and preservation of historical attempts.
- Used Moodle capabilities and the File API for activity access and uploaded assets.

**Tagged source:** [1.0.0](https://github.com/EduardoKrausME/moodle-mod_mcp/releases/tag/1.0.0)
