// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.
'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const test = require('node:test');
const path = require('node:path');

const sdk = fs.readFileSync(path.join(__dirname, '../../sdk.js'), 'utf8');
const bridge = fs.readFileSync(path.join(__dirname, '../../amd/src/bridge.js'), 'utf8');

test('sandbox SDK keeps credentials outside the iframe and matches parent messages', async() => {
    const events = {};
    const calls = [];
    const parent = {
        postMessage: (message) => calls.push(message)
    };
    const window = {
        parent,
        addEventListener: (type, callback) => { events[type] = callback; }
    };
    vm.runInNewContext(sdk, {window, setTimeout, clearTimeout, Map, Math, Object, Error, String});
    assert.equal(window.MoodleMCP.sesskey, undefined);
    assert.equal(typeof window.MoodleMCP.course.getNavigation, 'function');
    const result = window.MoodleMCP.progress.load();
    assert.equal(calls[0].action, 'progress.load');
    events.message({
        source: {},
        data: {channel: 'mod_mcp', type: 'response', id: calls[0].id, ok: true, result: {bad: true}}
    });
    events.message({
        source: parent,
        data: {channel: 'mod_mcp', type: 'response', id: calls[0].id, ok: true, result: {version: 3}}
    });
    assert.equal((await result).version, 3);
});

test('parent bridge rejects messages from another window and never trusts requested cmid', async() => {
    const events = {};
    const requests = [];
    const responses = [];
    const child = {postMessage: (data) => responses.push(data)};
    const frame = {contentWindow: child, isConnected: true};
    const window = {addEventListener: (name, fn) => {events[name] = fn;}};
    const document = {querySelector: () => frame};
    let exposed;
    const define = (dependencies, loader) => {
        exposed = loader({call: (calls) => {
            requests.push(calls[0]);
            return [Promise.resolve({json: '{"ok":true}'})];
        }});
    };
    vm.runInNewContext(bridge, {define, window, document, Set, JSON, Object, Number, Boolean, String});
    exposed.init({cmid: 27, preview: false});
    const message = {
        channel: 'mod_mcp', type: 'request', id: 'mcp-1-abc', action: 'completion.get',
        payload: {cmid: 999, userid: 999}
    };
    events.message({source: {}, data: message});
    assert.equal(requests.length, 0);
    events.message({source: child, data: message});
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].args.cmid, 27);
    assert.equal(requests[0].args.preview, false);
    assert.equal(responses[0].ok, true);
    events.message({source: child, data: {...message, action: 'grade.update'}});
    assert.equal(requests.length, 1);
});
