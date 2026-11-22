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
    vm.runInNewContext(sdk, {
        window, setTimeout, clearTimeout, Map, Math, Object, Error, String
    });
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


test("structured progress SDK calls the documented actions without sending a user ID", async() => {
    const events = {};
    const calls = [];
    const parent = {postMessage: (message) => calls.push(message)};
    const window = {
        parent,
        addEventListener: (name, handler) => {events[name] = handler;}
    };
    vm.runInNewContext(sdk, {
        window, setTimeout, clearTimeout, Map, Math, Object, Error, String
    });
    const pending = window.MoodleMCP.progress.getStatus();
    assert.equal(calls[0].action, "progress.getStatus");
    events.message({source: parent, data: {
        channel: "mod_mcp", type: "response", id: calls[0].id,
        ok: true, result: {version: 1, status: "inprogress", percent: 30}
    }});
    const status = await pending;
    const save = window.MoodleMCP.progress.saveStatus({
        version: status.version, location: "chapter-2",
        percent: 50, currentstep: 2, totalsteps: 4
    });
    assert.equal(calls[1].action, "progress.saveStatus");
    assert.equal(calls[1].payload.percent, 50);
    assert.equal(calls[1].payload.userid, undefined);
    events.message({source: parent, data: {
        channel: "mod_mcp", type: "response", id: calls[1].id,
        ok: true, result: {version: 2, status: "inprogress", percent: 50}
    }});
    assert.equal((await save).version, 2);
});


test("iframe navigation starts only after the parent bridge registers its message listener", async() => {
    const template = fs.readFileSync(
        path.join(__dirname, "../../templates/activity.mustache"), "utf8");
    assert.match(template, /src="about:blank" data-src="\{\{\{url_content\}\}\}"/);
    const events = {};
    const calls = [];
    const contenturl = "https://moodle.example/pluginfile.php/123/mod_mcp/content/4/index.html";
    const child = {postMessage: () => {}};
    const frame = {
        dataset: {src: contenturl},
        contentWindow: child,
        isConnected: true,
        style: {height: "650px"},
        addEventListener: () => {},
        set src(url) {
            assert.equal(url, contenturl);
            assert.equal(typeof events.message, "function");
            events.message({source: child, data: {
                channel: "mod_mcp", type: "request", id: "mcp-1-early",
                action: "progress.load", payload: {}
            }});
        }
    };
    const window = {addEventListener: (name, callback) => {events[name] = callback;}};
    const document = {querySelector: () => frame};
    let exposed;
    const define = (dependencies, factory) => {
        exposed = factory({call: (requests) => {
            calls.push(requests[0]);
            return [Promise.resolve({json: '{"version":0,"data":{},"userdata":{}}'})];
        }});
    };
    vm.runInNewContext(bridge, {define, window, document, Set, JSON, Object, Number, Boolean, String});
    exposed.init({cmid: 36, preview: false});
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(calls.length, 1);
    assert.equal(calls[0].args.cmid, 36);
    assert.equal(calls[0].args.action, "progress.load");
    assert.equal(frame.dataset.src, undefined);
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
    events.message({source: child, data: {...message, action: "progress.getStatus"}});
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(requests.length, 2);
    events.message({source: child, data: {...message, action: "progress.saveStatus"}});
    await new Promise((resolve) => setImmediate(resolve));
    assert.equal(requests.length, 3);
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
    vm.runInNewContext(sdk, {
        window, setTimeout, clearTimeout, Map, Math, Object, Error, String, Number
    });
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

    assert.equal(messages[0].type, "resize");
    assert.ok(messages.some((message) => message.type === "ready"));
    const count = messages.length;
    events.message({source: parent, data: {channel: "mod_mcp", type: "measure"}});
    assert.equal(messages.length, count + 1);
    assert.equal(messages.at(-1).type, "resize");
    assert.equal(messages.at(-1).height, 720);
});

