/**
 * Standalone comparison experience tests.
 * Run: node --test docs/pt_br/tests/technique-comparator.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root = new URL("../",import.meta.url);
const read = file => readFile(new URL(file,root),"utf8");
const load=async()=>{
  const html=await read("custom/comparador-de-tecnicas.html");
  const js=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(js);
  return {html,js};
};
test("standalone HTML is responsive, keyboard accessible and has no remote dependencies",async()=>{
  const {html,js}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<footer\b|https?:\/\//i);
  assert.doesNotThrow(()=>new Script(js));
  for(const pattern of [/<select id="priority">/,/role="group"/,/<table\b/,/tabindex="0"/,/role="status"/,/aria-live="polite"/,/@media\(max-width:540px\)/,/prefers-reduced-motion:reduce/,/type="checkbox"/,/replaceChildren\(\)/])
    assert.match(html,pattern);
  assert.doesNotMatch(js,/localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie|MoodleMCP/);
});
test("four distinct techniques have complete information and bounded rubric values",async()=>{
  const {js}=await load();
  const cc=js.match(/const criteria = (\[[\s\S]*?\]);\s*const techniques/);
  const tt=js.match(/const techniques = (\[[\s\S]*?\]);\s*const \$ =/);
  assert.ok(cc&&tt);
  const criteria=runInNewContext(cc[1]);
  const techniques=runInNewContext(tt[1]);
  assert.equal(criteria.length,4);
  assert.equal(techniques.length,4);
  assert.equal(new Set(techniques.map(x=>x.id)).size,4);
  assert.equal(new Set(criteria.map(x=>x.id)).size,4);
  for(const item of techniques){
    assert.ok(item.title&&item.use.length>30);
    assert.ok(item.strengths.length>=2&&item.limits.length>=2);
    for(const criterion of criteria){
      const score=item.scores[criterion.id];
      assert.ok(score&&Number.isInteger(score[0])&&score[0]>=1&&score[0]<=5);
      assert.ok(score[1].length>25);
    }
  }
});
test("select two to four, prevent fewer, update matrix and criterion, reset with focus",async()=>{
  const {html,js}=await load();
  class Element {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.attributes={};this.dataset={};this.style={width:"",setProperty(k,v){this[k]=v;}};this.disabled=false;this.checked=false;this.textContent="";this.value="";}
    setAttribute(k,v){this.attributes[k]=String(v);}
    append(...nodes){this.children.push(...nodes);}
    appendChild(child){this.children.push(child);}
    replaceChildren(...nodes){this.children=nodes;}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    change(checked){this.checked=checked;this.handlers.change?.();}
    click(){this.handlers.click?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes=new Map(ids.map(id=>[id,new Element("div",id)]));
  const document={getElementById(id){const el=nodes.get(id);assert.ok(el,"Missing DOM "+id);return el;},createElement(tag){return new Element(tag);}};
  nodes.get("priority").value="recall";
  runInNewContext(js,{document});
  const $=id=>nodes.get(id);
  const controls=()=>$("options").children.map(label=>label.children[0]);
  assert.equal(controls().length,4);
  assert.deepEqual(Array.from(controls(),c=>c.checked),[true,true,false,false]);
  assert.equal($("cards").children.length,2);
  assert.equal($("matrixBody").children.length,4);
  assert.equal($("matrixHead").children[0].children.length,3);
  assert.equal($("topName").textContent,"Questões práticas");
  controls()[0].change(false);
  assert.equal(controls()[0].checked,true,"at least two techniques required");
  assert.equal($("cards").children.length,2);
  controls()[2].change(true);
  assert.equal($("cards").children.length,3);
  assert.equal($("matrixHead").children[0].children.length,4);
  assert.equal($("count").textContent,"3 de 4 técnicas selecionadas");
  controls()[0].change(false);
  assert.equal($("cards").children.length,2);
  assert.equal($("matrixHead").children[0].children.length,3);
  $("priority").value="organization";
  $("priority").handlers.change();
  assert.equal($("criterionDescription").textContent.includes("estruturar"),true);
  assert.equal($("topName").textContent,"Explicar a um colega");
  assert.equal($("matrixBody").children[1].dataset.active,"true");
  $("showAll").click();
  assert.equal($("cards").children.length,4);
  assert.equal($("matrixHead").children[0].children.length,5);
  assert.equal($("selectAll").disabled,true);
  $("priority").value="continuity";
  $("priority").handlers.change();
  assert.equal($("topName").textContent,"Revisão espaçada");
  $("reset").click();
  assert.equal($("cards").children.length,2);
  assert.equal($("topName").textContent,"Questões práticas");
  assert.equal($("priority").value,"recall");
  assert.ok(controls()[0].focused);
  assert.deepEqual(Array.from(controls(),c=>c.checked),[true,true,false,false]);
});
test("only comparator catalog entry is updated and prompt asks teacher first",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  const item=catalog.examples.find(e=>e.id==="comparador-de-tecnicas");
  assert.ok(catalog.examples.length>=5,"O catálogo pode receber novos exemplos.");
  assert.equal(catalog.examples[4].id,"comparador-de-tecnicas");
  assert.ok(item);
  assert.ok(item.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(item.prompt.includes("Aguarde a resposta"));
  assert.match(item.prompt,/matriz comparativa/);
  assert.match(item.prompt,/duas ou mais/);
  assert.ok(catalog.examples[0].features.includes("Frente e verso 3D"));
  assert.ok(catalog.examples[1].features.includes("Conexões SVG"));
  assert.ok(catalog.examples[2].features.includes("Etapas conectadas"));
  assert.ok(catalog.examples[3].features.includes("Resultados fictícios"));
});
