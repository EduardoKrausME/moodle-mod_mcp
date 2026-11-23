/**
 * Testes independentes da linha do tempo investigativa.
 * Execute: node --test docs/pt_br/tests/investigative-timeline.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/linha-do-tempo-investigativa.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("HTML e SVG originais, sem dependências externas, com acessibilidade e responsividade",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<footer\b|<iframe\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const p of [
    /<svg id="timeline"/,/<title id="timelineTitle">/,/<desc id="timelineDesc">/,
    /aria-labelledby="timelineTitle timelineDesc"/,/aria-live="polite"/,
    /role="status"/,/role="img"/,/tabindex="-1"/,/<progress id="evidenceProgress"/,
    /prefers-reduced-motion:reduce/,/@media\(max-width:690px\)/,
    /pointerdown/,/pointermove/,/pointerup/,/pointercancel/,/ArrowUp/,/ArrowDown/,/Home/,/End/,
    /id="reviewList"/,/id="studentNote"/,/id="resetConfirmation"/,/id="confirmReset"/
  ]) assert.match(html,p);
  assert.doesNotMatch(script,/innerHTML\s*=|localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie/);
});
test("seis fontes fictícias coerentes com uma sequência temporal e distinção entre observação e hipótese",async()=>{
  const {script}=await load();
  const match=script.match(/const records=(\[[\s\S]*?\]);\s*const initial=/);
  assert.ok(match);
  const items=runInNewContext("("+match[1]+")");
  assert.equal(items.length,6);
  assert.deepEqual(items.map(x=>x.order),[0,1,2,3,4,5]);
  assert.equal(new Set(items.map(x=>x.id)).size,6);
  assert.ok(items.every(x=>/^([01]\d|2[0-3])h[0-5]\d$/.test(x.clock)));
  assert.ok(items.every((x,i)=>i===0||x.clock>items[i-1].clock));
  for(const item of items){
    for(const key of ["title","short","kind","source","summary","evidence","inference","question"]){
      assert.ok(typeof item[key]==="string"&&item[key].length>=(key==="short"?3:6),"Invalid "+key+" of "+item.id);
    }
  }
  assert.equal(items.filter(x=>x.category==="hipotese").length,2);
  assert.equal(items.filter(x=>x.category==="observacao").length,4);
  assert.ok(items.some(x=>/não comprova|não demonstra|não contém|não deve/i.test(x.inference)));
});
test("explorar fontes, navegar pelo teclado e reordenar por botões e arraste de toque",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.style={};
      this.handlers={};this.dataset={};this.attributes={};this.textContent="";
      this.className="";this.value="";this.disabled=false;this.hidden=false;
    }
    append(...items){for(const n of items){n.parentNode=this;this.children.push(n);}}
    appendChild(n){n.parentNode=this;this.children.push(n);}
    replaceChildren(...items){this.children=[];this.append(...items);}
    setAttribute(key,value){this.attributes[key]=String(value);}
    addEventListener(key,fn){this.handlers[key]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    keydown(key,shiftKey=false){let prevented=false;this.handlers.keydown?.({key,shiftKey,preventDefault(){prevented=true;}});return prevented;}
    pointerdown(y){this.handlers.pointerdown?.({button:0,clientY:y,pointerId:1});}
    pointermove(y){this.handlers.pointermove?.({clientY:y,preventDefault(){}});}
    pointerup(y){this.handlers.pointerup?.({clientY:y});this.handlers.click?.({detail:1});}
    pointercancel(y){this.handlers.pointercancel?.({clientY:y});}
    getBoundingClientRect(){
      const list=this.parentNode,position=list?.children?.indexOf(this)??0;
      return {left:0,top:position*110,width:580,height:96,right:580,bottom:position*110+96};
    }
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const controls=new Map(ids.map(id=>[id,new Element("div",id)]));
  for(const m of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const el=controls.get(m[2]);el.tag=m[1].toLowerCase();el.hidden=/\shidden(?:\s|>)/.test(m[0]);
  }
  const $=id=>controls.get(id);
  const document={
    getElementById(id){const el=$(id);assert.ok(el,"DOM element "+id);return el;},
    createElement(tag){return new Element(tag);},
    createElementNS(_ns,tag){return new Element(tag);}
  };
  runInNewContext(script,{document});
  const events=()=>$("events").children;
  const title=idx=>events()[idx].children[1].children[0].children[0].textContent;
  const handle=idx=>events()[idx].children[1].children[0];
  const buttons=idx=>events()[idx].children[1].children[2].children;
  const inspect=idx=>buttons(idx)[0].click();
  const find=label=>events().findIndex((_,idx)=>title(idx)===label);
  const up=idx=>buttons(idx)[1].click();
  const down=idx=>buttons(idx)[2].click();
  assert.equal(events().length,6);
  assert.equal(title(0),"Discutir uma hipótese");
  assert.equal($("evidenceProgress").value,0);
  assert.equal($("evidencePercent").textContent,"0%");
  assert.equal($("timelineDrawing").children.length,25,"one path plus four SVG elements per slot");
  assert.equal($("completion").hidden,true);
  $("validate").click();
  assert.equal($("feedbackTitle").textContent,"0 de 6 posições corretas");
  assert.equal(events()[0].dataset.checked,"wrong");
  inspect(0);
  assert.equal($("evidenceProgress").value,1);
  assert.equal($("evidenceHeading").focused,true);
  assert.equal($("evidenceDate").textContent,"09h05 · Dia 1");
  assert.equal($("evidenceType").textContent,"Interpretação / hipótese");
  assert.equal($("reviewList").children.length,1);
  assert.equal($("completion").hidden,true);
  $("backToCard").click();
  assert.ok(handle(0).focused);
  inspect(0);
  assert.equal($("evidenceProgress").value,1,"revisit must not double-count");
  assert.equal($("reviewList").children.length,1);
  // Keyboard arrows move nodes without erasing evidence.
  assert.equal(handle(0).keydown("ArrowDown"),true);
  assert.equal(title(1),"Discutir uma hipótese");
  assert.ok(handle(1).focused);
  assert.equal($("evidenceProgress").value,1);
  assert.equal($("feedbackTitle").textContent,"Organize e confira os registros");
  handle(1).keydown("Home");
  assert.equal(title(0),"Discutir uma hipótese");
  const prior=title(0);
  handle(0).pointerdown(44);
  handle(0).pointermove(173);
  assert.equal(events()[0].dataset.dragging,"true");
  handle(0).pointerup(245);
  assert.equal(title(2),prior,"touch drag moves card to position 3");
  assert.equal($("evidenceProgress").value,1,"drag must not accidentally inspect evidence by click");
  assert.ok(handle(2).focused);
  handle(2).pointerdown(250);
  handle(2).pointermove(300);
  handle(2).pointercancel(300);
  assert.equal(title(2),prior,"pointercancel must not reorder");
  // Inspect remaining sources (unique).
  for(let i=0;i<events().length;i++)inspect(i);
  assert.equal($("evidenceProgress").value,6);
  assert.equal($("evidencePercent").textContent,"100%");
  assert.equal($("reviewList").children.length,6);
  assert.equal($("completion").hidden,true,"visiting every source does not solve chronology");
  $("studentNote").value="Ordenei os documentos comparando as horas de cada anotação.";
  // Reconstruct the exact order using only the accessible up controls.
  const chronological=[
    "Preparar o experimento","Realizar a primeira medição","Discutir uma hipótese",
    "Organizar um teste comparativo","Conferir os resultados reunidos","Redigir uma conclusão provisória"
  ];
  for(let wanted=0;wanted<chronological.length;wanted++){
    let index=find(chronological[wanted]);
    while(index>wanted){up(index);index--;}
  }
  assert.deepEqual(Array.from(events(),(_,idx)=>title(idx)),chronological);
  assert.equal($("timelineDesc").textContent.includes("1. Preparar o experimento"),true);
  assert.equal($("studentNote").value,"Ordenei os documentos comparando as horas de cada anotação.");
  assert.equal($("completion").hidden,true,"A conclusão exige verificação explícita, mesmo com fontes e ordem corretas.");
  $("validate").click();
  assert.equal($("feedbackTitle").textContent,"Todas as etapas estão em ordem");
  assert.equal($("completion").hidden,false);
  assert.equal($("evidenceProgress").value,6);
  assert.ok(events().every(el=>el.dataset.checked==="right"));
  // Review and reset confirmation.
  $("reviewList").children[1].children[0].click();
  assert.equal($("evidenceDate").textContent,"08h35 · Dia 1");
  assert.equal($("evidenceProgress").value,6);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  assert.ok($("cancelReset").focused);
  $("cancelReset").click();
  assert.equal($("completion").hidden,false);
  $("reset").click();$("confirmReset").click();
  assert.equal($("evidenceProgress").value,0);
  assert.equal($("completion").hidden,true);
  assert.equal($("reviewList").children.length,0);
  assert.equal($("evidenceContent").hidden,true);
  assert.equal($("studentNote").value,"");
  assert.equal(title(0),"Discutir uma hipótese");
  assert.ok(handle(0).focused);
});
test("catálogo mantém os onze registros anteriores e exige perguntar ao professor antes de gerar",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  const expected=[
    "flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado",
    "votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo",
    "quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa",
    "explorador-de-imagens-com-pontos-interativos","planejador-de-aprendizagem"
  ];
  assert.equal(catalog.category,"custom");
  assert.equal(catalog.version,1);
  assert.ok(catalog.examples.length>=12,"O catálogo pode receber exemplos posteriores sem invalidar o item 12.");
  assert.deepEqual(catalog.examples.slice(0,11).map(x=>x.id),expected);
  const item=catalog.examples[11];
  assert.equal(item.id,"linha-do-tempo-investigativa");
  assert.equal(item.path,"custom/linha-do-tempo-investigativa.html");
  assert.equal(catalog.examples[11].id,"linha-do-tempo-investigativa","Não duplicar nem reposicionar o exemplo 12.");
  assert.match(item.prompt,/PRIMEIRA resposta/);
  assert.match(item.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(item.prompt,/Aguarde a resposta/);
  assert.match(item.prompt,/reordena|reordenar|reconstrução/i);
  for(const old of catalog.examples.slice(0,11))assert.ok(old.prompt.length>350);
});
