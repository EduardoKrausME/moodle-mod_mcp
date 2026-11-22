/**
 * Regressões do jogo 11 - Bingo educativo.
 * Execute: node --test docs/pt_br/tests/bingo.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-bingo-educativo/cidadania-digital.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'Bingo precisa conter JavaScript próprio');
const configStart=js.indexOf('const CONFIG='),helpersStart=js.indexOf('function validate(data)'),helpersEnd=js.indexOf('// End pure game rules.');
assert.ok(configStart>=0&&helpersStart>configStart&&helpersEnd>helpersStart);
const code=js.slice(configStart,js.indexOf('const root=',configStart))+js.slice(helpersStart,helpersEnd);
const {CONFIG,validate,hashSeed,shuffled,makeCard,drawOrder,winPatterns,completedLines,initialState,pendingClue,validState,transition}=
 new Function(code+'return {CONFIG,validate,hashSeed,shuffled,makeCard,drawOrder,winPatterns,completedLines,initialState,pendingClue,validState,transition};')();

const act=(state,type,id)=>transition(state,{type,id},CONFIG);

test('HTML autossuficiente, acessível e sem cabeçalho institucional, menu, imagens ou rodapé',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.match(html,/id="board"[^>]+role="group"/);
 assert.match(html,/id="clue-text"[^>]+aria-live="polite"/);
 assert.match(html,/id="history"/);
 assert.match(html,/id="restart"/);
 assert.match(html,/id="new-card"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotThrow(()=>new Script(js));
});

test('pool suficiente, IDs únicos e configuração coerente são obrigatórios',()=>{
 assert.equal(validate(CONFIG),true);
 assert.equal(CONFIG.type,'bingo');
 assert.equal(CONFIG.entries.length,22);
 assert.equal(CONFIG.settings.size,4);
 assert.equal(CONFIG.settings.win,'row_column_diagonal');
 assert.equal(validate({...CONFIG,entries:CONFIG.entries.slice(0,15)}),false,'pool menor que a cartela');
 const repeated=structuredClone(CONFIG);
 repeated.entries[1].id=repeated.entries[0].id;
 assert.equal(validate(repeated),false);
 const invalidSize=structuredClone(CONFIG);
 invalidSize.settings.size=0;
 assert.equal(validate(invalidSize),false);
 const invalidCenter=structuredClone(CONFIG);
 invalidCenter.settings.freeCenter=true;
 assert.equal(validate(invalidCenter),false,'casa livre em grade par não é permitida');
});

test('semente produz cartela e ordem reprodutíveis sem repetição',()=>{
 const a=initialState(CONFIG,0),b=initialState(CONFIG,0);
 assert.deepEqual(a,b);
 assert.equal(a.card.length,16);
 assert.equal(new Set(a.card).size,16);
 assert.equal(a.order.length,22);
 assert.equal(new Set(a.order).size,22);
 assert.deepEqual(makeCard(CONFIG,0),a.card);
 assert.deepEqual(drawOrder(CONFIG,0),a.order);
 assert.notDeepEqual(makeCard(CONFIG,0),makeCard(CONFIG,1));
 assert.equal(validState(a,CONFIG),true);
});

test('sorteio único permite somente uma pista pendente de cada vez',()=>{
 const start=initialState(CONFIG);
 assert.equal(act(start,'mark',start.card[0]).changed,false,'não é possível marcar antes de sortear');
 const first=act(start,'draw');
 assert.equal(first.changed,true);
 assert.equal(first.state.called.length,1);
 assert.equal(pendingClue(first.state),first.state.order[0]);
 assert.equal(act(first.state,'draw').changed,false);
 assert.equal(validState(first.state,CONFIG),true);
});

test('marcações por ID rejeitam respostas erradas e registram apenas chamada atual',()=>{
 const start=act(initialState(CONFIG),'draw').state;
 const current=pendingClue(start);
 const wrong=start.card.find(id=>id!==current);
 const bad=act(start,'mark',wrong);
 assert.equal(bad.changed,true);
 assert.equal(bad.state.errors,1);
 assert.equal(bad.state.marked.length,0);
 assert.equal(bad.state.resolved.length,0);
 assert.equal(validState(bad.state,CONFIG),true);
 const idNotInCard=CONFIG.entries.find(e=>!start.card.includes(e.id)).id;
 assert.equal(act(start,'mark',idNotInCard).changed,false);
 if(start.card.includes(current)){
  const correct=act(start,'mark',current);
  assert.equal(correct.state.marked[0],current);
  assert.equal(correct.state.resolved[0],current);
  assert.equal(correct.state.errors,0);
  assert.equal(validState(correct.state,CONFIG),true);
 }else{
  assert.equal(act(start,'skip').state.resolved[0],current);
 }
});

test('pistas ausentes da cartela podem ser ignoradas, mas pistas presentes não',()=>{
 let state=initialState(CONFIG),foundInside=false,foundOutside=false;
 for(let index=0;index<22&&!state.won;index++){
  const draw=act(state,'draw');
  assert.equal(draw.changed,true);
  state=draw.state;
  const id=pendingClue(state);
  if(state.card.includes(id)){
   foundInside=true;
   const bad=act(state,'skip');
   assert.equal(bad.state.resolved.length,state.resolved.length);
   assert.equal(bad.state.errors,state.errors+1);
   state=act(state,'mark',id).state;
  }else{
   foundOutside=true;
   const good=act(state,'skip');
   assert.equal(good.state.resolved.at(-1),id);
   state=good.state;
  }
  assert.equal(validState(state,CONFIG),true);
 }
 assert.equal(foundInside,true);
 assert.equal(foundOutside,true);
});

test('regras detectam linha, coluna, diagonal e cartela cheia corretamente',()=>{
 const s=initialState(CONFIG),size=CONFIG.settings.size;
 assert.equal(winPatterns(size,'row_column_diagonal').length,10);
 assert.equal(winPatterns(size,'row').length,4);
 assert.equal(winPatterns(size,'column').length,4);
 assert.equal(winPatterns(size,'diagonal').length,2);
 assert.equal(winPatterns(size,'full_card').length,1);
 const diagonal=[0,5,10,15].map(i=>s.card[i]);
 const d={...s,marked:diagonal};
 assert.ok(completedLines(d,CONFIG).some(line=>line.join(',')==='0,5,10,15'));
 const row={...s,marked:s.card.slice(0,4)};
 assert.ok(completedLines(row,CONFIG).some(line=>line.join(',')==='0,1,2,3'));
 const column={...s,marked:[0,4,8,12].map(i=>s.card[i])};
 assert.ok(completedLines(column,CONFIG).some(line=>line.join(',')==='0,4,8,12'));
 const full={...s,marked:s.card.slice()};
 assert.equal(completedLines(full,{...CONFIG,settings:{...CONFIG.settings,win:'full_card'}}).length,1);
});

test('seis cartelas distintas podem chegar a bingo e continuar válidas a cada jogada',()=>{
 const ids=new Set();
 for(let cardNo=0;cardNo<6;cardNo++){
  let state=initialState(CONFIG,cardNo);
  ids.add(state.card.join(','));
  let draws=0;
  while(!state.won&&draws<CONFIG.entries.length){
   state=act(state,'draw').state;
   const clue=pendingClue(state);
   state=act(state,state.card.includes(clue)?'mark':'skip',clue).state;
   assert.equal(validState(state,CONFIG),true,'cartela '+cardNo+' após sorteio '+draws);
   draws++;
  }
  assert.equal(state.won,true);
  assert.ok(completedLines(state,CONFIG).length>0);
  assert.equal(pendingClue(state),null);
  assert.equal(act(state,'draw').changed,false);
  assert.deepEqual(act(state,'restart').state,initialState(CONFIG,cardNo));
 }
 assert.equal(ids.size,6);
});

test('nova cartela mantém a semente e gera nova distribuição e sorteio',()=>{
 const original=initialState(CONFIG);
 const next=act(original,'newCard').state;
 assert.equal(next.cardNo,1);
 assert.notDeepEqual(next.card,original.card);
 assert.equal(next.called.length,0);
 assert.equal(next.marked.length,0);
 assert.equal(validState(next,CONFIG),true);
 assert.deepEqual(next,initialState(CONFIG,1));
});

test('estado adulterado não pode ser restaurado com pistas puladas ou chamadas repetidas',()=>{
 const base=initialState(CONFIG);
 let invalid={...base,called:[base.order[1]]};
 assert.equal(validState(invalid,CONFIG),false,'sorteio fora da ordem');
 invalid={...base,called:[base.order[0],base.order[0]]};
 assert.equal(validState(invalid,CONFIG),false,'chamada repetida');
 invalid={...base,marked:[base.card[0]]};
 assert.equal(validState(invalid,CONFIG),false,'marca sem chamada');
 const onCard=base.order.find(x=>base.card.includes(x));
 const index=base.order.indexOf(onCard);
 invalid={...base,called:base.order.slice(0,index+1),resolved:base.order.slice(0,index+1)};
 assert.equal(validState(invalid,CONFIG),false,'pista de item presente não pode ser resolvida sem marcação');
 invalid={...base,won:true};
 assert.equal(validState(invalid,CONFIG),false,'vitória falsa');
 invalid={...base,card:base.card.slice(1)};
 assert.equal(validState(invalid,CONFIG),false,'cartela truncada');
});

test('progresso usa somente API documentada, sem lançar notas ou completar a atividade',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.match(html,/@media\(max-width:520px\)/);
});

test('manifestos local e geral são idênticos e prompt aguarda escolha do conteúdo',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-bingo-educativo/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='bingo-educativo-cidadania-digital';
 const one=local.examples.find(x=>x.id===id),other=general.examples.find(x=>x.id===id);
 assert.ok(one&&other);
 assert.deepEqual(one,other);
 assert.equal(one.path,file);
 assert.match(one.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(one.prompt,/Aguarde a resposta/);
 assert.match(one.prompt,/\.chatgpt\/games\/11-bingo-educativo\.md/);
 assert.ok(local.examples.some(e=>e.id==='bingo-educativo'&&e.path==='game-bingo-educativo/numeros.html'));
});
