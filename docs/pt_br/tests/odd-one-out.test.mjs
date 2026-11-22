/**
 * Regressões do jogo 15 - Qual não pertence?
 * Execute: node --test docs/pt_br/tests/odd-one-out.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-qual-nao-pertence/ciencias-e-tecnologia.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'É necessário um script incorporado');
const start=js.indexOf('const DATA='),pure=js.indexOf('function normalized(value)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'Regras de validação e estado ausentes');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,normalized,validate,hashSeed,shuffled,optionOrder,initialState,score,validState,transition}=
 new Function(source+'return {DATA,normalized,validate,hashSeed,shuffled,optionOrder,initialState,score,validState,transition};')();
const act=(state,type,id)=>transition(state,{type,id},DATA);

test('HTML completo, responsivo, acessível e sem dependências nem imagens',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/id="options"[^>]+role="group"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/@media\(max-width:535px\)/);
 assert.doesNotThrow(()=>new Script(js));
});

test('oito critérios explícitos com uma única exceção por rodada',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'odd_one_out');
 assert.equal(DATA.rounds.length,8);
 assert.ok(DATA.rounds.every(r=>r.items.length===4));
 assert.ok(DATA.rounds.every(r=>r.items.filter(x=>!x.matches).length===1));
 assert.ok(DATA.rounds.every(r=>r.items.find(x=>!x.matches).id===r.oddId));
 assert.ok(DATA.rounds.every(r=>r.criterion&&r.prompt&&r.explanation));
 assert.ok(DATA.rounds.every(r=>r.items.every(item=>item.why)));
});

test('oddId ausente, incorreto ou não correspondente a item falso é rejeitado',()=>{
 const invalid=structuredClone(DATA);
 invalid.rounds[0].oddId='inexistente';
 assert.equal(validate(invalid),false);
 const incorrect=structuredClone(DATA);
 incorrect.rounds[0].oddId=incorrect.rounds[0].items[0].id;
 assert.equal(validate(incorrect),false);
 const absent=structuredClone(DATA);
 absent.rounds[0].oddId='';
 assert.equal(validate(absent),false);
});

test('zero ou duas exceções tornam a classificação inválida',()=>{
 const noOdd=structuredClone(DATA);
 noOdd.rounds[0].items.forEach(x=>x.matches=true);
 assert.equal(validate(noOdd),false);
 const twoOdd=structuredClone(DATA);
 twoOdd.rounds[0].items[1].matches=false;
 assert.equal(validate(twoOdd),false);
 const nonboolean=structuredClone(DATA);
 nonboolean.rounds[0].items[1].matches='true';
 assert.equal(validate(nonboolean),false);
});

test('critério ambíguo em branco, explicação ausente e itens iguais são rejeitados',()=>{
 const ambiguous=structuredClone(DATA);
 ambiguous.rounds[0].criterion='  ';
 assert.equal(validate(ambiguous),false);
 const noExplanation=structuredClone(DATA);
 noExplanation.rounds[0].explanation='';
 assert.equal(validate(noExplanation),false);
 const duplicated=structuredClone(DATA);
 duplicated.rounds[0].items[1].label=duplicated.rounds[0].items[0].label;
 assert.equal(validate(duplicated),false);
 const accented=structuredClone(DATA);
 accented.rounds[0].items[1].label='MERCÚRIO';
 assert.equal(validate(accented),false);
 const duplicateIds=structuredClone(DATA);
 duplicateIds.rounds[0].items[1].id=duplicateIds.rounds[0].items[0].id;
 assert.equal(validate(duplicateIds),false);
});

test('cada rodada tem um ID exclusivo e entre três e seis alternativas',()=>{
 const repeated=structuredClone(DATA);
 repeated.rounds[1].id=repeated.rounds[0].id;
 assert.equal(validate(repeated),false);
 const short=structuredClone(DATA);
 short.rounds[0].items=short.rounds[0].items.slice(0,2);
 assert.equal(validate(short),false);
 const noReason=structuredClone(DATA);
 noReason.rounds[0].items[0].why='';
 assert.equal(validate(noReason),false);
});

test('embaralhamento determinístico preserva IDs e não revela o gabarito',()=>{
 const first=initialState(DATA),second=initialState(DATA);
 assert.deepEqual(first,second);
 assert.equal(first.order.length,8);
 for(let i=0;i<DATA.rounds.length;i++){
  assert.equal(first.order[i].length,4);
  assert.equal(new Set(first.order[i]).size,4);
  assert.deepEqual(new Set(first.order[i]),new Set(DATA.rounds[i].items.map(item=>item.id)));
  assert.deepEqual(first.order[i],optionOrder(DATA,DATA.rounds[i]));
 }
 assert.equal(validState(first,DATA),true);
 assert.deepEqual(first.choices,DATA.rounds.map(()=>null));
});

test('sem embaralhamento, a ordem original permanece intacta',()=>{
 const config=structuredClone(DATA);
 config.settings.shuffle=false;
 assert.equal(validate(config),true);
 for(const round of config.rounds){
  assert.deepEqual(optionOrder(config,round),round.items.map(item=>item.id));
 }
 assert.equal(validState(initialState(config),config),true);
});

test('não avança antes da resposta e IDs desconhecidos não são aceitos',()=>{
 const start=initialState(DATA);
 assert.equal(act(start,'next').changed,false);
 assert.equal(act(start,'select','inexistente').changed,false);
 const nextRoundId=DATA.rounds[1].items[0].id;
 assert.equal(act(start,'select',nextRoundId).changed,false);
 assert.equal(start.choices[0],null);
});

test('resposta correta é registrada uma vez, permite avanço e soma um acerto',()=>{
 let state=initialState(DATA);
 const picked=act(state,'select',DATA.rounds[0].oddId);
 assert.equal(picked.changed,true);
 assert.equal(picked.kind,'good');
 assert.ok(picked.message.includes(DATA.rounds[0].explanation));
 state=picked.state;
 assert.equal(score(state,DATA),1);
 assert.equal(validState(state,DATA),true);
 assert.equal(act(state,'select',DATA.rounds[0].oddId).changed,false);
 assert.equal(act(state,'select',DATA.rounds[0].items[0].id).changed,false);
 state=act(state,'next').state;
 assert.equal(state.index,1);
 assert.equal(validState(state,DATA),true);
});

test('erro explica por que o selecionado pertence ao grupo e revela a exceção',()=>{
 const start=initialState(DATA),round=DATA.rounds[0];
 const chosen=round.items.find(item=>item.matches);
 const result=act(start,'select',chosen.id);
 assert.equal(result.changed,true);
 assert.equal(result.kind,'warn');
 assert.equal(result.state.choices[0],chosen.id);
 assert.equal(score(result.state,DATA),0);
 assert.ok(result.message.includes(chosen.why));
 assert.ok(result.message.includes(round.explanation));
 assert.ok(result.message.includes(round.items.find(item=>item.id===round.oddId).label));
 assert.equal(validState(result.state,DATA),true);
});

test('oito rodadas com escolhas mistas concluem e mantêm contagem correta',()=>{
 let state=initialState(DATA),correctCount=0;
 for(let i=0;i<DATA.rounds.length;i++){
  const round=DATA.rounds[i],id=i%2===0?round.oddId:round.items.find(item=>item.matches).id;
  if(i%2===0)correctCount++;
  state=act(state,'select',id).state;
  assert.equal(validState(state,DATA),true);
  state=act(state,'next').state;
  assert.equal(validState(state,DATA),true);
 }
 assert.equal(state.completed,true);
 assert.equal(state.index,8);
 assert.equal(score(state,DATA),correctCount);
 assert.equal(correctCount,4);
 assert.equal(act(state,'next').changed,false);
 assert.deepEqual(act(state,'restart').state,initialState(DATA));
});

test('estado adulterado com alternativa externa ou ordem trocada não é restaurável',()=>{
 const external=initialState(DATA);
 external.choices[0]='nao-existe';
 assert.equal(validState(external,DATA),false);
 const shifted=initialState(DATA);
 [shifted.order[0][0],shifted.order[0][1]]=[shifted.order[0][1],shifted.order[0][0]];
 assert.equal(validState(shifted,DATA),false);
 const skipped=initialState(DATA);
 skipped.index=2;
 assert.equal(validState(skipped,DATA),false);
 const future=initialState(DATA);
 future.choices[4]=DATA.rounds[4].oddId;
 assert.equal(validState(future,DATA),false);
 const forged=initialState(DATA);
 forged.completed=true;
 assert.equal(validState(forged,DATA),false);
});

test('UI gera opções com DOM seguro e trata SDK como opcional',()=>{
 assert.match(js,/document\.createElement\(tag\)/);
 assert.match(js,/textContent=value/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
 assert.match(html,/role="status"/);
});

test('os dois manifestos registram mesmo exemplo e prompt que aguarda professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-qual-nao-pertence/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='qual-nao-pertence-ciencias-e-tecnologia';
 const a=local.examples.find(e=>e.id===id),b=general.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/15-qual-nao-pertence\.md/);
});
