/**
 * Independent checks for the custom demonstration-only poll.
 * Run: node --test docs/pt_br/tests/quick-poll.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root = new URL("../",import.meta.url);
const read = file => readFile(new URL(file,root),"utf8");
const load=async()=>{
  const html=await read("custom/votacao-rapida.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("standalone HTML, semantic form, accessible results and reduced motion",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]*src=|<footer\b|https?:\/\//i);
  assert.doesNotThrow(()=>new Script(script));
  for(const pattern of [/<fieldset\b/,/<legend\b/,/type="submit"/,/aria-live="polite"/,/role="status"/,/aria-label=/,/prefers-reduced-motion:reduce/,/@media\(max-width:520px\)/,/event\.preventDefault\(\)/])
    assert.match(html,pattern);
  assert.match(html,/100 respostas fictícias/);
  assert.match(html,/não incluem a sua escolha/);
  assert.doesNotMatch(script,/localStorage|sessionStorage|fetch\(|XMLHttpRequest|MoodleMCP|document\.cookie/);
});
test("fixed fictional result dataset never aggregates the learner's choice",async()=>{
  const {script}=await load();
  const source=script.match(/const options = (\[[\s\S]*?\]);\s*const \$ =/);
  assert.ok(source);
  const options=runInNewContext(source[1]);
  assert.equal(options.length,4);
  assert.equal(options.reduce((n,item)=>n+item.sample,0),100);
  assert.equal(new Set(options.map(x=>x.id)).size,4);
  for(const option of options){
    assert.ok(option.title.length>4&&option.feedback.length>30&&option.prompt.length>20);
    assert.ok(option.sample>=0&&option.sample<=100);
  }
  assert.doesNotMatch(script,/\.sample\s*(?:\+\+|--|\+=|-=|=)/);
});
test("select, confirm, edit, cancel, reconfirm, reset and chart invariants",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.attributes={};this.dataset={};this.style={};this.hidden=false;this.checked=false;this.disabled=false;this.textContent="";}
    setAttribute(k,v){this.attributes[k]=String(v);}
    append(...children){this.children.push(...children);}
    appendChild(child){this.children.push(child);}
    addEventListener(type,fn){this.handlers[type]=fn;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    change(){this.handlers.change?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes=new Map(ids.map(id=>[id,new Element("div",id)]));
  // Reflect the initial HTML's hidden attributes in the lightweight DOM.
  for(const tag of html.matchAll(/<[^>]+\bid="([^"]+)"[^>]*>/g)) {
    if(/\shidden(?:\s|>)/.test(tag[0])) nodes.get(tag[1]).hidden=true;
  }
  const document={
    getElementById(id){const result=nodes.get(id);assert.ok(result,"DOM "+id);return result;},
    createElement(tag){return new Element(tag);}
  };
  runInNewContext(script,{document});
  const $=id=>nodes.get(id);
  const radios=()=>$("choices").children.map(label=>label.children[0]);
  const chart=()=>$("chart").children;
  assert.equal(radios().length,4);
  assert.ok($("confirmVote").disabled);
  assert.ok($("resultContent").hidden);
  radios()[1].change();
  assert.ok(!$("confirmVote").disabled);
  assert.equal($("previewText").textContent.startsWith("Jogo educativo"),true);
  assert.ok($("pollForm").submit());
  assert.equal($("confirmed").hidden,false);
  assert.equal($("resultContent").hidden,false);
  assert.equal($("confirmedTitle").focused,true);
  assert.ok($("confirmedCopy").textContent.includes("sem envio ao Moodle"));
  assert.equal(chart().length,4);
  assert.deepEqual(Array.from(chart(),row=>row.children[1].children[0].style.width),["37%","29%","21%","13%"]);
  assert.deepEqual(Array.from(chart(),row=>row.dataset.selected),["false","true","false","false"]);
  $("editVote").click();
  assert.equal($("pollForm").hidden,false);
  assert.equal($("confirmed").hidden,true);
  radios()[0].change();
  $("cancelEdit").click();
  assert.equal($("confirmed").hidden,false);
  assert.deepEqual(Array.from(chart(),row=>row.dataset.selected),["false","true","false","false"]);
  $("editVote").click();
  radios()[3].change();
  $("pollForm").submit();
  assert.deepEqual(Array.from(chart(),row=>row.dataset.selected),["false","false","false","true"]);
  assert.equal($("feedbackCopy").textContent.includes("tutorial"),true);
  assert.deepEqual(Array.from(chart(),row=>row.children[1].children[0].style.width),["37%","29%","21%","13%"],"fictional figures unchanged");
  $("restart").click();
  assert.equal($("resetConfirmation").hidden,false);
  $("cancelRestart").click();
  assert.equal($("confirmed").hidden,false);
  $("restart").click();$("confirmRestart").click();
  assert.equal($("confirmed").hidden,true);
  assert.equal($("resultContent").hidden,true);
  assert.equal($("emptyResults").hidden,false);
  assert.ok($("confirmVote").disabled);
  assert.ok(radios().every(r=>!r.checked));
  assert.ok(radios()[0].focused);
});
test("manifest preserves other examples and teacher-first prompt",async()=>{
  const manifest=JSON.parse(await read("custom/examples.json"));
  const item=manifest.examples.find(e=>e.id==="votacao-rapida");
  assert.equal(manifest.examples.length,5);
  assert.ok(item&&item.path==="custom/votacao-rapida.html");
  assert.ok(item.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(item.prompt.includes("Aguarde a resposta"));
  assert.match(item.prompt,/resultados fictícios/);
  assert.match(item.prompt,/alterar a própria escolha/);
  assert.ok(manifest.examples[0].features.includes("Frente e verso 3D"));
  assert.ok(manifest.examples[1].features.includes("Conexões SVG"));
  assert.ok(manifest.examples[2].features.includes("Etapas conectadas"));
});
