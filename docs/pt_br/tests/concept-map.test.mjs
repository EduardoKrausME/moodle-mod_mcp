/**
 * Checks the standalone concept map without external dependencies.
 * Run: node --test docs/pt_br/tests/concept-map.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root = new URL("../", import.meta.url);
const read = path => readFile(new URL(path, root), "utf8");
function load() {
  return Promise.all([read("custom/mapa-mental-exploravel.html"),read("custom/examples.json")])
    .then(([html,manifest]) => {
      const script = html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
      assert.ok(script,"single inline script");
      return {html,script,item:JSON.parse(manifest).examples.find(e => e.id === "mapa-mental-exploravel")};
    });
}
test("HTML independently includes SVG connectors, accessible nodes and mobile styling",async () => {
  const {html,script,item} = await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<footer\b/i);
  assert.doesNotThrow(() => new Script(script));
  assert.match(html,/<svg id="links"[^>]+aria-hidden="true"/);
  assert.match(html,/@media\(max-width:620px\)/);
  assert.match(html, /prefers-reduced-motion:reduce/);
  assert.match(html, /id="announcer"/);
  assert.match(script,/aria-expanded/);
  assert.match(script,/aria-controls/);
  assert.match(script,/ArrowDown/);
  assert.match(script,/createElementNS\(ns,"path"\)/);
  assert.match(script,/replaceChildren\(\)/);
  assert.ok(item.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(item.prompt.includes("Aguarde a resposta"));
  assert.ok(item.prompt.includes("conexões SVG"));
});
test("the branching data has unique nodes and useful content",async () => {
  const {script} = await load();
  const source=script.match(/const branches = (\[[\s\S]*?\]);\s*const overview/);
  assert.ok(source,"data declared independently of the view");
  const branches=runInNewContext(source[1]);
  assert.equal(branches.length,4);
  const ids=new Set();
  for(const branch of branches) {
    assert.ok(!ids.has(branch.id));
    ids.add(branch.id);
    assert.equal(branch.leaves.length,2);
    for(const field of ["title","summary","note"]) assert.ok(branch[field]?.length>20);
    for(const leaf of branch.leaves) {
      assert.ok(!ids.has(leaf.id));
      ids.add(leaf.id);
      for(const field of ["title","summary","note"]) assert.ok(leaf[field]?.length>10);
      assert.ok(leaf.insights.length>=2);
    }
  }
});
test("keyboard interaction, expansion and SVG edge count",async () => {
  const {script} = await load();
  class FakeElement {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.dataset={};this.children=[];this.handlers={};this.attributes={};this.style={};this.hidden=false;this.textContent="";this.className="";this.focused=false;}
    setAttribute(k,v){this.attributes[k]=String(v);}
    getAttribute(k){return this.attributes[k];}
    append(...nodes){this.children.push(...nodes);}
    appendChild(node){this.children.push(node);}
    replaceChildren(...nodes){this.children=nodes;}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    closest(selector){return this.hidden ? this : null;}
    querySelectorAll(tag){return this.children.filter(node=>node.tag===tag);}
    getBoundingClientRect(){return {left:0,top:0,bottom:100,width:900,height:200};}
    click(){this.handlers.click?.();}
    keydown(key){let prevented=false;this.handlers.keydown?.({key,preventDefault(){prevented=true;}});return prevented;}
  }
  const initial = [...(await load()).html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes = new Map(initial.map(id=>[id,new FakeElement("div",id)]));
  const document = {
    getElementById(id){const el=nodes.get(id);assert.ok(el,"Missing element "+id);return el;},
    createElement(tag){return new FakeElement(tag);},
    createElementNS(_ns,tag){return new FakeElement(tag);}
  };
  const window={addEventListener(){}};
  runInNewContext(script,{document,window,requestAnimationFrame:fn=>fn(),ResizeObserver:class{observe(){}}});
  const $ = id=>nodes.get(id);
  const rootChildren=$("branches").children;
  assert.equal(rootChildren.length,4);
  assert.equal($("links").children.length,6,"four root lines and two expanded leaves");
  const first=rootChildren[0].children[0],second=rootChildren[1].children[0];
  assert.equal(first.getAttribute("aria-expanded"),"true");
  assert.equal(second.getAttribute("aria-expanded"),"false");
  assert.equal(first.keydown("ArrowRight"),true);
  assert.equal(second.focused,true);
  second.click();
  assert.equal(second.getAttribute("aria-expanded"),"true");
  assert.equal($("links").children.length,8);
  const leaf=rootChildren[1].children[1].children[0];
  leaf.click();
  assert.equal(leaf.getAttribute("aria-pressed"),"true");
  assert.equal($("detailTitle").textContent,"Aplicações");
  leaf.keydown("Escape");
  assert.equal(second.getAttribute("aria-expanded"),"false");
  assert.equal(second.focused,true);
  assert.equal($("detailTitle").textContent,"Tecnologia");
  $("expandAll").click();
  assert.equal($("links").children.length,12);
  assert.equal($("branchCount").textContent,"4 de 4 ramos expandidos");
  $("collapseAll").click();
  assert.equal($("links").children.length,4);
  $("reset").click();
  assert.equal($("links").children.length,6);
  assert.equal($("detailTitle").textContent,"Projeto digital");
  assert.equal($("branchCount").textContent,"1 de 4 ramos expandidos");
});
