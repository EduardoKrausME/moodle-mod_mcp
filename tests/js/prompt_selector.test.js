// Copyright 2026 Eduardo Kraus. GNU GPL v3 or later.
"use strict";

const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const vm = require("node:vm");
const test = require("node:test");

const template = fs.readFileSync(
    path.join(__dirname, "../../templates/prompt_selector.mustache"), "utf8");
const style = fs.readFileSync(path.join(__dirname, "../../styles.css"), "utf8");
const script = template.match(/\{\{#js\}\}([\s\S]*?)\{\{\/js\}\}/)[1]
    .replace(/\{\{cmid\}\}/g, "27");

function createFixture(options = {}) {
    const data = {copied: [], selected: 0, exec: 0, toasts: [], timers: []};
    const events = (target) => {
        target.handlers = {};
        target.addEventListener = (name, callback) => {
            target.handlers[name] = callback;
        };
        return target;
    };
    const status = {textContent: ""};
    const panel = events({
        hidden: false,
        attributes: {"data-prompt-panel": "interactive"},
        getAttribute(name) { return this.attributes[name]; },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; }
    });
    const field = events({
        value: "Full generated prompt, including markup",
        focus() {},
        select() { data.selected++; },
        closest() { return panel; },
        getBoundingClientRect() {
            return {left: 10, top: 110, width: 240, height: 150};
        }
    });
    const button = events({
        disabled: false,
        closest() { return panel; },
        getBoundingClientRect() {
            return {left: 10, top: 290, width: 130, height: 35};
        }
    });
    panel.querySelector = (selector) => ({
        "[data-copy-prompt-field]": field,
        "[data-copy-status]": status,
        "[data-copy-prompt]": button
    })[selector];

    const choice = events({
        attributes: {"data-prompt-choice": "interactive"},
        getAttribute(name) { return this.attributes[name]; },
        setAttribute(name, value) { this.attributes[name] = value; },
        classList: {toggle() {}},
        focus() {}
    });
    const root = {
        querySelectorAll(selector) {
            return ({
                "[data-prompt-choice]": [choice],
                "[data-prompt-panel]": [panel],
                "[data-copy-prompt-field]": [field],
                "[data-copy-prompt]": [button]
            })[selector] || [];
        },
        getAttribute(name) {
            return {
                "data-copy-toast": "Conteúdo copiado",
                "data-copy-success": "Comando copiado!",
                "data-copy-error": "Não foi possível copiar"
            }[name];
        }
    };
    const document = {
        documentElement: {clientWidth: 390, clientHeight: 800},
        getElementById() { return root; },
        createElement() {
            return events({
                style: {},
                offsetWidth: 138,
                setAttribute() {},
                remove() { this.removed = true; }
            });
        },
        body: {appendChild(toast) { data.toasts.push(toast); }},
        execCommand(command) {
            assert.equal(command, "copy");
            data.exec++;
            return options.fallbackSuccess !== false;
        }
    };
    const navigator = {};
    if (options.clipboard !== false) {
        navigator.clipboard = {
            writeText(value) {
                data.copied.push(value);
                return options.clipboardReject ?
                    Promise.reject(new Error("Denied")) : Promise.resolve();
            }
        };
    }
    const window = {
        innerWidth: 390,
        innerHeight: 800,
        setTimeout(callback) { data.timers.push(callback); }
    };
    vm.runInNewContext(script, {
        require: (dependencies, callback) => callback(),
        document, navigator, window, Promise, Number, Array
    });
    return {data, field, button, status, panel};
}

async function settleClipboard() {
    await new Promise((resolve) => setImmediate(resolve));
}

test("clicking the readonly prompt selects and copies it, with a floating confirmation", async() => {
    const fixture = createFixture();
    fixture.field.handlers.click({clientX: 10, clientY: 220, detail: 1});
    await settleClipboard();

    assert.equal(fixture.data.selected, 1);
    assert.deepEqual(fixture.data.copied, [fixture.field.value]);
    assert.equal(fixture.status.textContent, "Comando copiado!");
    assert.equal(fixture.data.toasts.length, 1);
    const toast = fixture.data.toasts[0];
    assert.equal(toast.textContent, "Conteúdo copiado");
    assert.equal(toast.style.left, "81px"); // Kept inside the viewport on left-edge clicks.
    assert.equal(toast.style.top, "212px");
    toast.handlers.animationend();
    assert.equal(toast.removed, true);
    assert.equal(fixture.button.disabled, false);
});

test("copy button still works without navigator.clipboard and stays keyboard-friendly", async() => {
    const fixture = createFixture({clipboard: false});
    fixture.button.handlers.click({clientX: 0, clientY: 0, detail: 0});
    await settleClipboard();

    assert.equal(fixture.data.exec, 1);
    assert.equal(fixture.data.selected, 2); // Initial selection plus fallback selection.
    assert.equal(fixture.status.textContent, "Comando copiado!");
    assert.equal(fixture.data.toasts.length, 1);
    assert.equal(fixture.button.disabled, false);
});

test("denied clipboard access shows an error and leaves the text selected", async() => {
    const fixture = createFixture({clipboardReject: true, fallbackSuccess: false});
    fixture.field.handlers.click({clientX: 120, clientY: 200, detail: 1});
    await settleClipboard();

    assert.equal(fixture.data.exec, 1);
    assert.equal(fixture.data.selected, 2);
    assert.equal(fixture.status.textContent, "Não foi possível copiar");
    assert.equal(fixture.data.toasts.length, 0);
    assert.equal(fixture.button.disabled, false);
});

test("floating confirmation is pointer-transparent and respects reduced motion", () => {
    assert.match(template, /data-copy-prompt-field/);
    assert.match(template, /aria-describedby="[^"]*hint/);
    assert.match(style, /\.mod-mcp-copy-toast\s*\{/);
    assert.match(style, /pointer-events:\s*none/);
    assert.match(style, /@media\s*\(prefers-reduced-motion:\s*reduce\)/);
});
