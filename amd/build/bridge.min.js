// This file is part of Moodle - http://moodle.org/
// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.

/**
 * Trusted host of the sandbox-to-Moodle bridge. No credentials enter the iframe.
 *
 * @module mod_mcp/bridge
 */
define(["core/ajax"], function(Ajax) {
    "use strict";

    const allowed = new Set([
        "activity.getContext", "course.getActivities", "course.getNavigation",
        "completion.get", "completion.complete", "progress.load", "progress.save",
        "tracking.record", "tracking.summary", "attempt.get", "attempt.start",
        "attempt.finish", "questions.get", "questions.submit"
    ]);

    /**
     * Initialise only for the iframe rendered by this activity.
     *
     * @param {Object} config The trusted PHP-side page configuration.
     */
    const init = (config) => {
        const frame = document.querySelector(".mod-mcp-document[data-cmid=\"" +
            String(config.cmid) + "\"]");
        if (!frame) {
            return;
        }
        window.addEventListener("message", (event) => {
            // The sandboxed srcdoc has an opaque origin. Verify the WindowProxy,
            // not event.origin, which is "null" for this iframe.
            if (event.source !== frame.contentWindow) {
                return;
            }
            const msg = event.data;
            if (!msg || msg.channel !== "mod_mcp" || msg.type !== "request" ||
                    typeof msg.id !== "string" || !/^mcp-[a-zA-Z0-9-]{1,48}$/.test(msg.id) ||
                    !allowed.has(msg.action) || !msg.payload ||
                    typeof msg.payload !== "object" || Array.isArray(msg.payload)) {
                return;
            }
            let payload;
            try {
                payload = JSON.stringify(msg.payload);
                if (payload.length > 16384) {
                    return;
                }
            } catch (e) {
                return;
            }
            // postMessage's targetOrigin is * only because the child is opaque;
            // source is strictly bound to this one iframe at both ends.
            const send = (message) => {
                if (frame.isConnected && event.source === frame.contentWindow) {
                    frame.contentWindow.postMessage(Object.assign({
                        channel: "mod_mcp", type: "response", id: msg.id
                    }, message), "*");
                }
            };
            Ajax.call([{
                methodname: "mod_mcp_dispatch",
                args: {
                    cmid: Number(config.cmid),
                    action: msg.action,
                    payload: payload,
                    preview: Boolean(config.preview)
                }
            }])[0].then((result) => {
                send({ok: true, result: JSON.parse(result.json)});
                return result;
            }).catch((error) => {
                send({
                    ok: false,
                    error: error.errorcode || "requestfailed"
                });
            });
        });
    };

    return {init: init};
});
