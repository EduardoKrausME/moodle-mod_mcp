/**
 * Independent interactive hotspot-image tests.
 * Run: node --test docs/pt_br/tests/image-hotspots.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/explorador-de-imagens-com-pontos-interativos.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("standalone SVG image with descriptive alternatives, responsive navigation and no remote dependencies",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|@import\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const pattern of [
    /<svg[^>]*viewBox="0 0 1000 600"/,/<title id="illustrationTitle">/,
    /<desc id="illustrationDescription">/,/role="img"/,/aria-labelledby="illustrationTitle illustrationDescription"/,
    /<progress id="progress"/,/role="region"/,/tabindex="0"/,
    /aria-live="polite"/,/role="status"/,/aria-expanded="false"/,
    /@media\(max-width:730px\)/,/prefers-reduced-motion:reduce/,
    /id="hotspots"/,/id="stepList"/,/id="reviewList"/,
    /id="resetConfirmation"/,/id="confirmReset"/,/id="nextPoint"/,
    /ArrowRight/,/ArrowLeft/,/Home/,/End/
  ]) assert.match(html,pattern);
  assert.doesNotMatch(script,/innerHTML\s*=|localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie/);
});
test("eight located hotspots form unique, complete educational explanations",async()=>{
  const {script}=await load();
  const source=script.match(/const points=(\[[\s\S]*?\]);\s*const \$=/);
  assert.ok(source);
  const points=runInNewContext(source[1]);
  assert.equal(points.length,8);
  assert.equal(new Set(points.map(p=>p.id)).size,8);
  for(const point of points){
    assert.ok(point.x>=10&&point.x<=90,"hotspot within visible art");
    assert.ok(point.y>=10&&point.y<=96,"hotspot within visible art");
    for(const key of ["title","subtitle","tag","explanation","insight","question"]){
      assert.ok(typeof point[key]==="string"&&point[key].length>=(key==="tag"?3:12),key+" for "+point.id);
    }
  }
  assert.equal(points[0].id,"nascente");
  assert.equal(points[7].id,"rio");
  assert.ok(points.some(p=>p.id==="eta"));
  assert.ok(points.some(p=>p.id==="ete"));
});
test("guided discovery, revisits, arrow navigation, review and confirmation reset",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.handlers={};this.attributes={};
      this.dataset={};this.style={};this.value="";this.textContent="";this.disabled=false;this.hidden=false;
      this.title="";this.className="";
    }
    append(...nodes){for(const n of nodes){n.parentNode=this;this.children.push(n);}}
    appendChild(n){n.parentNode=this;this.children.push(n);}
    replaceChildren(...nodes){this.children=[];this.append(...nodes);}
    setAttribute(k,v){this.attributes[k]=String(v);}
    getAttribute(k){return this.attributes[k];}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    keydown(key){if(this.disabled)return false;let prevented=false;this.handlers.keydown?.({key,preventDefault(){prevented=true;}});return prevented;}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const nodes=new Map(ids.map(id=>[id,new Element("div",id)]));
  for(const match of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const el=nodes.get(match[2]);el.tag=match[1].toLowerCase();el.hidden=/\shidden(?:\s|>)/.test(match[0]);
  }
  const document={
    getElementById(id){const el=nodes.get(id);assert.ok(el,"DOM element "+id);return el;},
    createElement(tag){return new Element(tag);}
  };
  runInNewContext(script,{document});
  const $=id=>nodes.get(id);
  const hot=()=>$("hotspots").children;
  const steps=()=>$("stepList").children.map(li=>li.children[0]);
  const review=()=>$("reviewList").children.map(li=>li.children[0]);
  assert.equal(hot().length,8);
  assert.equal(steps().length,8);
  assert.deepEqual(Array.from(hot(),h=>h.disabled),[false,true,true,true,true,true,true,true]);
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.ok($("reviewToggle").disabled);
  assert.equal($("nextPoint").disabled,false,"start action should be available");
  assert.equal($("nextPoint").textContent,"Começar pelo ponto 1");
  assert.equal(hot()[0].keydown("ArrowRight"),true);
  assert.ok(hot()[0].focused);
  // Visited through textual list, then map and next action.
  steps()[0].click();
  assert.equal($("progress").value,1);
  assert.equal($("progressPercent").textContent,"13%");
  assert.equal(hot()[1].disabled,false);
  assert.ok(!$("nextPoint").disabled);
  assert.ok(!$("reviewToggle").disabled);
  assert.equal($("detailsTitle").textContent,"Nascente e bacia de captação");
  assert.equal($("reflection").hidden,false);
  assert.equal(steps()[0].children[2].textContent,"Visitado");
  // Arrow moves focus without counting a visit.
  hot()[0].keydown("ArrowRight");
  assert.ok(hot()[1].focused);
  assert.equal($("progress").value,1);
  hot()[0].keydown("End");
  assert.ok(hot()[1].focused);
  hot()[1].click();
  assert.equal($("progress").value,2);
  hot()[1].click();
  assert.equal($("progress").value,2,"revisit does not add a second discovery");
  $("nextPoint").click();
  assert.equal($("progress").value,3);
  assert.ok(hot()[2].focused,"next button focuses the new hotspot");
  assert.equal($("detailsTitle").textContent,"Estação de tratamento de água");
  // The review area opens only for visited items.
  $("reviewToggle").click();
  assert.equal($("reviewPanel").hidden,false);
  assert.equal($("reviewToggle").getAttribute("aria-expanded"),"true");
  assert.equal(review().length,3);
  assert.ok($("reviewTitle").focused);
  review()[0].click();
  assert.equal($("progress").value,3);
  assert.ok(hot()[0].focused,"review moves focus to the matching hotspot");
  // Dismissal and cancellation keep current progress.
  $("reviewToggle").click();
  assert.equal($("reviewPanel").hidden,true);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  assert.ok($("cancelReset").focused);
  $("cancelReset").click();
  assert.equal($("progress").value,3);
  assert.equal($("resetConfirmation").hidden,true);
  // Continue guided route through each remaining stop.
  for(let i=3;i<8;i++)hot()[i].click();
  assert.equal($("progress").value,8);
  assert.equal($("progressPercent").textContent,"100%");
  assert.equal($("completion").hidden,false);
  assert.equal($("nextPoint").disabled,true);
  assert.equal(review().length,8,"review list refreshes as points are visited");
  $("reset").click();$("confirmReset").click();
  assert.equal($("progress").value,0);
  assert.equal($("completion").hidden,true);
  assert.equal($("reviewPanel").hidden,true);
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("reviewToggle").disabled,true);
  assert.equal(hot()[0].disabled,false);
  assert.equal(hot()[7].disabled,true);
  assert.ok(hot()[0].focused);
});
test("free exploration permits out-of-order visits and guided mode preserves them",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.dataset={};this.attributes={};this.style={};this.hidden=false;this.disabled=false;this.textContent="";}
    append(...items){this.children.push(...items);}
    appendChild(item){this.children.push(item);}
    replaceChildren(...items){this.children=items;}
    setAttribute(k,v){this.attributes[k]=String(v);}
    addEventListener(k,f){this.handlers[k]=f;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const els=new Map(ids.map(id=>[id,new Element("div",id)]));
  const document={getElementById(id){const el=els.get(id);assert.ok(el);return el;},createElement(tag){return new Element(tag);}};
  runInNewContext(script,{document});
  const $=id=>els.get(id),hot=()=>$("hotspots").children;
  assert.equal(hot()[7].disabled,true);
  $("freeMode").click();
  assert.equal($("freeMode").attributes["aria-pressed"],"true");
  assert.equal(hot()[7].disabled,false);
  hot()[7].click();
  assert.equal($("progress").value,1);
  assert.equal($("detailsTitle").textContent,"Rio e proteção do ambiente");
  $("freeMode").click();
  assert.equal(hot()[7].disabled,false,"already visited remains available");
  assert.equal(hot()[6].disabled,true,"unvisited late stage locks again");
  assert.equal(hot()[1].disabled,true,"guided mode waits for the first unvisited point");
  assert.equal(hot()[0].disabled,false,"first unvisited point is available");
  $("nextPoint").click();
  assert.equal($("progress").value,2);
  assert.equal(hot()[0].dataset.visited,"true");
  assert.equal(hot()[1].disabled,false,"second point unlocks only after first discovery");
  assert.equal(hot()[7].dataset.visited,"true");
  assert.equal($("progressPercent").textContent,"25%");
});
test("catalog adds one new entry without modifying the previous nine",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  const previous=["flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado","votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo","quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa"];
  assert.equal(catalog.category,"custom");
  assert.equal(catalog.version,1);
  assert.ok(catalog.examples.length>=10,"Exemplos posteriores não invalidam o décimo registro.");
  assert.deepEqual(catalog.examples.slice(0,9).map(e=>e.id),previous);
  const item=catalog.examples[9];
  assert.equal(item.id,"explorador-de-imagens-com-pontos-interativos");
  assert.equal(item.path,"custom/explorador-de-imagens-com-pontos-interativos.html");
  assert.match(item.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(item.prompt,/Aguarde a resposta/);
  assert.match(item.prompt,/revisão dos itens visitados/);
  for(const old of catalog.examples.slice(0,9))assert.ok(old.prompt.length>350);
});
