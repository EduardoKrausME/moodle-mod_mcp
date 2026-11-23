/**
 * Tests for the interactive argument-and-evidence workshop.
 * Run: node --test docs/pt_br/tests/argument-workshop.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/oficina-de-argumentos-e-evidencias.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("HTML autossuficiente, SVG dinâmico e acessibilidade por teclado e toque",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<footer\b|<iframe\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const re of [
    /<svg class="argument-map"/,/<title id="diagramTitle">/,
    /<desc id="diagramDesc">/,/aria-labelledby="diagramTitle diagramDesc"/,
    /<progress id="progress"/,/role="status"/,/aria-live="polite"/,
    /@media\(max-width:750px\)/,/prefers-reduced-motion:reduce/,
    /id="sourceList"/,/id="selectedList"/,/id="counterList"/,
    /id="counterAdvice"/,/id="reviewResult"/,/id="confirmReset"/,
    /ArrowUp/,/ArrowDown/,/altKey/,/aria-pressed/,/setAttribute\("aria-label"/
  ]) assert.match(html,re);
  assert.doesNotMatch(script,/innerHTML\s*=|localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie|document\.write/);
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  assert.equal(new Set(ids).size,ids.length,"IDs HTML must be unique");
  const used=[...script.matchAll(/\$\("([^"]+)"\)/g)].map(m=>m[1]);
  for(const id of used) assert.ok(ids.includes(id),"Missing DOM element: "+id);
});
test("oito fontes sintéticas distinguem evidência limitada de generalização frágil",async()=>{
  const {script}=await load();
  const source=script.match(/const sources=(\[[\s\S]*?\]);\s*const objections=(\[[\s\S]*?\]);\s*const initial=/);
  assert.ok(source);
  const sources=runInNewContext("("+source[1]+")");
  const objections=runInNewContext("("+source[2]+")");
  assert.equal(sources.length,8);
  assert.equal(new Set(sources.map(item=>item.id)).size,8);
  assert.equal(sources.filter(item=>item.category==="relevant").length,5);
  assert.equal(sources.filter(item=>item.category==="fragile").length,3);
  assert.equal(objections.length,3);
  for(const item of sources) {
    for(const field of ["title","origin","body","feedback"])assert.ok(item[field].length>17,item.id+" "+field);
    assert.ok(["support","mixed","caution"].includes(item.direction));
    assert.ok(["relevant","fragile"].includes(item.category));
  }
  assert.ok(sources.some(item=>/amostra voluntária/.test(item.feedback)));
  assert.ok(sources.some(item=>/não permite concluir/.test(item.feedback)));
  assert.ok(sources.some(item=>/generalização/.test(item.feedback)));
  assert.ok(objections.every(o=>o.focus.length>30));
});
test("tese, classificação, seleção, reordenação e desvinculação são editáveis",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.attributes={};
      this.handlers={};this.dataset={};this.style={};this.textContent="";this.value="";
      this.checked=false;this.disabled=false;this.hidden=false;this.className="";
    }
    append(...children){for(const child of children){child.parentNode=this;this.children.push(child);}}
    appendChild(child){child.parentNode=this;this.children.push(child);}
    replaceChildren(...children){this.children=[];this.append(...children);}
    setAttribute(key,value){this.attributes[key]=String(value);}
    getAttribute(key){return this.attributes[key];}
    addEventListener(type,fn){this.handlers[type]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    input(){this.handlers.input?.();}
    change(){this.handlers.change?.();}
    keydown(key,altKey=false){
      let prevented=false;
      this.handlers.keydown?.({key,altKey,preventDefault(){prevented=true;}});
      return prevented;
    }
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(ids.map(id=>[id,new Element("div",id)]));
  const $=id=>{const el=elements.get(id);assert.ok(el,"element "+id);return el;};
  const document={
    getElementById:$,
    createElement(tag){return new Element(tag);},
    createElementNS(ns,tag){return new Element(tag);}
  };
  runInNewContext(script,{document});
  const source=i=>$("sourceList").children[i];
  const classify=(i,option)=>source(i).children[4].children[option].click();
  const include=i=>source(i).children.at(-1).click();
  const selected=()=>$("selectedList").children;
  const selectedInput=i=>selected()[i].children[2].children[0];
  const counter=i=>$("counterList").children[i];
  const type=(id,value)=>{$(id).value=value;$(id).input();};
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal(source(0).children[2].textContent,"Questionário fictício · 42 de 60 respostas");
  assert.equal($("sourceList").children.length,8);
  assert.equal($("counterList").children.length,3);
  assert.equal($("diagramLayer").children.length,26,"four SVG nodes with five components and six arrows");
  assert.equal($("selectedEmpty").hidden,false);
  $("review").click();
  assert.equal($("reviewResult").hidden,false);
  assert.equal($("reviewResult").dataset.state,"incomplete");
  assert.match($("reviewTitle").textContent,/ponto\(s\) a revisar/);
  assert.ok($("reviewIssues").children.length>=8);
  type("thesis","Defendo abrir a biblioteca duas noites por semana, com horários acessíveis e custos revisados.");
  assert.equal($("progress").value,1);
  assert.equal($("reviewResult").hidden,true,"editing hides stale review");
  classify(0,0);classify(1,0);classify(2,0);classify(3,0);classify(4,1);
  assert.equal($("progress").value,2);
  assert.equal($("classificationProgress").textContent.startsWith("5 de 8"),true);
  assert.equal(source(4).dataset.class,"fragile");
  assert.equal(source(4).children[5].dataset.mismatch,"false");
  include(0);include(1);
  assert.equal(selected().length,2);
  assert.equal($("selectedEmpty").hidden,true);
  assert.equal(selected()[0].children[0].children[0].textContent,"Pesquisa voluntária com estudantes");
  assert.ok(source(1).children.at(-1).focused);
  selectedInput(0).value="O levantamento indica demanda, mas a amostra voluntária é limitada.";
  selectedInput(0).input();
  selectedInput(1).value="As visitas no piloto mostram procura, sem comprovar efeito no aprendizado.";
  selectedInput(1).input();
  assert.equal($("progress").value,3);
  assert.equal($("progressPercent").textContent,"60%");
  const input=selectedInput(1);
  assert.equal(input.keydown("ArrowUp",true),true);
  assert.equal(selected()[0].children[0].children[0].textContent,"Contagem de visitas em projeto-piloto");
  assert.ok(selectedInput(0).focused);
  selected()[0].children[1].children[1].click();
  assert.equal(selected()[1].children[0].children[0].textContent,"Contagem de visitas em projeto-piloto");
  // Wrongly mark a fragile anecdote as relevant, include it and review the warning.
  classify(4,0);
  assert.equal(source(4).children[5].dataset.mismatch,"true");
  include(4);
  assert.equal(selected().length,3);
  assert.match($("announcer").textContent,/incluída na justificativa/);
  $("review").click();
  assert.ok($("reviewIssues").children.some(li=>li.textContent.includes("Um relato individual")));
  // Reclassifying as fragile removes it from the justification automatically.
  classify(4,1);
  assert.equal(selected().length,2);
  assert.equal($("reviewResult").hidden,true);
  assert.equal($("progress").value,3);
  // A valid but cautionary source must receive contextualized feedback.
  include(2);
  selectedInput(2).value="Esse custo exige uma condição financeira antes da aprovação da proposta.";
  selectedInput(2).input();
  $("review").click();
  assert.ok($("reviewIssues").children.some(li=>li.textContent.includes("apresenta restrições")));
  include(2);
  assert.equal(selected().length,2);
  assert.equal($("progress").value,3);
  // Reclassifying an included relevant source as fragile must reduce structural progress.
  classify(1,1);
  assert.equal(selected().length,1);
  assert.equal($("progress").value,2);
  classify(1,0);
  include(1);
  assert.equal(selected().length,2);
  assert.equal($("progress").value,3,"connection text preserved after re-adding");
  return {$,source,classify,include,selected,selectedInput,counter,type};
});
test("contra-argumento, resposta, revisão crítica e reinício confirmado",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.dataset={};this.attributes={};this.style={};this.value="";this.textContent="";this.checked=false;this.disabled=false;this.hidden=false;}
    append(...xs){for(const x of xs){x.parentNode=this;this.children.push(x);}}
    appendChild(x){x.parentNode=this;this.children.push(x);}
    replaceChildren(...xs){this.children=[];this.append(...xs);}
    setAttribute(k,v){this.attributes[k]=String(v);}
    addEventListener(k,f){this.handlers[k]=f;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    input(){this.handlers.input?.();}
    change(){this.handlers.change?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const els=new Map(ids.map(id=>[id,new Element("div",id)]));
  const $=id=>{const element=els.get(id);assert.ok(element,id);return element;};
  const document={getElementById:$,createElement:t=>new Element(t),createElementNS:(_ns,t)=>new Element(t)};
  runInNewContext(script,{document});
  const src=i=>$("sourceList").children[i];
  const classify=(i,decision)=>src(i).children[4].children[decision].click();
  const include=i=>src(i).children.at(-1).click();
  const write=(id,value)=>{$(id).value=value;$(id).input();};
  const selectedInputs=()=>$("selectedList").children.map(el=>el.children[2].children[0]);
  write("thesis","A biblioteca deveria abrir em dois dias específicos, com transporte, equipe e custos avaliados.");
  for(let i=0;i<5;i++)classify(i,i===4?1:0);
  include(0);include(1);
  const inputs=selectedInputs();
  inputs[0].value="O questionário indica interesse de parte dos estudantes, mas não de toda a comunidade.";inputs[0].input();
  inputs[1].value="As visitas registradas no piloto indicam demanda naquele período observado.";inputs[1].input();
  assert.equal($("progress").value,3);
  $("counterList").children[1].click();
  assert.equal($("counterList").children[1].dataset.selected,"true");
  assert.ok($("counterList").children[1].focused);
  assert.match($("counterAdvice").textContent,/desigualdade de acesso/);
  write("counterAnalysis","Alguns estudantes dependem de transporte público e podem enfrentar riscos ao voltar para casa mais tarde.");
  assert.equal($("progress").value,4);
  write("rebuttal","A objeção é relevante. A ampliação só seria defensável em dias com transporte e equipe disponíveis, e deveria começar com um piloto avaliado de forma transparente.");
  assert.equal($("progress").value,5);
  assert.equal($("progressPercent").textContent,"100%");
  $("review").click();
  assert.equal($("reviewResult").dataset.state,"incomplete","manual reflection is still required");
  assert.ok($("reviewIssues").children.some(li=>li.textContent.includes("Autoavaliação")));
  for(const id of ["checkScope","checkLimits","checkCounter"]){$(id).checked=true;$(id).change();}
  $("review").click();
  assert.equal($("reviewResult").dataset.state,"complete");
  assert.equal($("reviewIssues").children.length,1);
  assert.match($("reviewIssues").children[0].textContent,/não avalia verdade/);
  assert.ok($("reviewTitle").focused);
  // Revisions cannot leave a stale successful review.
  write("rebuttal","Ainda não sei responder.");
  assert.equal($("progress").value,4);
  assert.equal($("reviewResult").hidden,true);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  assert.ok($("cancelReset").focused);
  $("cancelReset").click();
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("progress").value,4);
  $("reset").click();$("confirmReset").click();
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal($("thesis").value,"");
  assert.equal($("rebuttal").value,"");
  assert.equal($("counterAnalysis").value,"");
  assert.equal($("reviewResult").hidden,true);
  assert.equal($("selectedList").children.length,0);
  assert.equal($("counterList").children[1].dataset.selected,"false");
  assert.equal($("checkScope").checked,false);
  assert.ok($("thesis").focused);
});
test("catálogo acrescenta exclusivamente o exemplo 14 com pergunta inicial obrigatória",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  const prior=[
    "flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado",
    "votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo",
    "quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa",
    "explorador-de-imagens-com-pontos-interativos","planejador-de-aprendizagem",
    "linha-do-tempo-investigativa","simulador-de-cenarios-e-consequencias"
  ];
  assert.equal(catalog.version,1);
  assert.equal(catalog.category,"custom");
  assert.equal(catalog.examples.length,14);
  assert.deepEqual(catalog.examples.slice(0,13).map(x=>x.id),prior);
  const item=catalog.examples[13];
  assert.equal(item.id,"oficina-de-argumentos-e-evidencias");
  assert.equal(item.path,"custom/oficina-de-argumentos-e-evidencias.html");
  assert.match(item.prompt,/Na sua PRIMEIRA resposta/);
  assert.match(item.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(item.prompt,/Aguarde a resposta/);
  assert.match(item.prompt,/contra-argumentos|contra-argumento/i);
  for(const existing of catalog.examples.slice(0,13))assert.ok(existing.prompt.length>350);
});
