// This file is part of Moodle - http://moodle.org/
// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.

/**
 * Small, dependency-free SDK running inside sandbox="allow-scripts".
 * The trusted parent owns AJAX, session, sesskey and authorization checks.
 */
(function(window) {
    'use strict';

    if (Object.prototype.hasOwnProperty.call(window, 'MoodleMCP')) {
        return;
    }

    const pending = new Map();
    let sequence = 0;

    window.addEventListener('message', function(event) {
        if (event.source !== window.parent) {
            return;
        }
        const msg = event.data;
        if (!msg || msg.channel !== 'mod_mcp' || msg.type !== 'response' ||
                typeof msg.id !== 'string') {
            return;
        }
        const request = pending.get(msg.id);
        if (!request) {
            return;
        }
        pending.delete(msg.id);
        clearTimeout(request.timer);
        if (msg.ok) {
            request.resolve(msg.result);
        } else {
            request.reject(new Error(String(msg.error || 'requestfailed')));
        }
    });

    /**
     * Dispatch a single operation. This identifier is for matching only;
     * it is not a token and is never used as proof of authorization.
     *
     * @param {string} action Allowlisted Moodle action.
     * @param {Object} payload Optional argument object.
     * @returns {Promise<Object>} Validated server result.
     */
    const request = function(action, payload = {}) {
        return new Promise(function(resolve, reject) {
            if (window.parent === window) {
                reject(new Error('MoodleMCP requires an embedded Moodle activity'));
                return;
            }
            const id = 'mcp-' + (++sequence).toString(36) + '-' +
                Math.random().toString(36).slice(2, 12);
            const timer = setTimeout(function() {
                pending.delete(id);
                reject(new Error('MoodleMCP request timed out'));
            }, 20000);
            pending.set(id, {resolve: resolve, reject: reject, timer: timer});
            window.parent.postMessage({
                channel: 'mod_mcp', type: 'request', id: id,
                action: action, payload: payload
            }, '*');
        });
    };

    const api = {
        activity: Object.freeze({getContext: () => request('activity.getContext')}),
        course: Object.freeze({
            getActivities: () => request('course.getActivities'),
            getNavigation: () => request('course.getNavigation')
        }),
        completion: Object.freeze({
            get: () => request('completion.get'),
            complete: () => request('completion.complete')
        }),
        progress: Object.freeze({
            load: () => request('progress.load'),
            save: (state) => request('progress.save', state)
        }),
        attempt: Object.freeze({
            get: () => request('attempt.get'),
            start: () => request('attempt.start'),
            finish: () => request('attempt.finish')
        }),
        questions: Object.freeze({
            get: () => request('questions.get'),
            submit: (responses, finish = false) =>
                request('questions.submit', {responses: responses, finish: finish})
        }),
        tracking: Object.freeze({
            record: (event) => request('tracking.record', event),
            summary: () => request('tracking.summary')
        })
    };
    Object.defineProperty(window, 'MoodleMCP', {
        value: Object.freeze(api), writable: false, configurable: false
    });
})(window);
