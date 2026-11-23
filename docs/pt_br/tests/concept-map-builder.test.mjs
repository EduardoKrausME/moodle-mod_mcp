/**
 * Tests for the standalone concept-map builder.
 * Run: node --test docs/pt_br/tests/concept-map-builder.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/mapa-conceitual-interativo.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("self-contained HTML has a keyboard and touch editor, SVG and no remote dependencies",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<footer\b|<link[^>]+href=|@import\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const pattern of [/<svg id="edges"/,/<marker id="arrowTip"/,/aria-live="polite"/,/role="status"/,/@media\(max-width:630px\)/,/prefers-reduced-motion:reduce/,/pointerdown/,/pointermove/,/ArrowLeft/,/shiftKey/,/id="moveLeft"/,/id="undo"/,/id="resetConfirmation"/,/aria-label="Conceitos sugeridos"/,/textContent=edge\.label/])
    assert.match(html,pattern);
  assert.doesNotMatch(script,/localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie|innerHTML\s*=/);
});
test("starter propositions are consistent and data are independently editable",async()=>{
  const {script}=await load();
  const source=script.match(/const seed = (\{[\s\S]*?\});\s*const \$ =/);
  assert.ok(source);
  const seed=runInNewContext("("+source[1]+")");
  assert.equal(seed.nodes.length,3);
  assert.equal(seed.edges.length,2);
  assert.equal(new Set(seed.nodes.map(n=>n.id)).size,seed.nodes.length);
  assert.equal(new Set(seed.edges.map(e=>e.id)).size,seed.edges.length);
  const ids=new Set(seed.nodes.map(n=>n.id));
  for(const edge of seed.edges){
    assert.ok(ids.has(edge.from)&&ids.has(edge.to)&&edge.from!==edge.to);
    assert.ok(edge.label.length>=2);
  }
});
test("construct, move, connect, edit, delete, undo, reset and validation",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.handlers={};this.attributes={};this.dataset={};this.style={};
      this.value="";this.textContent="";this.hidden=false;this.disabled=false;this.type="";this.clientWidth=760;this.clientHeight=540;
    }
    setAttribute(k,v){this.attributes[k]=String(v);}
    append(...elements){this.children.push(...elements);}
    appendChild(element){this.children.push(element);}
    replaceChildren(...elements){this.children=elements;}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
    keydown(key,shiftKey=false){let prevented=false;this.handlers.keydown?.({key,shiftKey,preventDefault(){prevented=true;}});return prevented;}
    pointerdown(x,y){this.handlers.pointerdown?.({button:0,pointerId:1,clientX:x,clientY:y});}
    pointermove(x,y){this.handlers.pointermove?.({pointerId:1,clientX:x,clientY:y,preventDefault(){}});}
    pointerup(){this.handlers.pointerup?.();}
    getBoundingClientRect(){return {left:0,top:0,width:this.clientWidth,height:this.clientHeight};}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(ids.map(id=>[id,new Element("div",id)]));
  const document={
    getElementById(id){const el=elements.get(id);assert.ok(el,"Missing DOM "+id);return el;},
    createElement(tag){return new Element(tag);},
    createElementNS(_ns,tag){return new Element(tag);}
  };
  const window={addEventListener(){}};
  runInNewContext(script,{document,window,ResizeObserver:class{observe(){}}});
  const $=id=>elements.get(id);
  const nodes=()=>$("nodeLayer").children;
  const links=()=>$("edgeLayer").children;
  const list=()=>$("relationList").children;
  assert.equal(nodes().length,3);
  assert.equal(links().length,6,"each edge contributes path, label background, text");
  assert.equal(list().length,2);
  assert.equal($("undo").disabled,true);
  assert.equal($("counter").textContent,"3 conceitos · 2 relações");
  const first=nodes()[0];
  first.focus();
  assert.equal(first.keydown("ArrowRight",true),true);
  assert.equal(first.style.left,"33%");
  assert.ok(first.focused,"moving by keyboard retains focus");
  $("undo").click();
  assert.equal(nodes()[0].style.left,"26%");
  assert.equal($("undo").disabled,true);
  $("newConcept").value="Feedback";
  assert.ok($("addForm").submit());
  assert.equal(nodes().length,4);
  assert.ok(nodes()[3].focused);
  assert.equal($("counter").textContent,"4 conceitos · 2 relações");
  $("newConcept").value="feedback";
  $("addForm").submit();
  assert.equal(nodes().length,4,"duplicate concept rejected");
  $("renameInput").value="Reflexão";
  $("renameButton").click();
  assert.equal(nodes()[3].children[0].textContent,"Feedback","duplicate rename rejected");
  $("renameInput").value="Colaboração";
  $("renameButton").click();
  assert.equal(nodes()[3].children[0].textContent,"Colaboração");
  $("undo").click();
  assert.equal(nodes()[3].children[0].textContent,"Feedback","rename undo works");
  const moved=nodes()[3],originalPosition=moved.style.left;
  moved.pointerdown(0,0);
  moved.pointermove(520,300);
  moved.pointerup();
  assert.notEqual(nodes()[3].style.left,originalPosition,"dragging moves node");
  $("undo").click();
  assert.equal(nodes()[3].style.left,originalPosition,"drag can be undone");
  $("moveLeft").click();
  assert.notEqual(nodes()[3].style.left,originalPosition,"touch move button works");
  $("undo").click();
  assert.equal(nodes()[3].style.left,originalPosition);
  $("fromNode").value="n4";
  $("toNode").value="n2";
  $("relationLabel").value="incentiva";
  assert.ok($("relationForm").submit());
  assert.equal(list().length,3);
  assert.equal(links().length,9);
  $("fromNode").value="n4";
  $("toNode").value="n2";
  $("relationLabel").value="fortalece";
  $("relationForm").submit();
  assert.equal(list().length,3,"duplicate directional edge rejected");
  $("toNode").value="n4";
  $("relationForm").submit();
  assert.equal(list().length,3,"self-loop rejected");
  list()[2].children[0].click();
  assert.equal($("relationLabel").value,"incentiva");
  $("relationLabel").value="favorece";
  $("toNode").value="n3";
  $("relationForm").submit();
  assert.equal(list().length,3);
  assert.equal(list()[2].children[0].children[0].textContent,"Feedback → favorece → Reflexão");
  $("undo").click();
  assert.equal(list()[2].children[0].children[0].textContent,"Feedback → incentiva → Experiência","edge editing undo");
  list()[2].children[0].click();
  $("deleteRelation").click();
  assert.equal(list().length,2);
  $("undo").click();
  assert.equal(list().length,3,"edge deletion undo");
  nodes()[3].click();
  $("deleteNode").click();
  assert.equal(nodes().length,3);
  assert.equal(list().length,2,"deleting node removes attached relations");
  $("undo").click();
  assert.equal(nodes().length,4);
  assert.equal(list().length,3,"deleting node undo restores relations");
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  $("cancelReset").click();
  assert.equal(nodes().length,4);
  $("reset").click();
  $("confirmReset").click();
  assert.equal(nodes().length,3);
  assert.equal(list().length,2);
  $("undo").click();
  assert.equal(nodes().length,4,"reset undo restores previous work");
  assert.equal(list().length,3);
});
test("manifest adds exactly the new builder and preserves teacher-first prompt",async()=>{
  const manifest=JSON.parse(await read("custom/examples.json"));
  assert.equal(manifest.version,1);
  assert.equal(manifest.category,"custom");
  assert.ok(manifest.examples.length>=6,"O catálogo pode receber novos exemplos.");
  assert.equal(manifest.examples[5].id,"mapa-conceitual-interativo");
  const item=manifest.examples[5];
  assert.equal(item.id,"mapa-conceitual-interativo");
  assert.equal(item.path,"custom/mapa-conceitual-interativo.html");
  assert.ok(item.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(item.prompt.includes("Aguarde a resposta"));
  assert.match(item.prompt,/conexões/);
  assert.match(item.prompt,/desfazer/);
  assert.ok(manifest.examples[0].features.includes("Frente e verso 3D"));
  assert.ok(manifest.examples[1].features.includes("Conexões SVG"));
  assert.ok(manifest.examples[2].features.includes("Etapas conectadas"));
  assert.ok(manifest.examples[3].features.includes("Resultados fictícios"));
  assert.ok(manifest.examples[4].features.includes("Matriz por critérios"));
});
