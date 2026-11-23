/**
 * Testes independentes do simulador de cenários e consequências.
 * Executar: node --test docs/pt_br/tests/scenario-simulator.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/simulador-de-cenarios-e-consequencias.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
class Element {
  constructor(tag,id=""){
    this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.handlers={};
    this.attributes={};this.dataset={};this.style={};this.value="";this.textContent="";
    this.hidden=false;this.disabled=false;this.className="";
  }
  append(...items){for(const n of items){n.parentNode=this;this.children.push(n);}}
  appendChild(n){n.parentNode=this;this.children.push(n);}
  replaceChildren(...items){this.children=[];this.append(...items);}
  setAttribute(k,v){this.attributes[k]=String(v);}
  getAttribute(k){return this.attributes[k];}
  addEventListener(k,fn){this.handlers[k]=fn;}
  focus(){this.focused=true;}
  click(){if(!this.disabled)this.handlers.click?.();}
}
const mount=({html,script})=>{
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const elements=new Map(ids.map(id=>[id,new Element("div",id)]));
  const $=id=>{const e=elements.get(id);assert.ok(e,"DOM element "+id);return e;};
  const document={getElementById:$,createElement:tag=>new Element(tag)};
  runInNewContext(script,{document});
  const choose=index=>{
    const list=$("choiceList").children;
    assert.equal(list.length,3);
    list[index].children[0].click();
  };
  const review=index=>{
    const list=$("history").children;
    assert.ok(list[index],"review "+index);
    list[index].children.at(-1).click();
  };
  const value=index=>{
    const el=$("metrics").children[index];
    return Number(el.children[0].children[1].textContent.replace("/100",""));
  };
  return {$,choose,review,value};
};
test("HTML e SVG originais, acessíveis e independentes",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<footer\b|<iframe\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const re of [
    /<svg class="scene-svg"/,/<title id="sceneSvgTitle">/,/<desc id="sceneSvgDesc">/,
    /aria-labelledby="sceneSvgTitle sceneSvgDesc"/,/role="img"/,
    /role="status"/,/aria-live="polite"/,/aria-atomic="true"/,
    /<progress id="stageProgress"/,/id="sceneNode0"/,/id="sceneNode3"/,
    /id="saveBaseline"/,/id="comparisonBody"/,/id="history"/,
    /id="confirmReset"/,/id="resetConfirmation"/,/id="lastFeedbackAdjustment"/,/@media\(max-width:740px\)/,
    /prefers-reduced-motion:reduce/,/aria-label/,/tabindex="-1"/
  ]) assert.match(html,re);
  assert.doesNotMatch(script,/innerHTML\s*=|localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie/);
});
test("modelo de quatro etapas valida escolhas, impactos e contextos",async()=>{
  const {script}=await load();
  const match=script.match(/const indicators=(\[[\s\S]*?\]);\s*const seed=(\{[\s\S]*?\});\s*const stages=(\[[\s\S]*?\]);\s*const \$=/);
  assert.ok(match);
  const indicators=runInNewContext("("+match[1]+")");
  const seed=runInNewContext("("+match[2]+")");
  const stages=runInNewContext("("+match[3]+")");
  assert.deepEqual(Array.from(indicators,x=>x.key),["resources","participation","sustainability","trust"]);
  assert.deepEqual(Object.values(seed),[70,45,55,50]);
  assert.equal(stages.length,4);
  assert.equal(new Set(stages.map(s=>s.id)).size,4);
  assert.equal(stages.reduce((acc,s)=>acc*s.options.length,1),81);
  for(const [idx,stage] of stages.entries()){
    assert.equal(stage.options.length,3);
    assert.equal(new Set(stage.options.map(o=>o.id)).size,3);
    assert.ok(stage.question.length>15);
    if(idx>0){
      assert.ok(stage.contextKey in seed);
      assert.ok(stage.high.length>30&&stage.low.length>30);
    }
    for(const option of stage.options){
      assert.ok(option.feedback.length>40);
      assert.ok(option.detail.length>20);
      assert.deepEqual(Object.keys(option.delta),["resources","participation","sustainability","trust"]);
      for(const value of Object.values(option.delta))assert.ok(Number.isInteger(value));
    }
  }
});
test("decisões encadeadas, contextos variáveis, revisão, comparação e reinício",async()=>{
  const app=mount(await load());
  const {$,choose,review,value}=app;
  assert.equal($("stageProgress").value,0);
  assert.equal($("stagePercent").textContent,"0%");
  assert.equal($("undo").disabled,true);
  assert.equal($("saveBaseline").disabled,true);
  assert.deepEqual([0,1,2,3].map(value),[70,45,55,50]);
  assert.equal($("choiceList").children.length,3);
  assert.equal($("sceneNode0").dataset.phase,"active");
  choose(0); // convite aberto
  assert.equal($("stageProgress").value,1);
  assert.equal($("stagePercent").textContent,"25%");
  assert.equal($("undo").disabled,false);
  assert.deepEqual([0,1,2,3].map(value),[54,64,54,60]);
  assert.match($("stageStory").textContent,/atraiu bastante interesse/);
  assert.equal($("history").children.length,1);
  assert.equal($("sceneNode0").dataset.phase,"done");
  assert.equal($("sceneNode1").dataset.phase,"active");
  assert.match($("lastFeedbackText").textContent,/amplia a participação/);
  assert.equal($("lastFeedbackAdjustment").hidden,true,"sem saturação não há aviso de ajuste");
  assert.match($("announcer").textContent,/Próxima etapa disponível/);
  choose(2); // empréstimo
  assert.deepEqual([0,1,2,3].map(value),[57,74,66,67]);
  choose(2); // remoto
  assert.deepEqual([0,1,2,3].map(value),[50,77,72,69]);
  choose(0); // mostra pública
  assert.equal($("stageProgress").value,4);
  assert.equal($("stagePercent").textContent,"100%");
  assert.deepEqual([0,1,2,3].map(value),[41,89,76,83]);
  assert.equal($("choicePanel").hidden,true);
  assert.equal($("completePanel").hidden,false);
  assert.ok($("outcomeTitle").focused);
  assert.equal($("history").children.length,4);
  assert.equal($("saveBaseline").disabled,false);
  $("saveBaseline").click();
  assert.equal($("compareResults").hidden,false);
  assert.match($("compareInfo").textContent,/idênticos/);
  assert.equal($("comparisonBody").children.length,4);
  assert.match($("baselineRoute").textContent,/Convite aberto/);
  assert.equal($("saveBaseline").focused,true);
  review(1);
  assert.equal($("stageProgress").value,1,"revisão remove decisões posteriores");
  assert.equal($("choicePanel").hidden,false);
  assert.equal($("compareResults").hidden,true,"comparação não mostra caminho incompleto");
  assert.match($("compareInfo").textContent,/1 de quatro escolhas/);
  assert.ok($("decisionTitle").focused);
  assert.deepEqual([0,1,2,3].map(value),[54,64,54,60]);
  choose(1); // compra
  assert.equal($("stageProgress").value,2);
  assert.deepEqual([0,1,2,3].map(value),[31,72,39,63]);
  assert.match($("stageStory").textContent,/recursos ficaram escassos/);
  choose(0); // espaço coberto
  assert.deepEqual([0,1,2,3].map(value),[17,83,36,72]);
  assert.match($("lastFeedbackWarning").textContent,/recursos ficou abaixo de 30/);
  choose(1); // relatório compacto
  assert.equal($("stageProgress").value,4);
  assert.deepEqual([0,1,2,3].map(value),[22,80,32,76]);
  assert.equal($("compareResults").hidden,false);
  assert.match($("compareInfo").textContent,/3 escolha\(s\) diferente\(s\)/);
  assert.equal($("comparisonBody").children.length,4);
  const resourceRow=$("comparisonBody").children[0];
  assert.equal(resourceRow.children[1].textContent,"41");
  assert.equal(resourceRow.children[2].textContent,"22");
  assert.equal(resourceRow.children[3].textContent,"-19");
  assert.equal(resourceRow.children[3].dataset.trend,"down");
  assert.match($("comparisonSummary").textContent,/não comprova/);
  assert.match($("baselineRoute").textContent,/Convite aberto/);
  $("undo").click();
  assert.equal($("stageProgress").value,3);
  assert.equal($("compareResults").hidden,true);
  assert.equal($("saveBaseline").disabled,true);
  assert.deepEqual([0,1,2,3].map(value),[17,83,36,72]);
  choose(1);
  assert.equal($("compareResults").hidden,false);
  review(0);
  assert.equal($("stageProgress").value,0);
  assert.deepEqual([0,1,2,3].map(value),[70,45,55,50]);
  choose(1); // poucos grupos
  assert.match($("stageStory").textContent,/procura ainda está tímida/);
  assert.deepEqual([0,1,2,3].map(value),[79,31,59,37]);
  $("reset").click();
  assert.equal($("resetConfirmation").hidden,false);
  assert.ok($("cancelReset").focused);
  $("cancelReset").click();
  assert.equal($("stageProgress").value,1);
  $("reset").click();$("confirmReset").click();
  assert.equal($("stageProgress").value,0);
  assert.equal($("stagePercent").textContent,"0%");
  assert.equal($("history").children.length,0);
  assert.deepEqual([0,1,2,3].map(value),[70,45,55,50]);
  assert.equal($("comparisonBody").children.length,0,"comparison must not retain hidden stale data");
  assert.equal($("compareResults").hidden,true);
  assert.equal($("saveBaseline").textContent,"Guardar caminho de referência");
  assert.equal($("saveBaseline").disabled,true);
  assert.equal($("resetConfirmation").hidden,true);
});
test("efeitos limitados a 0-100 com delta real distinto do previsto na saturação",async()=>{
  const {$,choose,value}=mount(await load());
  choose(1); // restrito: recursos 79
  choose(0); // reuso: recursos 89
  choose(1); // adia: recursos 97
  choose(2); // encerra sem retorno: recursos 109->100
  assert.equal(value(0),100);
  assert.equal($("stageProgress").value,4);
  const lastHistory=$("history").children[3],chips=lastHistory.children[3].children;
  assert.equal(chips[0].textContent,"Recursos +3","delta exibido corresponde ao aplicado após limite");
  assert.equal($("lastFeedbackAdjustment").hidden,false,"saturação deve produzir alerta explícito");
  assert.match($("lastFeedbackAdjustment").textContent,/Recursos: previsto \+12, aplicado \+3/);
  assert.match($("announcer").textContent,/Percurso concluído/);
  assert.match($("announcer").textContent,/limites de 0 a 100/);
  assert.equal(value(1),3);
  assert.equal(value(2),81);
  assert.equal(value(3),20);
  for(let i=0;i<4;i++)assert.ok(value(i)>=0&&value(i)<=100);
  assert.match($("outcomeText").textContent,/exigem maior atenção/);
});
test("todas as 81 sequências de decisão mantêm indicadores válidos",async()=>{
  const fixture=await load();
  let counted=0;
  for(let a=0;a<3;a++)for(let b=0;b<3;b++)for(let c=0;c<3;c++)for(let d=0;d<3;d++){
    const app=mount(fixture);
    for(const index of [a,b,c,d]){
      app.choose(index);
      for(let m=0;m<4;m++){
        assert.ok(Number.isInteger(app.value(m)));
        assert.ok(app.value(m)>=0&&app.value(m)<=100);
      }
    }
    assert.equal(app.$("stageProgress").value,4);
    assert.equal(app.$("history").children.length,4);
    assert.equal(app.$("completePanel").hidden,false);
    counted++;
  }
  assert.equal(counted,81);
});
test("catálogo acrescenta apenas o 13º exemplo e começa perguntando ao professor",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  const previous=[
    "flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado",
    "votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo",
    "quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa",
    "explorador-de-imagens-com-pontos-interativos","planejador-de-aprendizagem","linha-do-tempo-investigativa"
  ];
  assert.equal(catalog.version,1);
  assert.equal(catalog.category,"custom");
  assert.ok(catalog.examples.length>=13,"Exemplos posteriores não invalidam o cadastro do item 13.");
  assert.deepEqual(catalog.examples.slice(0,12).map(e=>e.id),previous);
  const current=catalog.examples[12];
  assert.equal(current.id,"simulador-de-cenarios-e-consequencias");
  assert.equal(current.path,"custom/simulador-de-cenarios-e-consequencias.html");
  assert.equal(catalog.examples[12].id,"simulador-de-cenarios-e-consequencias","O exemplo 13 deve manter sua posição.");
  assert.match(current.prompt,/Na sua PRIMEIRA resposta/);
  assert.match(current.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(current.prompt,/Aguarde a resposta/);
  assert.match(current.prompt,/trajetória|caminho de referência/i);
  for(const prior of catalog.examples.slice(0,12))assert.ok(prior.prompt.length>350);
});