test("sandbox SDK shrinks even when html.scrollHeight reflects the old iframe viewport", () => {
    const messages = [];
    let changed;
    const parent = {postMessage: (message) => messages.push(message)};
    const doc = {
        readyState: "complete",
        documentElement: {scrollHeight: 8408, offsetHeight: 8408, clientHeight: 8408},
        body: {scrollHeight: 8408, offsetHeight: 8408},
        addEventListener: () => {}
    };
    const window = {
        parent,
        document: doc,
        innerHeight: 8408,
        addEventListener: () => {},
        requestAnimationFrame: (callback) => callback(),
        ResizeObserver: class {
            constructor(callback) {
                changed = callback;
            }
            observe() {}
        },
        MutationObserver: class {
            observe() {}
        }
    };
    vm.runInNewContext(sdk, {
        window, setTimeout, clearTimeout, Map, Math, Object, Error, String, Number
    });
    assert.equal(messages[0].height, 8408);

    // Browser keeps html.scrollHeight at the iframe's old height while the
    // document's actual content and offset dimensions have already shrunk.
    doc.documentElement.scrollHeight = 8408;
    doc.documentElement.offsetHeight = 7525;
    doc.body.scrollHeight = 7525;
    doc.body.offsetHeight = 7525;
    changed();
    assert.equal(messages.at(-1).type, "resize");
    assert.equal(messages.at(-1).height, 7525);

    // Keep html.scrollHeight when it represents actual overflow, not the viewport.
    doc.documentElement.scrollHeight = 9500;
    changed();
    assert.equal(messages.at(-1).height, 9500);
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
    events.message({source: {}, data: {channel: "mod_mcp", type: "ready"}});
    assert.equal(outgoing.length, 1);
    events.message({source: child, data: {channel: "mod_mcp", type: "ready"}});
    assert.equal(outgoing.length, 2);
    assert.equal(outgoing[1].type, "measure");
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


test("sandbox SDK applies only the trusted parent's theme variables", () => {
    const listeners = {};
    const properties = new Map();
    const attributes = new Map();
    const parent = {postMessage: () => {}};
    const declaration = {
        setProperty: (name, value) => properties.set(name, value),
        removeProperty: (name) => properties.delete(name)
    };
    const document = {
        readyState: "loading",
        documentElement: {
            getAttribute: (name) => attributes.get(name) ?? null,
            setAttribute: (name, value) => attributes.set(name, value),
            removeAttribute: (name) => attributes.delete(name)
        },
        getElementById: (id) => id === "mod-mcp-theme-vars" ?
            {sheet: {cssRules: [{style: declaration}]}} : null,
        addEventListener: () => {}
    };
    const window = {
        parent, document,
        addEventListener: (name, callback) => {listeners[name] = callback;}
    };
    vm.runInNewContext(sdk, {
        window, setTimeout, clearTimeout, Map, Set, Math, Object, Error, String, Number
    });
    const message = {channel: "mod_mcp", type: "theme", mode: "dark", variables: {
        "--bs-primary": "#154f90", "--bs-accent": "rgb(10, 20, 30)",
        "body{color:red}": "bad"
    }};
    listeners.message({source: {}, data: message});
    assert.equal(properties.size, 0);
    listeners.message({source: parent, data: message});
    assert.equal(properties.get("--bs-primary"), "#154f90");
    assert.equal(properties.get("--bs-accent"), "rgb(10, 20, 30)");
    assert.equal(properties.size, 2);
    assert.equal(attributes.get("data-bs-theme"), "dark");

    listeners.message({source: parent, data: {
        channel: "mod_mcp", type: "theme", mode: "light",
        variables: {"--bs-secondary": "#eef0f2"}
    }});
    assert.equal(properties.has("--bs-primary"), false);
    assert.equal(properties.get("--bs-secondary"), "#eef0f2");
    assert.equal(attributes.get("data-bs-theme"), "light");
    listeners.message({source: parent, data: {
        channel: "mod_mcp", type: "theme", mode: null, variables: {}
    }});
    assert.equal(properties.size, 0);
    assert.equal(attributes.has("data-bs-theme"), false);
});

test("parent bridge forwards the computed Moodle theme and refreshes on mode changes", () => {
    const events = {};
    const outgoing = [];
    const observers = [];
    const child = {postMessage: (message) => outgoing.push(message)};
    const parent = {
        mode: "dark",
        parentElement: null,
        getAttribute(name) { return name === "data-bs-theme" ? this.mode : null; }
    };
    const frame = {
        contentWindow: child, isConnected: true, style: {height: "650px"},
        parentElement: parent, addEventListener: () => {}
    };
    const computed = {
        length: 4,
        item: (i) => ["--bs-primary", "color", "--bs-accent", "--bad"][i],
        getPropertyValue: (name) => ({
            "--bs-primary": "#123456",
            "--bs-accent": "#fedcba",
            "--bad": "  "
        })[name] || ""
    };
    const window = {
        addEventListener: (name, cb) => {events[name] = cb;},
        getComputedStyle: () => computed,
        MutationObserver: class {
            constructor(callback) {observers.push(callback);}
            observe() {}
        }
    };
    const document = {querySelector: () => frame};
    let api;
    const define = (dependencies, load) => {api = load({call: () => []});};
    vm.runInNewContext(bridge, {
        define, document, window, Set, JSON, Object, Number, Boolean, String
    });
    api.init({cmid: 22, preview: false});
    const first = outgoing.find((message) => message.type === "theme");
    assert.equal(first.variables["--bs-primary"], "#123456");
    assert.equal(first.variables["--bs-accent"], "#fedcba");
    assert.equal(Object.keys(first.variables).length, 2);
    assert.equal(first.mode, "dark");

    parent.mode = "light";
    observers[0]();
    assert.equal(outgoing.at(-1).mode, "light");
    events.message({source: {}, data: {channel: "mod_mcp", type: "ready"}});
    assert.equal(outgoing.at(-1).type, "theme");
    assert.equal(outgoing.at(-1).mode, "light");
    events.message({source: child, data: {channel: "mod_mcp", type: "ready"}});
    assert.equal(outgoing.at(-1).type, "theme");
});
