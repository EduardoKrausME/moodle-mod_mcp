/**
 * Jogo 18 - Descubra o erro. Execute com:
 * node --test docs/pt_br/tests/find-error.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-descubra-o-erro/revisao-codigo-web.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O exemplo deve conter um único script inline.');
const start=js.indexOf('const DATA='),pure=js.indexOf('function validate(data)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'As regras puras não foram encontradas.');
const rules=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,validate,initialState,foundCount,totalErrors,roundDone,validState,transition}=
 new Function(rules+'return {DATA,validate,initialState,foundCount,totalErrors,roundDone,validState,transition};')();
const act=(s,type,id)=>transition(s,{type,id},DATA);

test('HTML completo e independente, sem scripts de terceiros, menus ou rodapé',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|footer|nav|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/@media\(max-width:540px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.doesNotThrow(()=>new Script(js));
});

test('três rodadas com dois erros por trecho e explicações antes/depois',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'find_error');
 assert.equal(DATA.settings.mode,'line');
 assert.equal(DATA.rounds.length,3);
 assert.equal(totalErrors(DATA),6);
 assert.ok(DATA.rounds.every(r=>r.errors.length===2&&r.lines.length>=7));
 assert.ok(DATA.rounds.every(r=>r.errors.every(e=>e.explanation&&e.correctText)));
});

test('código HTML perigoso permanece literal e não é interpretado como DOM',()=>{
 assert.ok(DATA.rounds[0].lines.some(l=>l.text.includes('</div>')));
 assert.match(js,/const code=el\("code","",line\.text\)/);
 assert.match(js,/code=el\("code","",value\)/);
 assert.match(js,/textContent=value/);
 assert.doesNotMatch(js.slice(js.indexOf("const root=")),/\.innerHTML\s*=/);
 assert.doesNotMatch(js,/<img src=x onerror=/);
 assert.doesNotMatch(js,/<\/script>/);
 const adversarial=structuredClone(DATA);
 adversarial.rounds[0].lines[0].text='<img src=x onerror=alert(1)>';
 assert.equal(validate(adversarial),true,'o texto é aceito como dados, mas deve ser exibido só via textContent');
});

test('IDs repetidos ou texto vazio em linhas são recusados',()=>{
 const duplicate=structuredClone(DATA);
 duplicate.rounds[0].lines[1].id=duplicate.rounds[0].lines[0].id;
 assert.equal(validate(duplicate),false);
 const empty=structuredClone(DATA);
 empty.rounds[1].lines[0].text='   ';
 assert.equal(validate(empty),false);
 const duplicateRound=structuredClone(DATA);
 duplicateRound.rounds[2].id=duplicateRound.rounds[0].id;
 assert.equal(validate(duplicateRound),false);
});

test('erros não podem apontar para linha inexistente ou repetida',()=>{
 const missing=structuredClone(DATA);
 missing.rounds[0].errors[0].lineId='unknown';
 assert.equal(validate(missing),false);
 const repeated=structuredClone(DATA);
 repeated.rounds[0].errors[1].lineId=repeated.rounds[0].errors[0].lineId;
 assert.equal(validate(repeated),false);
 const repeatErrorId=structuredClone(DATA);
 repeatErrorId.rounds[1].errors[0].id=repeatErrorId.rounds[0].errors[0].id;
 assert.equal(validate(repeatErrorId),false);
});

test('não aceita correção idêntica, explicação vazia ou mais erros que linhas',()=>{
 const identical=structuredClone(DATA);
 identical.rounds[0].errors[0].correctText=identical.rounds[0].lines[2].text;
 assert.equal(validate(identical),false);
 const noExplanation=structuredClone(DATA);
 noExplanation.rounds[0].errors[0].explanation='';
 assert.equal(validate(noExplanation),false);
 const tooMany=structuredClone(DATA);
 tooMany.rounds[0].errors=tooMany.rounds[0].lines.map((l,i)=>({id:'err-'+i,lineId:l.id,correctText:'x',explanation:'explicação'}));
 assert.equal(validate(tooMany),false);
});

test('estado inicial não marca linhas e não concede acerto antecipado',()=>{
 const state=initialState(DATA);
 assert.equal(validState(state,DATA),true);
 assert.equal(state.index,0);
 assert.deepEqual(state.found,[[],[],[]]);
 assert.deepEqual(state.wrong,[[],[],[]]);
 assert.deepEqual(state.revealed,[false,false,false]);
 assert.equal(foundCount(state),0);
 assert.equal(state.completed,false);
});

test('não é permitido avançar antes de identificar todos os erros',()=>{
 const state=initialState(DATA);
 const next=act(state,'next');
 assert.equal(next.changed,false);
 assert.match(next.message,/Encontre todos os erros/);
 assert.equal(state.index,0);
});

test('marcar linha correta registra erro, não abre a correção nem perde achados',()=>{
 let state=initialState(DATA);
 const result=act(state,'select','h1');
 assert.equal(result.changed,true);
 assert.equal(result.kind,'warn');
 state=result.state;
 assert.deepEqual(state.wrong[0],['h1']);
 assert.deepEqual(state.found[0],[]);
 assert.equal(foundCount(state),0);
 assert.equal(validState(state,DATA),true);
});

test('cliques em linha desconhecida e seleção repetida não contam outra tentativa',()=>{
 let state=initialState(DATA);
 assert.equal(act(state,'select','inexistente').changed,false);
 state=act(state,'select','h1').state;
 assert.equal(act(state,'select','h1').changed,false);
 assert.deepEqual(state.wrong[0],['h1']);
 state=act(state,'select','h3').state;
 assert.equal(act(state,'select','h3').changed,false);
 assert.deepEqual(state.found[0],['h3']);
});

test('cada erro identificado tem explicação e correção e soma apenas um acerto',()=>{
 let state=initialState(DATA);
 const one=act(state,'select','h3');
 assert.equal(one.changed,true);
 assert.equal(one.kind,'good');
 assert.ok(one.message.includes('Correção:'));
 assert.ok(one.message.includes('</p>'));
 state=one.state;
 assert.equal(foundCount(state),1);
 assert.equal(roundDone(state,DATA,0),false);
 state=act(state,'select','h7').state;
 assert.equal(foundCount(state),2);
 assert.equal(roundDone(state,DATA,0),true);
 assert.equal(validState(state,DATA),true);
});

test('duas falhas no mesmo trecho precisam ser encontradas antes de avançar',()=>{
 let state=initialState(DATA);
 state=act(state,'select','h3').state;
 assert.equal(act(state,'next').changed,false);
 state=act(state,'select','h7').state;
 const result=act(state,'next');
 assert.equal(result.changed,true);
 assert.equal(result.state.index,1);
 assert.equal(validState(result.state,DATA),true);
});

test('revelar solução encerra trecho sem contar erros pendentes como acertos',()=>{
 let state=act(initialState(DATA),'select','h3').state;
 const reveal=act(state,'reveal');
 assert.equal(reveal.changed,true);
 assert.equal(reveal.kind,'warn');
 state=reveal.state;
 assert.equal(state.revealed[0],true);
 assert.equal(foundCount(state),1);
 assert.equal(roundDone(state,DATA,0),true);
 assert.equal(validState(state,DATA),true);
 assert.equal(act(state,'select','h7').changed,false);
 const next=act(state,'next');
 assert.equal(next.state.index,1);
 assert.equal(validState(next.state,DATA),true);
});

test('não revela solução após o aluno já encontrar todos os erros',()=>{
 let s=initialState(DATA);
 for(const e of DATA.rounds[0].errors)s=act(s,'select',e.lineId).state;
 assert.equal(act(s,'reveal').changed,false);
 assert.equal(s.revealed[0],false);
 const locked=structuredClone(DATA);
 locked.settings.allowReveal=false;
 assert.equal(validate(locked),true);
 assert.equal(transition(initialState(locked),{type:'reveal'},locked).changed,false);
});

test('percursos mistos combinam achados e soluções reveladas sem falsificar a nota',()=>{
 let s=initialState(DATA);
 s=act(s,'select','h3').state;
 s=act(s,'reveal').state;
 s=act(s,'next').state;
 for(const e of DATA.rounds[1].errors)s=act(s,'select',e.lineId).state;
 s=act(s,'next').state;
 s=act(s,'select','j5').state;
 s=act(s,'select','j1').state;
 s=act(s,'select','j6').state;
 s=act(s,'next').state;
 assert.equal(s.index,3);
 assert.equal(s.completed,true);
 assert.equal(foundCount(s),5);
 assert.equal(s.wrong[2].length,1);
 assert.equal(s.revealed[0],true);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'next').changed,false);
 assert.deepEqual(act(s,'restart').state,initialState(DATA));
});

test('fluxo com seis achados sem revelar alcança conclusão correta',()=>{
 let s=initialState(DATA);
 for(const round of DATA.rounds){
  for(const e of round.errors)s=act(s,'select',e.lineId).state;
  assert.equal(validState(s,DATA),true);
  s=act(s,'next').state;
 }
 assert.equal(s.completed,true);
 assert.equal(foundCount(s),6);
 assert.equal(validState(s,DATA),true);
});

test('restauração bloqueia linha errada no conjunto correto e IDs desconhecidos',()=>{
 const bad=initialState(DATA);
 bad.found[0]=['h1'];
 assert.equal(validState(bad,DATA),false);
 const unknown=initialState(DATA);
 unknown.wrong[0]=['not-here'];
 assert.equal(validState(unknown,DATA),false);
 const duplicate=initialState(DATA);
 duplicate.found[0]=['h3','h3'];
 assert.equal(validState(duplicate,DATA),false);
 const overlap=initialState(DATA);
 overlap.wrong[0]=['h3'];
 assert.equal(validState(overlap,DATA),false);
});

test('não permite avançar artificialmente, forjar conclusão ou editar rodadas futuras',()=>{
 const forward=initialState(DATA);
 forward.index=2;
 assert.equal(validState(forward,DATA),false);
 const future=initialState(DATA);
 future.found[2]=['j5'];
 assert.equal(validState(future,DATA),false);
 const finished=initialState(DATA);
 finished.completed=true;
 assert.equal(validState(finished,DATA),false);
 const badReveal=initialState(DATA);
 badReveal.revealed[0]=true;
 badReveal.found[0]=DATA.rounds[0].errors.map(e=>e.lineId);
 assert.equal(validState(badReveal,DATA),false);
});

test('trechos com quebras de linha são preservados como código e rolagem horizontal',()=>{
 assert.match(html,/white-space:pre/);
 assert.match(html,/overflow-x:auto/);
 assert.match(js,/const code=el\("code","",line\.text\)/);
 assert.ok(DATA.rounds[0].lines.some(line=>line.text.startsWith('  ')));
 assert.ok(DATA.rounds[2].lines.some(line=>line.text.includes('  ')));
});

test('linhas são botões navegáveis por teclado, Tab, setas, Enter e Espaço',()=>{
 assert.match(js,/button\.type="button";button\.dataset\.line=line\.id/);
 assert.match(js,/event\.key==="ArrowDown"\|\|event\.key==="ArrowUp"/);
 assert.match(js,/\.focus\(\)/);
 assert.match(html,/Cada linha é um botão/);
 assert.match(html,/role="status" aria-live="polite"/);
 assert.match(html,/id="challenge"[^>]+tabindex="-1"/);
 assert.match(html,/id="feedback"[^>]+tabindex="-1"/);
});

test('persistência de progresso é opcional, versionada e não aciona nota acadêmica',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('manifesto local e global usam exatamente o mesmo prompt e aguardam professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-descubra-o-erro/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='descubra-o-erro-revisao-codigo-web';
 const a=local.examples.find(e=>e.id===id),b=general.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/18-descubra-o-erro\.md/);
});
