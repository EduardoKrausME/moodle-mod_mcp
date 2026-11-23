/**
 * Testes independentes do planejador de aprendizagem.
 * Executar: node --test docs/pt_br/tests/learning-planner.test.mjs
 */
import test from "node:test";
import assert from "node:assert/strict";
import {readFile} from "node:fs/promises";
import {Script,runInNewContext} from "node:vm";
const root=new URL("../",import.meta.url);
const read=path=>readFile(new URL(path,root),"utf8");
const load=async()=>{
  const html=await read("custom/planejador-de-aprendizagem.html");
  const script=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
  assert.ok(script);
  return {html,script};
};
test("HTML independente, responsivo, acessível e sem dependências externas",async()=>{
  const {html,script}=await load();
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.equal((html.match(/<script>/g)||[]).length,1);
  assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|@import\b|<footer\b/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const re of [
    /<progress id="progress"/,/role="status"/,/aria-live="polite"/,
    /prefers-reduced-motion:reduce/,/@media\(max-width:510px\)/,
    /type="checkbox"/,/id="minutesDay"/,/id="days"/,
    /id="taskEditor"/,/id="reviewList"/,/id="overflow"/,
    /id="goalConfirmation"/,/id="resetConfirmation"/,
    /id="undo"/,/id="newTask"/,/id="confirmReset"/,
    /setAttribute\("aria-label"/,/task\.done/,/value=completed\.length/,
    /<svg[^>]+viewBox="0 0 164 142"/
  ]) assert.match(html,re);
  assert.doesNotMatch(script,/localStorage|sessionStorage|fetch\(|XMLHttpRequest|document\.cookie|innerHTML\s*=/);
  assert.doesNotMatch(script,/\.children\.forEach\(/,"HTMLCollection has no .forEach in browsers");
});
test("cinco modelos originais possuem atividades editáveis e durações válidas",async()=>{
  const {script}=await load();
  const source=script.match(/const goals=(\{[\s\S]*?\});\s*const initial=/);
  assert.ok(source);
  const goals=runInNewContext("("+source[1]+")");
  assert.deepEqual(Object.keys(goals),["concept","skill","exam","project","custom"]);
  for(const [id,model] of Object.entries(goals)){
    assert.ok(model.label.length>5,id);
    assert.ok(model.tasks.length>=5,id);
    for(const task of model.tasks){
      assert.equal(task.length,3);
      assert.ok(task[0].length>8&&task[1].length>20,id);
      assert.ok(task[2]>=5&&task[2]<=240&&task[2]%5===0,id);
    }
  }
  assert.equal(goals.concept.tasks.reduce((n,t)=>n+t[2],0),150);
});
test("edição real, redistribuição, divisão de etapas, excesso de tempo, progresso, revisão e desfazer",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){
      this.tag=tag;this.id=id;this.children=[];this.parentNode=null;this.handlers={};
      this.attributes={};this.dataset={};this.style={};this.value="";this.textContent="";
      this.disabled=false;this.hidden=false;this.checked=false;this.className="";
    }
    append(...items){for(const item of items){item.parentNode=this;this.children.push(item);}}
    appendChild(item){item.parentNode=this;this.children.push(item);}
    replaceChildren(...items){this.children=[];this.append(...items);}
    setAttribute(name,value){this.attributes[name]=String(value);}
    addEventListener(event,fn){this.handlers[event]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    change(){this.handlers.change?.();}
    submit(){let prevented=false;this.handlers.submit?.({preventDefault(){prevented=true;}});return prevented;}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(x=>x[1]);
  const elements=new Map(ids.map(id=>[id,new Element("div",id)]));
  for(const m of html.matchAll(/<([a-z]+)\b[^>]*\bid="([^"]+)"[^>]*>/gi)){
    const node=elements.get(m[2]);node.tag=m[1].toLowerCase();node.hidden=/\shidden(?:\s|>)/.test(m[0]);
  }
  const $=id=>{const el=elements.get(id);assert.ok(el,"element "+id);return el;};
  const document={getElementById:$,createElement:tag=>new Element(tag)};
  runInNewContext(script,{document});
  const tasks=()=>$("taskList").children;
  const taskCheck=i=>tasks()[i].children[0].children[0];
  const action=(i,k)=>tasks()[i].children[2].children[k];
  const totalScheduled=()=>$("schedule").children.reduce((sum,day)=>{
    const list=day.children[2];
    if(list.tag!=="ul")return sum;
    return sum+list.children.reduce((acc,row)=>acc+Number.parseInt(row.children[1].textContent,10),0);
  },0);
  const totalOverflow=()=>$("overflow").hidden?0:$("overflowList").children.reduce((sum,item)=>{
    const match=item.textContent.match(/(\d+) min restantes/);
    return sum+(match?Number(match[1]):0);
  },0);
  const assertAllocation=pending=>assert.equal(totalScheduled()+totalOverflow(),pending,"all pending minutes allocated or explicitly overdue");
  assert.equal(tasks().length,5);
  assert.equal($("capacity").textContent,"200 min");
  assert.equal($("pendingMinutes").textContent,"150 min");
  assert.equal($("balance").textContent,"50 min");
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal($("schedule").children.length,5);
  assert.equal($("overflow").hidden,true);
  assertAllocation(150);
  // Marcar como concluída não elimina a etapa e libera capacidade.
  taskCheck(0).checked=true;taskCheck(0).change();
  assert.equal($("progress").value,1);
  assert.equal($("progressPercent").textContent,"20%");
  assert.equal($("completedMinutes").textContent,"20 min");
  assert.equal($("pendingMinutes").textContent,"130 min");
  assert.equal($("reviewList").children.length,1);
  assertAllocation(130);
  // Mudança de capacidade preserva checklists; excesso fica visível.
  $("minutesDay").value="15";$("minutesDay").change();
  assert.equal($("capacity").textContent,"75 min");
  assert.equal($("balanceTitle").textContent,"Tempo além da capacidade");
  assert.equal($("balance").textContent,"55 min");
  assert.equal($("overflow").hidden,false);
  assert.equal($("progress").value,1);
  assertAllocation(130);
  $("undo").click();
  assert.equal($("capacity").textContent,"200 min");
  assert.equal($("progress").value,1);
  assertAllocation(130);
  $("days").value="3";$("days").change();
  assert.equal($("capacity").textContent,"120 min");
  assert.equal($("balance").textContent,"10 min");
  assertAllocation(130);
  // Valor inválido não gera horas ou contagens inconsistentes.
  $("minutesDay").value="0";$("minutesDay").change();
  assert.equal($("minutesDay").value,"40");
  assert.equal($("capacity").textContent,"120 min");
  // Criar etapa com duração maior que uma sessão força divisão.
  $("newTask").click();
  assert.equal($("taskEditor").hidden,false);
  $("taskTitle").value="Resolver desafios de integração";
  $("taskDetails").value="Reunir conceitos e aplicar em situações reais.";
  $("taskMinutes").value="95";
  assert.equal($("taskForm").submit(),true);
  assert.equal(tasks().length,6);
  assert.equal($("pendingMinutes").textContent,"225 min");
  assert.equal($("overflow").hidden,false);
  assertAllocation(225);
  assert.ok(action(5,0).focused,"focus returns to the new task after saving");
  // Editar duração e descrição mantém marcações concluídas.
  action(5,0).click();
  $("taskTitle").value="Resolver desafios interdisciplinares";
  $("taskDetails").value="Aplicar o tema em cenários explicados.";
  $("taskMinutes").value="90";
  $("taskForm").submit();
  assert.equal(tasks()[5].children[0].children[1].children[0].textContent,"Resolver desafios interdisciplinares");
  assert.equal($("pendingMinutes").textContent,"220 min");
  assertAllocation(220);
  $("undo").click();
  assert.equal(tasks()[5].children[0].children[1].children[0].textContent,"Resolver desafios de integração");
  assertAllocation(225);
  // Reordenação muda a sequência sem alterar a soma de minutos.
  action(5,1).click();
  assert.equal(tasks()[4].children[0].children[1].children[0].textContent,"Resolver desafios de integração");
  assert.ok(action(4,0).focused);
  assertAllocation(225);
  $("undo").click();
  assert.equal(tasks()[5].children[0].children[1].children[0].textContent,"Resolver desafios de integração");
  action(5,3).click();
  assert.equal(tasks().length,5);
  assertAllocation(130);
  $("undo").click();
  assert.equal(tasks().length,6);
  assertAllocation(225);
  // Reabrir uma tarefa concluída devolve seu tempo ao cronograma.
  $("reviewList").children[0].children[2].click();
  assert.equal($("progress").value,0);
  assert.equal($("completedMinutes").textContent,"0 min");
  assert.equal($("pendingMinutes").textContent,"245 min");
  assertAllocation(245);
  $("undo").click();
  assert.equal($("progress").value,1);
  assert.equal($("reviewList").children.length,1);
  assertAllocation(225);
  // Trocar de objetivo exige confirmação e não modifica minutos disponíveis.
  $("goalSelect").value="skill";$("goalSelect").change();
  $("loadGoal").click();
  assert.equal($("goalConfirmation").hidden,false);
  $("cancelGoal").click();
  assert.equal($("goalConfirmation").hidden,true);
  assert.equal(tasks().length,6);
  $("goalSelect").value="skill";$("goalSelect").change();
  $("loadGoal").click();$("confirmGoal").click();
  assert.equal(tasks().length,5);
  assert.equal($("activeGoal").textContent,"Plano atual: Praticar uma habilidade");
  assert.equal($("capacity").textContent,"120 min");
  assert.equal($("progress").value,0);
  assertAllocation(165);
  $("undo").click();
  assert.equal(tasks().length,6);
  assert.equal($("activeGoal").textContent,"Plano atual: Compreender um conceito");
  assert.equal($("progress").value,1);
  // Reinício com cancelamento e desfazer.
  $("reset").click();assert.equal($("resetConfirmation").hidden,false);
  $("cancelReset").click();assert.equal($("resetConfirmation").hidden,true);
  assert.equal(tasks().length,6);
  $("reset").click();$("confirmReset").click();
  assert.equal(tasks().length,5);
  assert.equal($("capacity").textContent,"200 min");
  assert.equal($("pendingMinutes").textContent,"150 min");
  assert.equal($("progress").value,0);
  assert.equal($("resetConfirmation").hidden,true);
  $("undo").click();
  assert.equal(tasks().length,6);
  assert.equal($("capacity").textContent,"120 min");
  assert.equal($("progress").value,1);
});
test("sem tarefas, só tarefas concluídas e sessões vazias não perdem minutos nem produzem porcentagens inválidas",async()=>{
  const {html,script}=await load();
  class Element {
    constructor(tag,id=""){this.tag=tag;this.id=id;this.children=[];this.handlers={};this.dataset={};this.style={};this.attributes={};this.disabled=false;this.hidden=false;this.value="";this.textContent="";this.checked=false;}
    append(...items){this.children.push(...items);}
    appendChild(x){this.children.push(x);}
    replaceChildren(...items){this.children=items;}
    setAttribute(k,v){this.attributes[k]=String(v);}
    addEventListener(k,fn){this.handlers[k]=fn;}
    focus(){this.focused=true;}
    click(){if(!this.disabled)this.handlers.click?.();}
    change(){this.handlers.change?.();}
  }
  const ids=[...html.matchAll(/\bid="([^"]+)"/g)].map(m=>m[1]);
  const els=new Map(ids.map(id=>[id,new Element("div",id)]));
  const document={getElementById(id){const x=els.get(id);assert.ok(x,id);return x;},createElement(t){return new Element(t);}};
  runInNewContext(script,{document});
  const $=id=>els.get(id),tasks=()=>$("taskList").children;
  for(let i=0;i<5;i++){
    const checkbox=tasks()[i].children[0].children[0];
    checkbox.checked=true;checkbox.change();
  }
  assert.equal($("progress").value,5);
  assert.equal($("progressPercent").textContent,"100%");
  assert.equal($("pendingMinutes").textContent,"0 min");
  assert.equal($("schedule").children.length,5);
  assert.match($("scheduleNote").textContent,/Não há trabalho pendente/);
  assert.equal($("reviewList").children.length,5);
  for(let i=4;i>=0;i--)tasks()[i].children[2].children[3].click();
  assert.equal(tasks().length,0);
  assert.equal($("progress").value,0);
  assert.equal($("progressPercent").textContent,"0%");
  assert.equal($("emptyTasks").hidden,false);
  assert.equal($("pendingMinutes").textContent,"0 min");
  assert.equal($("overflow").hidden,true);
  assert.equal($("schedule").children.length,5);
  assert.match($("scheduleNote").textContent,/Não há trabalho pendente/);
  $("undo").click();
  assert.equal(tasks().length,1);
  assert.equal($("progress").value,1);
  assert.equal($("progressPercent").textContent,"100%");
});
test("catálogo acrescenta somente o 11º exemplo e preserva todos os prompts anteriores",async()=>{
  const catalog=JSON.parse(await read("custom/examples.json"));
  assert.equal(catalog.version,1);
  assert.equal(catalog.category,"custom");
  assert.equal(catalog.examples.length,11);
  const existing=["flashcards-de-revisao","mapa-mental-exploravel","roteiro-de-estudo-personalizado","votacao-rapida","comparador-de-tecnicas","mapa-conceitual-interativo","quadro-de-organizacao-de-ideias","construtor-de-fluxogramas","matriz-de-decisao-interativa","explorador-de-imagens-com-pontos-interativos"];
  assert.deepEqual(catalog.examples.slice(0,10).map(e=>e.id),existing);
  const item=catalog.examples[10];
  assert.equal(item.id,"planejador-de-aprendizagem");
  assert.equal(item.path,"custom/planejador-de-aprendizagem.html");
  assert.match(item.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(item.prompt,/Aguarde a resposta/);
  assert.match(item.prompt,/distribua|distribuição|redistribua/i);
  assert.match(item.prompt,/confirmar|confirmação/i);
  for(const prior of catalog.examples.slice(0,10))assert.ok(prior.prompt.length>350);
});
