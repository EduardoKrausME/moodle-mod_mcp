/**
 * Dependency-free tests for the custom interactive flowchart builder.
 * Run: node --test docs/pt_br/tests/flowchart-builder.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/construtor-de-fluxogramas.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("self-contained HTML is responsive, accessible, keyboard and touch enabled",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<footer\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const pattern of [
    /<svg class="edges"/,/<marker id="flowArrow"/,/marker-end/,/aria-live="polite"/,/role="status"/,
    /@media\(max-width:680px\)/,/prefers-reduced-motion:reduce/,/pointerdown/,/pointermove/,
    /pointercancel/,/ArrowLeft/,/shiftKey/,/id="moveRight"/,/id="undo"/,/id="validate"/,
    /id="confirmReset"/,/id="edgeBranch"/,/option value="Sim"/,/option value="Não"/,
    /id="edgeList"/,/textContent=edge\.label/
  ]) assert.match(html,pattern);
  assert.doesNotMatch(script,/innerHTML\s*=|localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie/);
});
test("initial flow has unique nodes and five directed, valid propositions",async()=>{
  const {script}=await load();
  const seedCode=script.match(/const seed=(\{[\s\S]*?\});\s*const \$=/);
  assert.ok(seedCode);
  const seed=runInNewContext("("+seedCode[1]+")");
  assert.equal(seed.nodes.length,5);
  assert.equal(seed.edges.length,5);
  assert.equal(seed.nodes.filter(n=>n.type==="start").length,1);
  assert.equal(seed.nodes.filter(n=>n.type==="decision").length,1);
  assert.equal(seed.nodes.filter(n=>n.type==="end").length,1);
  const nodes=new Map(seed.nodes.map(n=>[n.id,n]));
  assert.equal(nodes.size,seed.nodes.length);
  assert.equal(new Set(seed.edges.map(e=>e.id)).size,seed.edges.length);
  for(const edge of seed.edges) {
    assert.ok(nodes.has(edge.from)&&nodes.has(edge.to));
    assert.notEqual(edge.from,edge.to);
    assert.notEqual(nodes.get(edge.from).type,"end");
    assert.notEqual(nodes.get(edge.to).type,"start");
  }
  assert.deepEqual(Array.from(seed.edges.filter(e=>e.from==="n3"),e=>e.label).sort(),["Não","Sim"]);
});
test("flowchart edits, connection guards, accessibility and undo/reset",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.handlers={};this.attributes={};
      this.dataset={};this.style={};this.value="";this.textContent="";this.hidden=false;this.disabled=false;
      this.clientWidth=790;this.clientHeight=575;
    }
    append(...items){for(const item of items){item.parentNode=this;this.children.push(item);}}
    appendChild(item){item.parentNode=this;this.children.push(item);}
    replaceChildren(...items){this.children=[];this.append(...items);if(this.tag==="select")this.value="";}
    setAttribute(name,value){this.attributes[name]=String(value);}
    addEventListener(name,fn){this.handlers[name]=fn;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    change(){this.handlers.change?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
    keydown(key,shiftKey=false){let prevented=false;this.handlers.keydown?.({key,shiftKey,preventDefault(){prevented=true;}});return prevented;}
    pointerdown(x,y){this.handlers.pointerdown?.({button:0,pointerId:1,clientX:x,clientY:y});}
    pointermove(x,y){this.handlers.pointermove?.({clientX:x,clientY:y,preventDefault(){}});}
    pointerup(){this.handlers.pointerup?.();}
    pointercancel(){this.handlers.pointercancel?.();}
    getBoundingClientRect(){return {left:0,top:0,width:this.clientWidth,height:this.clientHeight};}
  }
  const htmlIDs=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(htmlIDs.map(id=>[id,new Element("div",id)]));
  for(const m of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const node=elements.get(m[2]);
    node.tag=m[1].toLowerCase();
    node.hidden=/\shidden(?:\s|>)/.test(m[0]);
  }
  const $=id=>elements.get(id);
  const document={
    getElementById(id){const node=elements.get(id);assert.ok(node,"DOM element "+id);return node;},
    createElement(tag){return new Element(tag);},
    createElementNS(_ns,tag){return new Element(tag);}
  };
  const window={addEventListener(){}};
  runInNewContext(script,{document,window,ResizeObserver:class{observe(){}}});
  const nodes=()=>$("nodeLayer").children;
  const links=()=>$("edgeList").children;
  const paths=()=>$("edgeLayer").children;
  const cards=()=>$("counter").textContent;
  assert.equal(nodes().length,5);
  assert.equal(links().length,5);
  assert.equal(paths().length,15,"each SVG connection has path, background and label");
  assert.equal(cards(),"5 nós · 5 conexões");
  assert.equal($("undo").disabled,true);
  $("validate").click();
  assert.equal($("validationHeading").textContent,"Fluxo estruturalmente consistente");
  assert.equal($("validationList").children.length,0);
  const originalX=nodes()[2].style.left;
  assert.equal(nodes()[2].keydown("ArrowRight",true),true);
  assert.equal(nodes()[2].style.left,"67%");
  assert.ok(nodes()[2].focused===undefined,"moving does not force focus elsewhere");
  $("undo").click();
  assert.equal(nodes()[2].style.left,originalX);
  nodes()[2].pointerdown(450,250);
  nodes()[2].pointermove(520,270);
  nodes()[2].pointerup();
  assert.notEqual(nodes()[2].style.left,originalX);
  $("undo").click();
  assert.equal(nodes()[2].style.left,originalX);
  $("newType").value="start";
  $("newLabel").value="Outro início";
  $("addForm").submit();
  assert.equal(nodes().length,5,"cannot duplicate start");
  $("newType").value="process";
  $("newLabel").value="Planejar melhoria";
  assert.ok($("addForm").submit());
  assert.equal(nodes().length,6);
  assert.equal(cards(),"6 nós · 5 conexões");
  assert.ok(nodes()[5].focused,"created node receives focus");
  assert.equal($("nodeName").value,"Planejar melhoria");
  $("nodeType").value="decision";
  $("nodeName").value="Há mais opções?";
  $("saveNode").click();
  assert.equal(nodes()[5].dataset.type,"decision");
  assert.equal(nodes()[5].children[0].textContent,"Há mais opções?");
  $("undo").click();
  assert.equal(nodes()[5].dataset.type,"process");
  nodes()[5].click();
  $("moveLeft").click();
  assert.notEqual(nodes()[5].style.left,undefined);
  $("undo").click();
  // Editing a connection
  links()[0].children[0].click();
  assert.equal($("edgeWord").value,"segue para");
  $("edgeWord").value="direciona para";
  $("edgeForm").submit();
  assert.equal(links()[0].children[0].children[0].textContent,"Início → direciona para → Reunir dados");
  $("undo").click();
  assert.equal(links()[0].children[0].children[0].textContent,"Início → segue para → Reunir dados");
  // Edge self loops and redundant outgoing from start are rejected.
  $("edgeFrom").value="n1";$("edgeFrom").change();
  $("edgeTo").value="n1";$("edgeWord").value="segue";
  $("edgeForm").submit();
  assert.equal(links().length,5);
  assert.match($("announcer").textContent,/dois nós diferentes/);
  $("edgeTo").value="n6";
  $("edgeForm").submit();
  assert.equal(links().length,5);
  assert.match($("announcer").textContent,/apenas uma saída/);
  // Add new connection from a process to a decision and validate routing.
  $("edgeFrom").value="n6";$("edgeFrom").change();
  $("edgeTo").value="n3";$("edgeWord").value="devolve para";
  $("edgeForm").submit();
  assert.equal(links().length,6);
  $("edgeFrom").value="n4";$("edgeFrom").change();
  $("edgeTo").value="n6";$("edgeWord").value="devolve";
  $("edgeForm").submit();
  assert.equal(links().length,6);
  assert.match($("announcer").textContent,/Fim não pode/);
  // A decision cannot repeat its Sim or Não branch.
  $("edgeFrom").value="n3";$("edgeFrom").change();
  assert.equal($("branchGroup").hidden,false);
  assert.equal($("wordGroup").hidden,true);
  $("edgeTo").value="n6";$("edgeBranch").value="Sim";
  $("edgeForm").submit();
  assert.equal(links().length,6);
  assert.match($("announcer").textContent,/saída da decisão já está conectada/);
  $("validate").click();
  assert.equal($("validation").dataset.state,"issues");
  assert.ok($("validationList").children.length>=1,"new unreachable node must be reported");
  // Edit one branch label and verify required Sim/Não rule.
  links()[3].children[0].click();
  assert.equal($("edgeBranch").value,"Não");
  $("edgeBranch").value="Sim";$("edgeForm").submit();
  assert.equal(links().length,6);
  assert.match($("announcer").textContent,/saída da decisão já está conectada/);
  $("deleteEdge").click();
  assert.equal(links().length,5);
  $("validate").click();
  const issues=$("validationList").children.map(x=>x.textContent);
  assert.ok(issues.some(x=>x.includes("uma saída Sim e uma Não")));
  $("undo").click();
  assert.equal(links().length,6,"undo deleted branch");
  $("undo").click();
  assert.equal(links().length,5,"undo created connection");
  // Test node removal with attached relationships and undo
  nodes()[2].click();
  $("deleteNode").click();
  assert.equal(nodes().length,5);
  assert.equal(links().length,1);
  $("undo").click();
  assert.equal(nodes().length,6);
  assert.equal(links().length,5);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  $("cancelReset").click();
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal(nodes().length,6);
  $("reset").click();
  $("confirmReset").click();
  assert.equal(nodes().length,5);
  assert.equal(links().length,5);
  $("undo").click();
  assert.equal(nodes().length,6,"undo reset restores previous editor state");
});
test("catalog adds exactly the eighth example and asks the teacher before generating HTML",async()=>{
  const manifest=JSON.parse(await read("custom/examples.json"));
  assert.equal(manifest.version,1);
  assert.equal(manifest.category,"custom");
  assert.equal(manifest.examples.length,8);
  const existing=["flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado","votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo","quadro-de-organizacao-de-ideias"];
  assert.deepEqual(manifest.examples.slice(0,7).map(x=>x.id),existing);
  const example=manifest.examples[7];
  assert.equal(example.id,"construtor-de-fluxogramas");
  assert.equal(example.path,"custom/construtor-de-fluxogramas.html");
  assert.match(example.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(example.prompt,/Aguarde a resposta/);
  assert.match(example.prompt,/Sim e Não/);
  assert.match(example.prompt,/desfazer/i);
  assert.ok(manifest.examples[0].features.includes("Frente e verso 3D"));
  assert.ok(manifest.examples[1].features.includes("Conexões SVG"));
  assert.ok(manifest.examples[2].features.includes("Etapas conectadas"));
  assert.ok(manifest.examples[3].features.includes("Resultados fictícios"));
  assert.ok(manifest.examples[4].features.includes("Matriz por critérios"));
  assert.ok(manifest.examples[5].features.includes("Conexões SVG rotuladas"));
  assert.ok(manifest.examples[6].features.includes("Kanban educativo"));
});
