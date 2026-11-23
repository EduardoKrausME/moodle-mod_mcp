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


test("flashcards: revelar, classificar, revisar apenas dificuldades e reiniciar sem perder o foco",async()=>{
  const html=await read("custom/flashcards-de-revisao.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)[1];
  class Element{
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.handlers={};this.attributes={};
      this.style={};this.dataset={};this.hidden=false;this.disabled=false;this.textContent="";
      this.classList={toggle:(name,on)=>{this.dataset[name]=String(on);}};
    }
    append(...items){this.children.push(...items);}
    appendChild(item){this.children.push(item);}
    setAttribute(key,value){this.attributes[key]=String(value);}
    removeAttribute(key){delete this.attributes[key];}
    addEventListener(name,fn){this.handlers[name]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes=new Map(ids.map(id=>[id,new Element("div",id)]));
  const $=id=>{const node=nodes.get(id);assert.ok(node,id);return node;};
  const document={getElementById:$,createElement:tag=>new Element(tag)};
  const runInNewContext=source=>Function("document",source)(document);
  runInNewContext(script);
  assert.equal($("topicList").children.length,4);
  assert.equal($("cardTerm").textContent,"Objetivo de aprendizagem");
  assert.equal($("knownCount").textContent,"0");
  assert.equal($("reviewCount").textContent,"0");
  assert.equal($("flashcard").attributes["aria-pressed"],"false");
  $("flashcard").click();
  assert.equal($("flashcard").attributes["aria-pressed"],"true");
  assert.match($("announcer").textContent,/resultado observável/);
  $("markReview").click();
  assert.equal($("reviewCount").textContent,"1");
  $("next").click();
  assert.equal($("flashcard").attributes["aria-pressed"],"false");
  $("markKnown").click();
  assert.equal($("knownCount").textContent,"1");
  $("next").click();
  $("next").click();
  assert.equal($("counter").textContent,"Cartão 4 de 4");
  $("next").click();
  assert.equal($("summary").hidden,false);
  assert.equal($("cardStage").hidden,true);
  assert.equal($("totalUnseen").textContent,"2");
  assert.equal($("reviewAgain").disabled,false);
  assert.ok($("summaryTitle").focused);
  $("reviewAgain").click();
  assert.equal($("sessionMode").textContent,"Revisão dirigida");
  assert.equal($("counter").textContent,"Cartão 1 de 1");
  assert.equal($("cardTerm").textContent,"Objetivo de aprendizagem");
  assert.ok($("flashcard").focused);
  $("markKnown").click();
  assert.equal($("reviewCount").textContent,"0");
  assert.equal($("knownCount").textContent,"2");
  $("next").click();
  assert.equal($("reviewAgain").disabled,true);
  $("startAgain").click();
  assert.equal($("knownCount").textContent,"0");
  assert.equal($("reviewCount").textContent,"0");
  assert.equal($("progress").attributes["aria-valuenow"],"0");
  assert.equal($("cardTerm").textContent,"Objetivo de aprendizagem");
  assert.ok($("flashcard").focused);
  $("topicList").children[2].children[0].click();
  assert.equal($("cardTerm").textContent,"Rubrica de avaliação");
  assert.equal($("counter").textContent,"Cartão 3 de 4");
});
