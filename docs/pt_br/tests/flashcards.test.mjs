/**
 * Checks for the independently usable flashcard example.
 * Run: node --test docs/pt_br/tests/flashcards.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script} from "node:vm";
const root = new URL("../", import.meta.url);
const read = path => readFile(new URL(path, root), "utf8");
test("flashcards have valid inline JS, complete data and accessible controls", async () => {
  const html = await read("custom/flashcards-de-revisao.html");
  const manifest = JSON.parse(await read("custom/examples.json"));
  const item = manifest.examples.find(x => x.id === "flashcards-de-revisao");
  assert.ok(item);
  assert.match(html, /^<!doctype html>/i);
  assert.match(html, /<html lang="pt-BR">/);
  assert.doesNotMatch(html, /<script[^>]*src=|<footer\b/i);
  const scripts = [...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
  assert.equal(scripts.length, 1);
  assert.doesNotThrow(() => new Script(scripts[0][1]));
  const data = scripts[0][1].match(/const cards = (\[[\s\S]*?\]);/);
  assert.ok(data, "card data must be present");
  const cards = JSON.parse(data[1]);
  assert.ok(cards.length >= 4);
  for (const card of cards) {
    for (const field of ["term","question","answer","example"]) {
      assert.ok(typeof card[field] === "string" && card[field].length > 10, field);
    }
  }
  assert.match(html, /aria-pressed="false"/);
  assert.match(html, /role="progressbar"/);
  assert.match(html, /aria-live="polite"/);
  assert.match(html, /prefers-reduced-motion:reduce/);
  assert.match(html, /id="reviewAgain"/);
  assert.match(html, /id="startAgain"/);
  assert.match(scripts[0][1], /textContent = item\.answer/);
  assert.match(scripts[0][1], /grade\.filter\(value => value === "review"\)/);
  assert.match(scripts[0][1], /filter\(index => grade\[index\] === "review"\)/);
  assert.match(item.prompt, /Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(item.prompt, /Aguarde a resposta/);
  assert.match(item.prompt, /revisão direcionada/);
});
