/**
 * Dependency-free tests for the educational Kanban board.
 * Run: node --test docs/pt_br/tests/ideas-board.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/quadro-de-organizacao-de-ideias.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("self-contained HTML provides an accessible responsive Kanban",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|@import\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const p of [/<progress\b/,/aria-live="polite"/,/role="status"/,/role="group"/,/@media\(max-width:420px\)/,/prefers-reduced-motion:reduce/,/pointerdown/,/pointermove/,/pointerup/,/pointercancel/,/ArrowRight/,/ArrowUp/,/id="moveRight"/,/id="undo"/,/id="confirmReset"/,/id="cardDescription"/,/id="cardPriority"/,/touch-action:none/])
    assert.match(html,p);
  assert.doesNotMatch(script,/innerHTML\s*=|fetch\(|localStorage|sessionStorage|XMLHttpRequest|document\.cookie/);
});
test("seed data maintain unique IDs and valid column membership",async()=>{
  const {script}=await load();
  const seedSource=script.match(/const seed=(\{[\s\S]*?\});\s*const \$=/);
  assert.ok(seedSource);
  const seed=runInNewContext("("+seedSource[1]+")");
  assert.equal(seed.cards.length,5);
  assert.deepEqual(Object.keys(seed.lanes).sort(),["doing","done","ideas","plan"]);
  const ids=seed.cards.map(c=>c.id),placed=Object.values(seed.lanes).flat();
  assert.equal(new Set(ids).size,ids.length);
  assert.equal(new Set(placed).size,ids.length);
  assert.deepEqual(placed.slice().sort(),ids.slice().sort());
  assert.equal(seed.lanes.done.length,1);
  for(const card of seed.cards) {
    assert.ok(card.title.length>=3);
    assert.ok(["low","medium","high"].includes(card.priority));
  }
});
test("create, edit, keyboard and touch moves, progress, undo, delete and reset",async()=>{
  const {html,script}=await load();
  class FakeElement {
    constructor(tag,id="") {
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.dataset={};this.attributes={};this.handlers={};
      this.style={};this.hidden=false;this.disabled=false;this.textContent="";this.value="";
      const classes=new Set();
      this.classList={
        add(name){classes.add(name);},
        remove(name){classes.delete(name);},
        toggle(name,force){if(force===undefined){force=!classes.has(name);}if(force)classes.add(name);else classes.delete(name);return force;},
        contains(name){return classes.has(name);}
      };
    }
    setAttribute(name,value){this.attributes[name]=String(value);}
    append(...items){items.forEach(item=>{item.parentNode=this;this.children.push(item);});}
    appendChild(item){item.parentNode=this;this.children.push(item);}
    replaceChildren(...items){this.children=[];this.append(...items);}
    addEventListener(type,fn){this.handlers[type]=fn;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
    keydown(key){let prevented=false;this.handlers.keydown?.({key,preventDefault(){prevented=true;}});return prevented;}
    pointerdown(x,y){this.handlers.pointerdown?.({button:0,pointerId:1,clientX:x,clientY:y});}
    pointermove(x,y){this.handlers.pointermove?.({clientX:x,clientY:y,preventDefault(){}});}
    pointerup(x,y){this.handlers.pointerup?.({clientX:x,clientY:y});}
    pointercancel(x,y){this.handlers.pointercancel?.({clientX:x,clientY:y});}
    getBoundingClientRect(){
      if(this.dataset.column){
        const order={ideas:0,plan:1,doing:2,done:3}[this.dataset.column];
        return {left:order*220,top:0,right:order*220+205,bottom:700,width:205,height:700};
      }
      if(this.tag==="li"&&this.parentNode){
        const at=this.parentNode.children.indexOf(this);
        const top=120+at*120;
        return {left:0,top,right:180,bottom:top+100,width:180,height:100};
      }
      return {left:0,top:0,right:860,bottom:700,width:860,height:700};
    }
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(ids.map(id=>[id,new FakeElement("div",id)]));
  const $=id=>elements.get(id);
  const board=$("board"),lanes={};
  for(const key of ["ideas","plan","doing","done"]){
    const lane=new FakeElement("section");
    lane.dataset.column=key;
    lanes[key]=lane;
    board.appendChild(lane);
  }
  board.querySelectorAll=selector=>selector===".lane"?Object.values(lanes):[];
  board.querySelector=selector=>{
    const key=selector.match(/\[data-column="([^"]+)"\]/)?.[1];
    return key?lanes[key]:null;
  };
  const document={
    getElementById(id){const el=elements.get(id);assert.ok(el,"DOM element "+id);return el;},
    createElement(tag){return new FakeElement(tag);}
  };
  runInNewContext(script,{document});
  const list=key=>$({ideas:"listIdeas",plan:"listPlan",doing:"listDoing",done:"listDone"}[key]).children;
  const handle=(key,index)=>list(key)[index].children[0];
  assert.equal(list("ideas").length,2);
  assert.equal(list("plan").length,1);
  assert.equal(list("doing").length,1);
  assert.equal(list("done").length,1);
  assert.equal($("progress").max,5);
  assert.equal($("progress").value,1);
  assert.equal($("percentage").textContent,"20%");
  assert.equal($("undo").disabled,true);
  assert.equal(handle("ideas",0).keydown("ArrowRight"),true);
  assert.equal(list("ideas").length,1);
  assert.equal(list("plan").length,2);
  assert.equal($("progress").value,1);
  assert.ok(handle("plan",0).focused,"keyboard move preserves card focus");
  $("undo").click();
  assert.equal(list("ideas").length,2);
  assert.equal(list("plan").length,1);
  $("createCard").click();
  assert.ok($("cardTitle").focused);
  $("cardTitle").value="Construir rubrica de avaliação";
  $("cardDescription").value="Definir critérios observáveis para o trabalho.";
  $("cardPriority").value="high";
  $("cardColumn").value="ideas";
  assert.ok($("cardForm").submit());
  assert.equal(list("ideas").length,3);
  assert.equal($("progress").max,6);
  assert.equal($("percentage").textContent,"17%");
  assert.ok(handle("ideas",2).focused);
  assert.equal($("editorTitle").textContent,"Editar cartão");
  $("createCard").click();
  $("cardTitle").value="Construir rubrica de avaliação";
  $("cardForm").submit();
  assert.equal($("progress").max,6,"duplicate title rejected");
  assert.match($("announcer").textContent,/Já existe/);
  handle("ideas",2).click();
  $("cardTitle").value="Preparar rubrica para a turma";
  $("cardDescription").value="Verificar critérios de aprendizagem.";
  $("cardPriority").value="low";
  $("cardForm").submit();
  assert.equal(handle("ideas",2).children[0].textContent,"Preparar rubrica para a turma");
  assert.equal(handle("ideas",2).children.at(-1).children[0].dataset.priority,"low");
  $("undo").click();
  assert.equal(handle("ideas",2).children[0].textContent,"Construir rubrica de avaliação");
  handle("ideas",2).keydown("ArrowUp");
  assert.equal(handle("ideas",1).children[0].textContent,"Construir rubrica de avaliação");
  assert.ok(handle("ideas",1).focused);
  $("undo").click();
  assert.equal(handle("ideas",2).children[0].textContent,"Construir rubrica de avaliação");
  handle("ideas",2).pointerdown(110,200);
  handle("ideas",2).pointermove(750,240);
  handle("ideas",2).pointerup(750,240);
  assert.equal(list("done").length,2,"touch/pointer drag places card into done lane");
  assert.equal($("progress").value,2);
  assert.equal($("percentage").textContent,"33%");
  $("undo").click();
  assert.equal(list("done").length,1);
  assert.equal(list("ideas").length,3);
  handle("ideas",2).click();
  $("moveRight").click();
  assert.equal(list("plan").length,2,"accessible move button changes lane");
  $("moveLeft").click();
  assert.equal(list("ideas").length,3);
  $("moveDown").click();
  assert.equal(list("ideas").length,3);
  $("cardColumn").value="done";
  $("cardForm").submit();
  assert.equal(list("done").length,2,"editing stage moves card");
  assert.equal($("progress").value,2);
  handle("done",1).click();
  $("deleteCard").click();
  assert.equal(list("done").length,1);
  assert.equal($("progress").max,5);
  $("undo").click();
  assert.equal(list("done").length,2,"undo deletion restores card in same lane");
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  $("cancelReset").click();
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("progress").max,6);
  $("reset").click();
  $("confirmReset").click();
  assert.equal($("progress").max,5);
  assert.equal($("progress").value,1);
  assert.equal(list("ideas").length,2);
  assert.equal(list("done").length,1);
  $("undo").click();
  assert.equal($("progress").max,6,"undo reset restores previous Kanban");
  assert.equal(list("done").length,2);
});
test("catalog preserves six prior entries and begins prompt with teacher question",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  assert.equal(catalog.examples.length,7);
  const expected=["flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado","votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo","quadro-de-organizacao-de-ideias"];
  assert.deepEqual(catalog.examples.map(e=>e.id),expected);
  const example=catalog.examples[6];
  assert.equal(example.path,"custom/quadro-de-organizacao-de-ideias.html");
  assert.ok(example.prompt.includes("Qual tipo de conteúdo você deseja trabalhar"));
  assert.ok(example.prompt.includes("Aguarde a resposta"));
  assert.match(example.prompt,/Pointer Events/);
  assert.match(example.prompt,/desfazer/i);
  assert.ok(catalog.examples[0].features.includes("Frente e verso 3D"));
  assert.ok(catalog.examples[1].features.includes("Conexões SVG"));
  assert.ok(catalog.examples[2].features.includes("Etapas conectadas"));
  assert.ok(catalog.examples[3].features.includes("Resultados fictícios"));
  assert.ok(catalog.examples[4].features.includes("Matriz por critérios"));
  assert.ok(catalog.examples[5].features.includes("Conexões SVG rotuladas"));
});
