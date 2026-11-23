/**
 * Standalone, dependency-free checks for the personalized study route.
 * Run: node --test docs/pt_br/tests/study-route.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root = new URL("../", import.meta.url);
const read = file => readFile(new URL(file,root),"utf8");
const load = async () => {
  const html = await read("custom/roteiro-de-estudo-personalizado.html");
  const script = html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("standalone HTML has native task controls, accessibility and no remote dependencies",async () => {
  const {html,script} = await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<footer\b|https?:\/\//i);
  assert.doesNotThrow(() => new Script(script));
  for (const pattern of [/role="group"/,/aria-live="polite"/,/<progress\b/,/prefers-reduced-motion:reduce/,/@media\(max-width:500px\)/,/type = "checkbox"/,/aria-pressed/,/input\.disabled=!unlocked/])
    assert.match(html,pattern);
});
test("four distinct paths have valid ordered steps, task descriptions and goal",async () => {
  const {script} = await load();
  const source=script.match(/const routes = (\[[\s\S]*?\]);\s*const byId/);
  assert.ok(source);
  const routes=runInNewContext(source[1]);
  assert.equal(routes.length,4);
  assert.deepEqual(Array.from(routes,x=>x.id),["conceito","habilidade","avaliacao","projeto"]);
  const ids=new Set();
  for(const route of routes) {
    assert.ok(!ids.has(route.id));
    ids.add(route.id);
    assert.ok(route.goal.length>30&&route.description.length>30);
    assert.equal(route.steps.length,4);
    const stepIds=new Set();
    for(const step of route.steps) {
      assert.ok(!stepIds.has(step.id));
      stepIds.add(step.id);
      assert.ok(step.minutes>0);
      assert.equal(step.tasks.length,3);
      for(const task of step.tasks) assert.ok(task.length>20);
    }
  }
});
test("route selection, locked steps, progress, completion, and reset work correctly",async () => {
  const {html,script}=await load();
  class FakeElement {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.attributes={};this.dataset={};this.style={};this.hidden=false;this.checked=false;this.disabled=false;this.textContent="";}
    setAttribute(k,v){this.attributes[k]=String(v);}
    getAttribute(k){return this.attributes[k];}
    append(...arr){this.children.push(...arr);}
    appendChild(node){this.children.push(node);}
    replaceChildren(...arr){this.children=arr;}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    change(checked){this.checked=checked;this.handlers.change?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(x=>x[1]);
  const elements=new Map(ids.map(id=>[id,new FakeElement("div",id)]));
  const document={getElementById(id){const el=elements.get(id);assert.ok(el,"DOM element "+id);return el;},createElement(tag){return new FakeElement(tag);}};
  runInNewContext(script,{document});
  const $=id=>elements.get(id);
  const stages=()=>$("steps").children;
  const inputs=index=>stages()[index].children[1].children[2].children.map(label=>label.children[0]);
  assert.equal($("routeGrid").children.length,4);
  assert.equal(stages().length,4);
  assert.equal($("progress").value,0);
  assert.equal($("progress").max,12);
  assert.ok(inputs(1).every(x=>x.disabled));
  inputs(0).forEach(x=>x.change(true));
  assert.equal($("progress").value,3);
  assert.ok(inputs(1).every(x=>!x.disabled));
  for(let i=1;i<4;i++) inputs(i).forEach(x=>x.change(true));
  assert.equal($("percentage").textContent,"100%");
  assert.equal($("completion").hidden,false);
  inputs(0)[0].change(false);
  assert.equal($("completion").hidden,true);
  assert.equal($("progress").value,2);
  assert.ok(inputs(1).every(x=>x.disabled),"later tasks locked again");
  $("routeGrid").children[1].click();
  assert.equal($("pathTitle").textContent,"Praticar uma habilidade");
  assert.equal($("progress").value,0);
  $("nextTask").click();
  assert.ok(inputs(0)[0].focused);
  $("routeGrid").children[0].click();
  assert.equal($("progress").value,2,"session progress preserved on path switching");
  $("resetRoute").click();
  assert.equal($("confirmReset").hidden,false);
  $("cancelReset").click();
  assert.equal($("progress").value,2);
  $("resetRoute").click();$("confirmResetButton").click();
  assert.equal($("progress").value,0);
  assert.ok(inputs(0)[0].focused);
});
test("only the target manifest entry changes and prompt waits for the teacher",async () => {
  const manifest=JSON.parse(await read("custom/examples.json"));
  const e=manifest.examples.find(x=>x.id==="roteiro-de-estudo-personalizado");
  assert.ok(e&&e.path==="custom/roteiro-de-estudo-personalizado.html");
  assert.ok(e.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(e.prompt.includes("Aguarde a resposta"));
  assert.match(e.prompt,/checklist/);
  assert.match(e.prompt,/percursos alternativos/);
  assert.equal(manifest.examples.length,5);
  assert.ok(manifest.examples.find(x=>x.id==="flashcards-de-revisao").features.includes("Frente e verso 3D"));
  assert.ok(manifest.examples.find(x=>x.id==="mapa-mental-exploravel").features.includes("Conexões SVG"));
});
