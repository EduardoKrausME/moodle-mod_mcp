// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.
"use strict";

const assert = require("node:assert/strict");
const fs = require("node:fs");
const vm = require("node:vm");
const test = require("node:test");
const path = require("node:path");

const sdk = fs.readFileSync(path.join(__dirname, "../../sdk.js"), "utf8");
const bridge = fs.readFileSync(path.join(__dirname, "../../amd/src/bridge.js"), "utf8");

test("sandbox SDK keeps credentials outside the iframe and matches parent messages", async() => {
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
    assert.equal(typeof window.MoodleMCP.course.getNavigation, "function");
    const result = window.MoodleMCP.progress.load();
    assert.equal(calls[0].action, "progress.load");
    events.message({
        source: {},
        data: {channel: "mod_mcp", type: "response", id: calls[0].id, ok: true, result: {bad: true}}
    });
    events.message({
        source: parent,
        data: {channel: "mod_mcp", type: "response", id: calls[0].id, ok: true, result: {version: 3}}
    });
    assert.equal((await result).version, 3);
});

test("parent bridge rejects messages from another window and never trusts requested cmid", async() => {
    const events = {};
    const requests = [];
    const responses = [];
    const child = {postMessage: (data) => responses.push(data)};
    const frame = {
        contentWindow: child, isConnected: true, style: {height: "650px"},
        addEventListener: () => {}
    };
    const window = {addEventListener: (name, fn) => {events[name] = fn;}};
    const document = {querySelector: () => frame};
    let exposed;
    const define = (dependencies, loader) => {
        exposed = loader({call: (calls) => {
            requests.push(calls[0]);
            return [Promise.resolve({json: "{\"ok\":true}"})];
        }});
    };
    vm.runInNewContext(bridge, {define, window, document, Set, JSON, Object, Number, Boolean, String});
    exposed.init({cmid: 27, preview: false});
    const message = {
        channel: "mod_mcp", type: "request", id: "mcp-1-abc", action: "completion.get",
        payload: {cmid: 999, userid: 999}
    };
    events.message({source: {}, data: message});
    assert.equal(requests.length, 0);
    events.message({source: child, data: message});
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].args.cmid, 27);
    assert.equal(requests[0].args.preview, false);
    assert.equal(responses.find((message) => message.type === "response").ok, true);
    events.message({source: child, data: {...message, action: "grade.update"}});
    assert.equal(requests.length, 1);
});


test("sandbox SDK tracks the real height, including later growth and shrinkage", () => {
    const events = {};
    const messages = [];
    let resizeObserver;
    let mutationObserver;
    const parent = {postMessage: (message) => messages.push(message)};
    const doc = {
        readyState: "complete",
        documentElement: {scrollHeight: 857, offsetHeight: 857},
        body: {scrollHeight: 857, offsetHeight: 857},
        addEventListener: () => {}
    };
    const window = {
        parent,
        document: doc,
        addEventListener: (name, fn) => {events[name] = fn;},
        requestAnimationFrame: (callback) => callback(),
        ResizeObserver: class {
            constructor(callback) {
                resizeObserver = callback;
            }
            observe() {}
        },
        MutationObserver: class {
            constructor(callback) {
                mutationObserver = callback;
            }
            observe() {}
        }
    };
    vm.runInNewContext(sdk, {window, setTimeout, clearTimeout, Map, Math, Object, Error, String, Number});
    assert.equal(messages[0].type, "resize");
    assert.equal(messages[0].height, 857);

    doc.body.scrollHeight = 912;
    doc.body.offsetHeight = 912;
    doc.documentElement.scrollHeight = 912;
    doc.documentElement.offsetHeight = 912;
    resizeObserver();
    assert.equal(messages.at(-1).height, 912);

    doc.body.scrollHeight = 720;
    doc.body.offsetHeight = 720;
    doc.documentElement.scrollHeight = 720;
    doc.documentElement.offsetHeight = 720;
    mutationObserver();
    assert.equal(messages.at(-1).height, 720);

    const count = messages.length;
    events.message({source: parent, data: {channel: "mod_mcp", type: "measure"}});
    assert.equal(messages.length, count);
});

test("parent bridge resizes only its own iframe on valid measurements", () => {
    const events = {};
    const outgoing = [];
    const child = {postMessage: (message) => outgoing.push(message)};
    const frame = {
        contentWindow: child, isConnected: true, style: {height: "650px"},
        addEventListener: (name, fn) => {events[name] = fn;}
    };
    const window = {addEventListener: (name, fn) => {events[name] = fn;}};
    const document = {querySelector: () => frame};
    let exposed;
    const define = (dependencies, loader) => {
        exposed = loader({call: () => {throw new Error("Height changes must not use AJAX");}});
    };
    vm.runInNewContext(bridge, {define, window, document, Set, JSON, Object, Number, Boolean, String});
    exposed.init({cmid: 27, preview: false});
    assert.equal(outgoing[0].type, "measure");
    const data = {channel: "mod_mcp", type: "resize", height: 857};
    events.message({source: {}, data});
    assert.equal(frame.style.height, "650px");
    events.message({source: child, data: {...data, height: "857"}});
    events.message({source: child, data: {...data, height: -50}});
    events.message({source: child, data: {...data, height: 100001}});
    assert.equal(frame.style.height, "650px");
    events.message({source: child, data});
    assert.equal(frame.style.height, "857px");
    events.message({source: child, data: {...data, height: 720}});
    assert.equal(frame.style.height, "720px");
    events.load();
    assert.equal(outgoing.at(-1).type, "measure");
});
