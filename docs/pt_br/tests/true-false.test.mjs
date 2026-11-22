/**
 * Regressões do jogo 12 - Verdadeiro ou falso rápido.
 * Execução: node --test docs/pt_br/tests/true-false.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-verdadeiro-ou-falso/mitos-e-fatos-da-astronomia.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O HTML deve conter script incorporado');
const start=js.indexOf('const DATA='),pure=js.indexOf('function validate(data)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'Regras de validação ausentes');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,validate,hash,orderIds,initialState,score,validState,transition}=
 new Function(source+'return {DATA,validate,hash,orderIds,initialState,score,validState,transition};')();
const act=(state,type,value)=>transition(state,{type,value},DATA);

test('HTML independente, sem menus, cabeçalho institucional, rodapé ou imagens',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]*<\/style>/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/id="feedback"[^>]+tabindex="-1"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/@media\(max-width:570px\)/);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotThrow(()=>new Script(js));
});

test('dados válidos têm 12 afirmações equilibradas, IDs únicos e explicações',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'true_false');
 assert.equal(DATA.statements.length,12);
 assert.equal(DATA.statements.filter(x=>x.correct).length,6);
 assert.equal(DATA.statements.filter(x=>!x.correct).length,6);
 assert.equal(new Set(DATA.statements.map(x=>x.id)).size,12);
 assert.ok(DATA.statements.every(x=>x.text&&x.explanation));
});

test('booleanos como string são rejeitados e afirmações devem ter explicação',()=>{
 const wrong=structuredClone(DATA);
 wrong.statements[0].correct='false';
 assert.equal(validate(wrong),false);
 wrong.statements[0].correct=false;
 wrong.statements[0].explanation='';
 assert.equal(validate(wrong),false);
 const repeated=structuredClone(DATA);
 repeated.statements[1].id=repeated.statements[0].id;
 assert.equal(validate(repeated),false);
});

test('texto longo é aceito sem quebrar limites e validação de conteúdo',()=>{
 const copy=structuredClone(DATA);
 copy.statements[0].text='Observando o céu a partir de cidades, sabemos que '.repeat(7)+'a interpretação depende dos dados disponíveis e da posição do observador.';
 assert.ok(copy.statements[0].text.length>200);
 assert.equal(validate(copy),true);
 assert.match(html,/overflow-wrap:anywhere/);
});

test('ordem reproduzível e suporte à opção de não embaralhar',()=>{
 const a=orderIds(DATA),b=orderIds(DATA);
 assert.deepEqual(a,b);
 assert.equal(a.length,12);
 assert.equal(new Set(a).size,12);
 const natural=structuredClone(DATA);
 natural.settings.shuffle=false;
 assert.deepEqual(orderIds(natural),natural.statements.map(x=>x.id));
});

test('resposta correta e incorreta mostram explicação e pontuação formativa',()=>{
 let state=initialState(DATA);
 const first=DATA.statements.find(x=>x.id===state.order[0]);
 const outcome=act(state,'answer',first.correct);
 state=outcome.state;
 assert.equal(outcome.changed,true);
 assert.equal(outcome.kind,'right');
 assert.ok(outcome.message.includes(first.explanation));
 assert.equal(score(state,DATA),1);
 assert.equal(validState(state,DATA),true);
 assert.equal(act(state,'answer',!first.correct).changed,false);
 const next=act(state,'next').state;
 const second=DATA.statements.find(x=>x.id===next.order[next.index]);
 const incorrect=act(next,'answer',!second.correct);
 assert.equal(incorrect.kind,'wrong');
 assert.equal(score(incorrect.state,DATA),1);
 assert.equal(validState(incorrect.state,DATA),true);
});

test('clique duplo não conta tentativa extra nem permite alterar resposta',()=>{
 let state=initialState(DATA);
 assert.equal(act(state,'next').changed,false);
 const first=act(state,'answer',true);
 state=first.state;
 assert.equal(state.attempts,1);
 const duplicate=act(state,'answer',false);
 assert.equal(duplicate.changed,false);
 assert.equal(duplicate.state.attempts,1);
 assert.equal(act(initialState(DATA),'answer','false').changed,false);
});

test('é possível voltar à anterior e rever resposta sem corrigir ou contar outra tentativa',()=>{
 let state=initialState(DATA);
 state=act(state,'answer',true).state;
 state=act(state,'next').state;
 state=act(state,'answer',false).state;
 const answers={...state.responses};
 state=act(state,'previous').state;
 assert.equal(state.index,0);
 assert.equal(state.attempts,2);
 assert.deepEqual(state.responses,answers);
 assert.equal(act(state,'answer',false).changed,false);
 state=act(state,'next').state;
 assert.equal(state.index,1);
 assert.equal(validState(state,DATA),true);
});

test('todas as respostas levam ao resumo e podem ser revistas posteriormente',()=>{
 let state=initialState(DATA);
 for(let i=0;i<DATA.statements.length;i++){
  const current=DATA.statements.find(x=>x.id===state.order[state.index]);
  state=act(state,'answer',current.correct).state;
  assert.equal(validState(state,DATA),true);
  state=act(state,'next').state;
 }
 assert.equal(state.completed,true);
 assert.equal(state.index,12);
 assert.equal(score(state,DATA),12);
 assert.equal(validState(state,DATA),true);
 state=act(state,'previous').state;
 assert.equal(state.index,11);
 assert.equal(state.completed,false);
 assert.equal(state.attempts,12);
 assert.equal(act(state,'next').state.completed,true);
 assert.deepEqual(act(state,'restart').state,initialState(DATA));
});

test('estado adulterado com resposta não booleana ou ordem desconhecida é rejeitado',()=>{
 const original=initialState(DATA);
 const altered=structuredClone(original);
 altered.order[0]='intruso';
 assert.equal(validState(altered,DATA),false);
 const bad=structuredClone(original);
 bad.responses[bad.order[0]]='true';bad.attempts=1;
 assert.equal(validState(bad,DATA),false);
 const skipped=structuredClone(original);
 skipped.responses[skipped.order[1]]=true;skipped.attempts=1;
 assert.equal(validState(skipped,DATA),false);
 const forced=structuredClone(original);
 forced.completed=true;
 assert.equal(validState(forced,DATA),false);
});

test('offline, persistência opcional e foco de teclado seguem APIs existentes',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/const progress=sdk&&typeof sdk\.load/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('manifestos sincronizados e prompt obrigatoriamente pergunta ao professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-verdadeiro-ou-falso/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='verdadeiro-ou-falso-astronomia';
 const a=local.examples.find(x=>x.id===id),b=general.examples.find(x=>x.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/12-verdadeiro-ou-falso\.md/);
});
