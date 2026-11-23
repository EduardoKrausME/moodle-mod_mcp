/**
 * Independent interactive decision-matrix tests.
 * Run: node --test docs/pt_br/tests/decision-matrix.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/matriz-de-decisao-interativa.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("standalone, responsive, keyboard-accessible activity with no remote dependencies",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|@import\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const pattern of [
    /<table class="matrix"/,/<caption class="sr-only">/,/role="region"/,/tabindex="0"/,
    /\.bar-track\{/,/role="status"/,/aria-live="polite"/,
    /@media\(max-width:700px\)/,/prefers-reduced-motion:reduce/,
    /id="scenarioCriterion"/,/id="scenarioWeight"/,/type="range"/,
    /id="resetConfirmation"/,/id="confirmReset"/,/maxLength=115/,
    /score\.setAttribute\("aria-label"/,/reason\.setAttribute\("aria-label"/,
    /\.scope="col"/
  ]) assert.match(html,pattern);
  assert.doesNotMatch(script,/innerHTML\s*=|fetch\(|localStorage|sessionStorage|XMLHttpRequest|document\.cookie/);
});
test("seed data has valid unique criteria, alternatives and scores",async()=>{
  const {script}=await load();
  const match=script.match(/const seed=(\{[\s\S]*?\});\s*const state=/);
  assert.ok(match);
  const seed=runInNewContext("("+match[1]+")");
  assert.equal(seed.criteria.length,4);
  assert.equal(seed.alternatives.length,3);
  assert.equal(new Set(seed.criteria.map(c=>c.id)).size,4);
  assert.equal(new Set(seed.alternatives.map(a=>a.id)).size,3);
  for(const criterion of seed.criteria){
    assert.ok(Number.isInteger(criterion.weight)&&criterion.weight>=0&&criterion.weight<=5);
    for(const alternative of seed.alternatives){
      const rating=alternative.ratings[criterion.id];
      assert.ok(rating&&Number.isInteger(rating.value)&&rating.value>=1&&rating.value<=5);
      assert.ok(rating.reason.length>15);
    }
  }
  const sumWeights=seed.criteria.reduce((n,c)=>n+c.weight,0);
  assert.equal(sumWeights,14);
  const avg=a=>seed.criteria.reduce((n,c)=>n+c.weight*a.ratings[c.id].value,0)/sumWeights;
  assert.ok(avg(seed.alternatives[1])>avg(seed.alternatives[2]));
  assert.ok(avg(seed.alternatives[2])>avg(seed.alternatives[0]));
});
test("add/rename/delete model, edit weights and scores, ties, all-zero and reset",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;
      this.handlers={};this.attributes={};this.dataset={};this.style={};
      this.value="";this.textContent="";this.disabled=false;this.hidden=false;
    }
    append(...nodes){for(const node of nodes){node.parentNode=this;this.children.push(node);}}
    appendChild(node){node.parentNode=this;this.children.push(node);}
    replaceChildren(...nodes){this.children=[];this.append(...nodes);}
    setAttribute(key,value){this.attributes[key]=String(value);}
    getAttribute(key){return this.attributes[key];}
    addEventListener(type,handler){this.handlers[type]=handler;}
    focus(){this.focused=true;}
    click(){this.handlers.click?.();}
    change(){this.handlers.change?.();}
    input(){this.handlers.input?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes=new Map(ids.map(id=>[id,new Element("div",id)]));
  for(const match of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const el=nodes.get(match[2]);el.tag=match[1].toLowerCase();el.hidden=/\shidden(?:\s|>)/.test(match[0]);
  }
  const document={
    getElementById(id){const el=nodes.get(id);assert.ok(el,"Missing element "+id);return el;},
    createElement(tag){return new Element(tag);}
  };
  runInNewContext(script,{document});
  const $=id=>nodes.get(id);
  const rows=()=>$("matrixBody").children;
  const score=(r,col)=>rows()[r].children[col].children[0].children[1];
  const reason=(r,col)=>rows()[r].children[col].children[1].children[1];
  const weight=r=>rows()[r].children[0].children[1].children[1];
  const alternativeInput=index=>$("alternativeList").children[index].children[0].children[1];
  const criterionInput=index=>$("criterionList").children[index].children[0].children[1];
  assert.equal(rows().length,4);
  assert.equal($("matrixHead").children[0].children.length,4);
  assert.equal($("ranking").children.length,3);
  assert.equal($("ranking").children[0].children[0].children[0].children[1].textContent,"Quiz com feedback");
  assert.match($("leaderText").textContent,/Quiz com feedback/);
  assert.match($("scenarioSummary").textContent,/permanece/);
  // Sensitivity is a preview: reducing time from 3 to 0 makes project leader, while current ranking remains quiz.
  $("scenarioWeight").value="0";$("scenarioWeight").input();
  assert.match($("scenarioSummary").textContent,/liderança mudaria/);
  assert.match($("scenarioSummary").textContent,/Projeto colaborativo/);
  assert.match($("leaderText").textContent,/Quiz com feedback/);
  assert.equal(weight(1).value,"3");
  $("restoreScenario").click();
  assert.equal($("scenarioWeight").value,"3");
  $("scenarioWeight").value="0";$("scenarioWeight").input();$("applyScenario").click();
  assert.equal(weight(1).value,"0");
  assert.match($("leaderText").textContent,/Projeto colaborativo/);
  assert.equal($("scenarioWeight").focused,true);
  // Invalid weight and score must not overwrite valid data.
  weight(1).value="9";weight(1).change();
  assert.equal(weight(1).value,"0");
  score(0,1).value="9";score(0,1).change();
  assert.equal(score(0,1).value,"5");
  reason(0,1).value="Uma observação feita pelo estudante.";
  reason(0,1).change();
  assert.match($("ranking").children.find(row=>row.getAttribute("aria-label")?.includes("Oficina prática"))?.children[2].textContent||"",/Uma observação/);
  // Adding one criterion and option generates editable score cells.
  $("criterionName").value="Adequação ao público";
  assert.equal($("addCriterion").submit(),true);
  assert.equal(rows().length,5);
  assert.equal($("criterionCount").textContent,"5/6");
  $("alternativeName").value="Debate orientado";
  assert.equal($("addAlternative").submit(),true);
  assert.equal($("matrixHead").children[0].children.length,5);
  assert.equal($("alternativeCount").textContent,"4/5");
  assert.equal(score(4,4).value,"3");
  const addedName=alternativeInput(3);
  addedName.value="Debate com exemplos";addedName.change();
  assert.equal($("matrixHead").children[0].children[4].textContent,"Debate com exemplos");
  const addedCriterion=criterionInput(4);
  addedCriterion.value="Facilidade para iniciantes";addedCriterion.change();
  assert.equal(rows()[4].children[0].children[0].textContent,"Facilidade para iniciantes");
  // Duplicate names are rejected.
  alternativeInput(3).value="Oficina prática";alternativeInput(3).change();
  assert.equal(alternativeInput(3).value,"Debate com exemplos");
  // Remove added criterion and alternative, but keep the original ones.
  $("criterionList").children[4].children[1].click();
  assert.equal(rows().length,4);
  $("alternativeList").children[3].children[1].click();
  assert.equal($("ranking").children.length,3);
  // Force a real tie using identical score values for all alternatives.
  for(let r=0;r<4;r++)for(let col=1;col<=3;col++){
    score(r,col).value="3";score(r,col).change();
  }
  assert.match($("leaderTitle").textContent,/Empate/);
  assert.deepEqual(Array.from($("ranking").children,row=>row.children[0].children[0].children[0].textContent),["1º lugar","1º lugar","1º lugar"]);
  // All weights zero produces no synthetic winner.
  for(let r=0;r<4;r++){weight(r).value="0";weight(r).change();}
  assert.equal($("leaderTitle").textContent,"Sem pesos ativos");
  assert.equal($("ranking").children.length,1);
  assert.match($("scenarioSummary").textContent,/sem classificação|primeira classificação|Sem pesos ativos nos dois cenários/i);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  $("cancelReset").click();
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("leaderTitle").textContent,"Sem pesos ativos");
  $("reset").click();$("confirmReset").click();
  assert.equal(rows().length,4);
  assert.equal($("alternativeList").children.length,3);
  assert.equal($("criterionList").children.length,4);
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("reset").focused,true);
  assert.match($("leaderText").textContent,/Quiz com feedback/);
  // Minimum number of alternatives and criteria is enforced.
  $("alternativeList").children[2].children[1].click();
  assert.equal($("alternativeList").children.length,2);
  assert.equal($("alternativeList").children[0].children[1].disabled,true);
  $("criterionList").children[3].children[1].click();
  $("criterionList").children[2].children[1].click();
  assert.equal(rows().length,2);
  assert.equal($("criterionList").children[0].children[1].disabled,true);
});
test("catalog adds only ninth entry; teacher question precedes generation",async()=>{
  const m=JSON.parse(await read("custom/examples.json"));
  const previous=["flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado","votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo","quadro-de-organizacao-de-ideias","construtor-de-fluxogramas"];
  assert.equal(m.version,1);
  assert.equal(m.category,"custom");
  assert.equal(m.examples.length,9);
  assert.deepEqual(m.examples.slice(0,8).map(e=>e.id),previous);
  const e=m.examples[8];
  assert.equal(e.id,"matriz-de-decisao-interativa");
  assert.equal(e.path,"custom/matriz-de-decisao-interativa.html");
  assert.match(e.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(e.prompt,/Aguarde a resposta/);
  assert.match(e.prompt,/análise de sensibilidade/i);
  assert.match(e.prompt,/empates/);
  for(const old of m.examples.slice(0,8)) assert.ok(old.prompt.length>350);
});
