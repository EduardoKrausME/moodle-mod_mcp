/**
 * Testes independentes da atividade Laboratório de diagnóstico e correção.
 * Execute: node --test docs/pt_br/tests/diagnostic-lab.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/laboratorio-de-diagnostico-e-correcao.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script,"Script embutido presente");
  return {html,script};
};
class Element {
  constructor(tag,id=""){
    this.tag=tag;this.id=id;this.children=[];this.parentNode=null;
    this.handlers={};this.attributes={};this.dataset={};this.style={};
    this.value="";this.textContent="";this.hidden=false;this.disabled=false;
    this.className="";this.checked=false;this.scope="";this.colSpan=1;
  }
  append(...items){for(const n of items){n.parentNode=this;this.children.push(n);}}
  appendChild(n){n.parentNode=this;this.children.push(n);}
  replaceChildren(...items){this.children=[];this.append(...items);}
  setAttribute(key,value){this.attributes[key]=String(value);}
  getAttribute(key){return this.attributes[key];}
  addEventListener(kind,handler){this.handlers[kind]=handler;}
  focus(){this.focused=true;}
  click(){if(!this.disabled)this.handlers.click?.();}
  input(){this.handlers.input?.();}
  change(){this.handlers.change?.();}
}
const mount=({html,script})=>{
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(ids.map(id=>[id,new Element("div",id)]));
  for(const match of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const el=elements.get(match[2]);
    el.tag=match[1].toLowerCase();
    el.hidden=/\shidden(?:\s|>)/.test(match[0]);
    if(el.tag==="progress")el.value=0;
  }
  const $=id=>{const el=elements.get(id);assert.ok(el,"missing "+id);return el;};
  const document={getElementById:$,createElement:tag=>new Element(tag)};
  runInNewContext(script,{document});
  const select=i=>$("moduleNav").children[i].children[0].click();
  const apply=(index,option)=>{
    select(index);$("config").value=option;$("apply").click();
  };
  const note=(index,value)=>{
    select(index);$("hypothesis").value=value;$("hypothesis").input();
  };
  const reading=(row,col)=>$("readingRows").children[row].children[col].textContent;
  const outcomes=()=>$("testResults").children.map(li=>li.dataset.pass);
  return {$,select,apply,note,reading,outcomes};
};
test("HTML, SVG original, acessibilidade, responsividade e nenhuma dependência remota",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<iframe\b|<footer\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const p of [
    /<svg id="diagram"/,/<title id="diagramTitle">/,/<desc id="diagramDescription">/,
    /aria-labelledby="diagramTitle diagramDescription"/,/role="img"/,
    /id="moduleNav"/,/id="config"/,/id="hypothesis"/,/id="apply"/,
    /id="testResults"/,/id="readingRows"/,/<table>/,/<caption class="sr-only">/,
    /<th scope="col">/,/role="region"/,/tabindex="0"/,
    /aria-live="polite"/,/role="status"/,/id="progress"/,/id="undo"/,
    /id="resetConfirmation"/,/id="confirmReset"/,/prefers-reduced-motion:reduce/,
    /@media\(max-width:540px\)/,/setAttribute\("aria-label"/
  ])assert.match(html,p);
  assert.doesNotMatch(script,/innerHTML\s*=|document\.write\(|localStorage|sessionStorage|XMLHttpRequest|fetch\(|document\.cookie/);
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  assert.equal(ids.length,new Set(ids).size);
  for(const match of script.matchAll(/\$\("([^"]+)"\)/g))assert.ok(ids.includes(match[1]),"DOM ID "+match[1]);
});
test("cinco módulos com opções distintas, regras válidas e situação inicial incorreta",async()=>{
  const {script}=await load();
  const match=script.match(/const modules=(\[[\s\S]*?\]);\s*const original=(\{[\s\S]*?\});\s*const \$=/);
  assert.ok(match);
  const modules=runInNewContext("("+match[1]+")");
  const initial=runInNewContext("("+match[2]+")");
  assert.deepEqual(modules.map(m=>m.id),["sensor","cadence","unit","clock","alarm"]);
  assert.equal(new Set(modules.map(m=>m.id)).size,5);
  assert.equal(modules.reduce((total,m)=>total*m.options.length,1),243);
  for(const m of modules){
    assert.equal(m.options.length,3);
    assert.equal(new Set(m.options.map(o=>o.id)).size,3);
    assert.ok(m.options.some(o=>o.id===m.correct));
    assert.ok(m.options.some(o=>o.id===initial[m.id]));
    assert.ok(m.correct!==initial[m.id]);
    assert.ok(m.title.length>=5);
    for(const key of ["story","requirement"])assert.ok(m[key].length>10);
  }
});
test("diagnóstico completo, testes desatualizados, comparação, desfazer, notas e confirmação de reinício",async()=>{
  const app=mount(await load());
  const {$,select,apply,note,reading,outcomes}=app;
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal($("moduleNav").children.length,5);
  assert.equal($("undo").disabled,true);
  assert.equal($("completion").hidden,true);
  assert.equal($("history").children.length,0);
  assert.equal($("emptyReport").hidden,false);
  select(0);
  assert.equal($("progress").value,1);
  note(0,"O sensor pode ter um desvio de três graus.");
  assert.equal($("progress").value,2);
  $("run").click();
  assert.equal($("testResults").children.length,5);
  assert.deepEqual(outcomes(),["false","false","false","false","false"]);
  assert.equal($("reportSummary").dataset.kind,"fail");
  assert.equal(reading(0,1),"80,6 °F");
  assert.equal(reading(0,2),"17:00 UTC");
  assert.equal(reading(0,3),"Alerta disparado");
  assert.equal(reading(1,1),"Sem leitura");
  assert.equal($("history").children.length,1);
  assert.equal($("history").children[0].children[0].textContent,"Rodada 1 · 0/5 requisitos");
  assert.ok($("reportTitle").focused);
  // Uma configuração alterada invalida a rodade anterior.
  apply(0,"calibrated");
  assert.equal($("reportSummary").dataset.kind,"stale");
  assert.equal($("progress").value,2);
  assert.equal($("undo").disabled,false);
  assert.match($("reportText").textContent,/execute novamente/);
  $("undo").click();
  assert.equal($("config").value,"offset");
  assert.equal($("reportSummary").dataset.kind,"stale");
  assert.equal($("hypothesis").value,"O sensor pode ter um desvio de três graus.");
  // Conserte todos os módulos; a hipótese é específica de cada módulo.
  apply(0,"calibrated");
  note(1,"A coleta espaçada pode perder o aquecimento curto.");
  apply(1,"5");
  note(2,"A escala Fahrenheit não atende ao relatório solicitado.");
  apply(2,"celsius");
  apply(3,"local");
  apply(4,"28");
  assert.equal($("progress").value,8);
  assert.equal($("progressPercent").textContent,"62%");
  assert.equal($("completion").hidden,true,"A missão ainda precisa de teste recente.");
  assert.equal($("svgAlarm").dataset.state,"reviewed");
  $("run").click();
  assert.deepEqual(outcomes(),["true","true","true","true","true"]);
  assert.equal($("reportSummary").dataset.kind,"pass");
  assert.equal($("progress").value,13);
  assert.equal($("progressPercent").textContent,"100%");
  assert.equal($("completion").hidden,false);
  assert.equal(reading(0,1),"24,0 °C");
  assert.equal(reading(1,1),"32,0 °C");
  assert.equal(reading(0,2),"14:00");
  assert.equal(reading(1,2),"14:10");
  assert.equal(reading(0,3),"Sem alerta");
  assert.equal(reading(1,3),"Alerta disparado");
  assert.equal($("svgSensor").dataset.state,"pass");
  assert.match($("comparison").textContent,/\+5 requisito\(s\)/);
  assert.equal($("history").children[0].children[0].textContent,"Rodada 2 · 5/5 requisitos");
  // Um ajuste posterior não pode continuar usando a aprovação anterior.
  apply(3,"utc");
  assert.equal($("reportSummary").dataset.kind,"stale");
  assert.equal($("completion").hidden,true);
  assert.equal($("progress").value,8);
  $("undo").click();
  assert.equal($("config").value,"local");
  assert.equal($("reportSummary").dataset.kind,"stale");
  $("run").click();
  assert.equal($("progress").value,13);
  assert.equal($("history").children[0].children[0].textContent,"Rodada 3 · 5/5 requisitos");
  // A retenção do histórico não faz a numeração voltar ao número 9.
  for(let i=0;i<9;i++)$("run").click();
  assert.equal($("history").children.length,8);
  assert.equal($("history").children[0].children[0].textContent,"Rodada 12 · 5/5 requisitos");
  assert.equal($("history").children.at(-1).children[0].textContent,"Rodada 5 · 5/5 requisitos");
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  assert.ok($("cancelReset").focused);
  $("cancelReset").click();
  assert.equal($("progress").value,13);
  assert.equal($("completion").hidden,false);
  $("resetAside").click();
  $("confirmReset").click();
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal($("history").children.length,0);
  assert.equal($("historyEmpty").hidden,false);
  assert.equal($("comparison").hidden,true);
  assert.equal($("emptyReport").hidden,false);
  assert.equal($("completion").hidden,true);
  assert.equal($("undo").disabled,true);
  assert.equal($("resetConfirmation").hidden,true);
  assert.equal($("moduleNav").children[0].children[0].focused,true);
  select(0);
  assert.equal($("hypothesis").value,"");
  assert.equal($("config").value,"offset");
  $("run").click();
  assert.equal($("history").children[0].children[0].textContent,"Rodada 1 · 0/5 requisitos");
});
test("dependências: leitura ausente, amostragem perdida e alerta indevido não aprovam a missão",async()=>{
  const app=mount(await load());
  const {$,apply,reading,outcomes,select}=app;
  apply(0,"offline");
  apply(1,"5");
  apply(2,"celsius");
  apply(3,"local");
  apply(4,"28");
  $("run").click();
  assert.equal(reading(0,1),"Sem leitura");
  assert.equal(reading(1,1),"Sem leitura");
  assert.equal(reading(0,3),"Sem dados");
  assert.deepEqual(outcomes(),["false","true","true","true","false"]);
  assert.match($("testResults").children[4].children[1].children[1].textContent,/leitura ausente/);
  apply(0,"calibrated");
  apply(1,"60");
  $("run").click();
  assert.equal(reading(1,1),"Sem leitura");
  assert.equal(reading(1,2),"Sem horário de evento");
  assert.equal($("testResults").children[4].dataset.pass,"false");
  apply(1,"5");
  apply(4,"20");
  $("run").click();
  assert.equal(reading(0,3),"Alerta disparado");
  assert.equal($("testResults").children[4].dataset.pass,"false");
  apply(4,"28");
  $("run").click();
  assert.deepEqual(outcomes(),["true","true","true","true","true"]);
  assert.equal($("completion").hidden,true,"Sem três hipóteses, a missão não está concluída.");
  // Hypotheses contribute after a valid report, without falsely needing another run.
  app.note(0,"A falha inicial foi no sensor desconectado.");
  app.note(1,"O evento precisa de amostragem mais frequente.");
  app.note(2,"A unidade deve estar indicada no relatório.");
  assert.equal($("completion").hidden,false);
  assert.equal($("progress").value,13);
  // Navegação pelos botões mantém foco na configuração relevante.
  $("previousModule").click();
  assert.equal($("moduleHeading").textContent,"Amostragem");
  assert.ok($("config").focused);
  $("nextModule").click();
  assert.equal($("moduleHeading").textContent,"Unidade");
  assert.equal($("config").value,"celsius");
});
test("a lista de opções rejeita configuração inválida sem corromper o estado",async()=>{
  const {$,select,apply}=mount(await load());
  select(0);
  $("config").value="nonsense";
  $("apply").click();
  assert.equal($("config").value,"offset");
  assert.equal($("undo").disabled,true);
  assert.match($("announcer").textContent,/Configuração inválida/);
  apply(0,"calibrated");
  assert.equal($("undo").disabled,false);
});
test("as 243 combinações de configurações produzem resultados completos sem falsos aprovados",async()=>{
  const app=mount(await load());
  const {$,apply,outcomes}=app;
  const configs=[
    ["offset","calibrated","offline"],
    ["60","5","1"],
    ["fahrenheit","celsius","bare"],
    ["utc","local","none"],
    ["20","28","off"]
  ];
  let runs=0,fullyPassing=0;
  for(const a of configs[0])for(const b of configs[1])
  for(const c of configs[2])for(const d of configs[3])for(const e of configs[4]){
    const values=[a,b,c,d,e];
    for(let i=0;i<5;i++)apply(i,values[i]);
    $("run").click();
    runs++;
    const outcomesNow=outcomes();
    assert.equal(outcomesNow.length,5);
    assert.ok(outcomesNow.every(v=>v==="true"||v==="false"));
    const passed=outcomesNow.filter(x=>x==="true").length;
    assert.ok(passed>=0&&passed<=5);
    assert.ok($("reportText").textContent.length>20);
    assert.equal($("readingRows").children.length,2);
    if(passed===5){
      fullyPassing++;
      assert.deepEqual(values,["calibrated","5","celsius","local","28"]);
    }
    assert.equal($("completion").hidden,true,"Testes sozinhos não substituem hipóteses autorais.");
  }
  assert.equal(runs,243);
  assert.equal(fullyPassing,1);
  assert.equal($("history").children.length,8);
  assert.equal($("history").children[0].children[0].textContent,"Rodada 243 · 0/5 requisitos");
});
test("15º registro adicionado sem alterar os quatorze exemplos anteriores ou seus prompts",async()=>{
  const m=JSON.parse(await read("custom/examples.json"));
  const previous=[
    "flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado",
    "votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo",
    "quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa",
    "explorador-de-imagens-com-pontos-interativos","planejador-de-aprendizagem",
    "linha-do-tempo-investigativa","simulador-de-cenarios-e-consequencias","oficina-de-argumentos-e-evidencias"
  ];
  assert.equal(m.version,1);
  assert.equal(m.category,"custom");
  assert.ok(m.examples.length>=15);
  assert.deepEqual(m.examples.slice(0,14).map(e=>e.id),previous);
  const current=m.examples[14];
  assert.equal(current.id,"laboratorio-de-diagnostico-e-correcao");
  assert.equal(current.path,"custom/laboratorio-de-diagnostico-e-correcao.html");
  assert.match(current.prompt,/Na sua PRIMEIRA resposta/);
  assert.match(current.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(current.prompt,/Aguarde a resposta/);
  assert.match(current.prompt,/hipóteses|hipótese/);
  assert.match(current.prompt,/desatualizados|desatualizado/);
  for(const previousEntry of m.examples.slice(0,14))assert.ok(previousEntry.prompt.length>350);
});
