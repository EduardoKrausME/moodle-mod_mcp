/**
 * Jogo 21 - Monte a frase.
 * Execute: node --test docs/pt_br/tests/sentence-order.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-monte-a-frase/ciencia-em-palavras.html';
const html=await readFile(resolve(root,file),'utf8');
const script=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(script,'O HTML precisa ter JavaScript incorporado');
const pureStart=script.indexOf('function hashSeed(source)'),pureEnd=script.indexOf('// End pure rules.');
assert.ok(pureStart>=0&&pureEnd>pureStart,'As regras puras precisam estar disponíveis');
const rules=script.slice(script.indexOf('const DATA='),script.indexOf('const root='))+script.slice(pureStart,pureEnd);
const {DATA,hashSeed,shuffle,tokenById,isAccepted,validate,bankOrder,initialState,validState,transition}=
 new Function(rules+'return {DATA,hashSeed,shuffle,tokenById,isAccepted,validate,bankOrder,initialState,validState,transition};')();
const clone=value=>structuredClone(value);
const action=(state,type,props={})=>transition(state,{type,...props},DATA);
const getRound=id=>DATA.rounds.find(r=>r.id===id);
const addAll=(state,ids)=>{for(const id of ids)state=action(state,'add',{id}).state;return state;};

test('HTML independente sem menus, cabeçalho, rodapé, imagens ou dependências remotas',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|picture|canvas)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:560px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(script));
});

test('cinco frases possuem tokens únicos, pontuação, explicação e ordens válidas',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'sentence_order');
 assert.equal(DATA.rounds.length,5);
 assert.ok(DATA.rounds.every(r=>r.tokens.length>=5&&r.tokens.length<=10));
 assert.ok(DATA.rounds.every(r=>r.topic&&r.task&&r.clue&&r.explanation));
 assert.ok(DATA.rounds.some(r=>r.tokens.some(t=>/[.,!?]$/.test(t.text))));
 assert.ok(DATA.rounds.some(r=>r.acceptedOrders.length===2));
});

test('frases com palavras repetidas usam IDs distintos para cada ocorrência',()=>{
 const round=getRound('agua-tratamento');
 assert.ok(round);
 const words=round.tokens.filter(t=>t.text==='que');
 assert.equal(words.length,2);
 assert.notEqual(words[0].id,words[1].id);
 assert.ok(round.acceptedOrders[0].includes(words[0].id));
 assert.ok(round.acceptedOrders[0].includes(words[1].id));
});

test('duas ocorrências idênticas podem trocar de ID sem tornar frase errada',()=>{
 const round=getRound('agua-tratamento'),swapped=[...round.acceptedOrders[0]];
 [swapped[2],swapped[5]]=[swapped[5],swapped[2]];
 assert.notDeepEqual(swapped,round.acceptedOrders[0]);
 assert.equal(isAccepted(round,round.acceptedOrders[0]),true);
 assert.equal(isAccepted(round,swapped),true);
 assert.equal(new Set(swapped).size,swapped.length);
});

test('duas ordens gramaticais diferentes são explicitamente aceitas',()=>{
 const round=getRound('colaboracao');
 assert.equal(round.acceptedOrders.length,2);
 assert.equal(isAccepted(round,round.acceptedOrders[0]),true);
 assert.equal(isAccepted(round,round.acceptedOrders[1]),true);
 assert.equal(round.acceptedOrders[0].map(x=>tokenById(round,x).text).join(' '),'Maria e João estudam juntos.');
 assert.equal(round.acceptedOrders[1].map(x=>tokenById(round,x).text).join(' '),'João e Maria estudam juntos.');
});

test('pontuação faz parte de tokens e não é ignorada',()=>{
 const round=getRound('chuva-solo');
 assert.equal(tokenById(round,'b').text,'chove,');
 assert.equal(tokenById(round,'h').text,'água.');
 assert.equal(isAccepted(round,round.acceptedOrders[0]),true);
 assert.equal(isAccepted(round,['a','b','c','d','e','f','h','g']),false);
 const another=clone(DATA);
 another.rounds[3].tokens.find(t=>t.id==='b').text='chove';
 assert.equal(validate(another),true,'O contrato aceita pontuação como texto e não presume correção');
});

test('IDs duplicados e texto vazio de token são inválidos',()=>{
 const duplicate=clone(DATA);
 duplicate.rounds[0].tokens[1].id=duplicate.rounds[0].tokens[0].id;
 assert.equal(validate(duplicate),false);
 const empty=clone(DATA);
 empty.rounds[1].tokens[2].text='   ';
 assert.equal(validate(empty),false);
 const leading=clone(DATA);
 leading.rounds[2].tokens[1].text=' e ';
 assert.equal(validate(leading),false);
 const duplicateRound=clone(DATA);
 duplicateRound.rounds[1].id=duplicateRound.rounds[0].id;
 assert.equal(validate(duplicateRound),false);
});

test('acceptedOrders não permite tokens ausentes, repetidos ou desconhecidos',()=>{
 const missing=clone(DATA);
 missing.rounds[0].acceptedOrders[0].pop();
 assert.equal(validate(missing),false);
 const repeated=clone(DATA);
 repeated.rounds[0].acceptedOrders[0][1]=repeated.rounds[0].acceptedOrders[0][0];
 assert.equal(validate(repeated),false);
 const unknown=clone(DATA);
 unknown.rounds[1].acceptedOrders[0][0]='unknown';
 assert.equal(validate(unknown),false);
 const duplicatedOrder=clone(DATA);
 duplicatedOrder.rounds[1].acceptedOrders.push([...duplicatedOrder.rounds[1].acceptedOrders[0]]);
 assert.equal(validate(duplicatedOrder),false);
});

test('ordens alternativas semanticamente idênticas são rejeitadas no JSON de autoria',()=>{
 const duplicate=clone(DATA);
 const round=duplicate.rounds[1],swapped=[...round.acceptedOrders[0]];
 [swapped[2],swapped[5]]=[swapped[5],swapped[2]];
 round.acceptedOrders.push(swapped);
 assert.equal(validate(duplicate),false);
});

test('limites de rounds e tamanho máximo são validados',()=>{
 const short=clone(DATA);
 short.rounds=short.rounds.slice(0,2);
 assert.equal(validate(short),false);
 const invalid=clone(DATA);
 invalid.settings.maxTokens=2;
 assert.equal(validate(invalid),false);
 const lots=clone(DATA);
 lots.settings.maxTokens=20;
 lots.rounds[0].tokens[0].text='a'.repeat(46);
 assert.equal(validate(lots),false);
});

test('embaralhamento é determinístico e usa cada token uma vez',()=>{
 const original=getRound('metodo-cientifico'),first=bankOrder(DATA,original),second=bankOrder(DATA,original);
 assert.deepEqual(first,second);
 assert.equal(new Set(first).size,original.tokens.length);
 assert.deepEqual([...first].sort(),original.tokens.map(t=>t.id).sort());
 assert.equal(isAccepted(original,first),false);
 for(const round of DATA.rounds){
  const shuffled=bankOrder(DATA,round);
  assert.equal(new Set(shuffled).size,round.tokens.length);
  assert.equal(isAccepted(round,shuffled),false);
 }
 assert.equal(hashSeed('fixed'),hashSeed('fixed'));
 assert.deepEqual(shuffle(['a','b','c','d'],'fixed'),shuffle(['a','b','c','d'],'fixed'));
});

test('estado inicial possui cinco rodadas vazias, sem erros nem conclusão',()=>{
 const state=initialState(DATA);
 assert.equal(state.index,0);
 assert.equal(state.completed,false);
 assert.equal(validState(state,DATA),true);
 assert.ok(state.rounds.every(x=>x.selected.length===0&&x.previous===null&&x.errors===0&&!x.solved));
});

test('adicionar token altera a ordem e não permite duplicar o mesmo ID',()=>{
 let state=initialState(DATA);
 const first=action(state,'add',{id:'a'});
 assert.equal(first.changed,true);
 assert.deepEqual(first.state.rounds[0].selected,['a']);
 state=first.state;
 assert.equal(action(state,'add',{id:'a'}).changed,false);
 assert.equal(action(state,'add',{id:'not-a-token'}).changed,false);
 assert.deepEqual(state.rounds[0].selected,['a']);
 assert.equal(validState(state,DATA),true);
});

test('palavras repetidas podem ser inseridas separadamente e não colapsam IDs',()=>{
 let state=initialState(DATA);
 state=addAll(state,getRound('metodo-cientifico').acceptedOrders[0]);
 state=action(state,'check').state;
 state=action(state,'next').state;
 state=action(state,'add',{id:'c'}).state;
 state=action(state,'add',{id:'f'}).state;
 assert.deepEqual(state.rounds[1].selected,['c','f']);
 assert.equal(validState(state,DATA),true);
});

test('mover para esquerda ou direita troca apenas dois IDs adjacentes',()=>{
 let state=initialState(DATA);
 state=action(state,'add',{id:'a'}).state;
 state=action(state,'add',{id:'b'}).state;
 state=action(state,'add',{id:'c'}).state;
 const moved=action(state,'move',{id:'c',delta:-1});
 assert.equal(moved.changed,true);
 assert.deepEqual(moved.state.rounds[0].selected,['a','c','b']);
 assert.equal(moved.state.rounds[0].selected.length,3);
 assert.equal(validState(moved.state,DATA),true);
 const restored=action(moved.state,'move',{id:'c',delta:1});
 assert.deepEqual(restored.state.rounds[0].selected,['a','b','c']);
});

test('movimento fora do intervalo ou com delta diagonal não altera o estado',()=>{
 let state=initialState(DATA);
 state=action(state,'add',{id:'a'}).state;
 state=action(state,'add',{id:'b'}).state;
 assert.equal(action(state,'move',{id:'a',delta:-1}).changed,false);
 assert.equal(action(state,'move',{id:'b',delta:1}).changed,false);
 assert.equal(action(state,'move',{id:'a',delta:2}).changed,false);
 assert.equal(action(state,'move',{id:'unknown',delta:1}).changed,false);
 assert.equal(validState(state,DATA),true);
});

test('remover devolve token ao banco sem perder a ordem dos restantes',()=>{
 let state=initialState(DATA);
 for(const id of ['a','b','c'])state=action(state,'add',{id}).state;
 const result=action(state,'remove',{id:'b'});
 assert.equal(result.changed,true);
 assert.deepEqual(result.state.rounds[0].selected,['a','c']);
 assert.equal(action(result.state,'remove',{id:'b'}).changed,false);
 assert.equal(validState(result.state,DATA),true);
});

test('Desfazer restaura a última alteração sem desfazer várias vezes',()=>{
 let state=initialState(DATA);
 state=action(state,'add',{id:'a'}).state;
 state=action(state,'add',{id:'b'}).state;
 const undone=action(state,'undo');
 assert.equal(undone.changed,true);
 assert.deepEqual(undone.state.rounds[0].selected,['a']);
 assert.equal(undone.state.rounds[0].previous,null);
 assert.equal(action(undone.state,'undo').changed,false);
 assert.equal(validState(undone.state,DATA),true);
});

test('limpar frase esvazia e Desfazer recupera a montagem anterior',()=>{
 let state=initialState(DATA);
 state=action(state,'add',{id:'a'}).state;
 state=action(state,'add',{id:'b'}).state;
 const cleared=action(state,'clear');
 assert.deepEqual(cleared.state.rounds[0].selected,[]);
 assert.deepEqual(action(cleared.state,'undo').state.rounds[0].selected,['a','b']);
 assert.equal(action(cleared.state,'clear').changed,false);
});

test('resposta parcial não é pontuada nem incrementa erros',()=>{
 const state=action(initialState(DATA),'add',{id:'a'}).state;
 const result=action(state,'check');
 assert.equal(result.changed,false);
 assert.match(result.message,/incompleta/);
 assert.equal(result.state.rounds[0].errors,0);
 assert.equal(result.state.rounds[0].solved,false);
 assert.equal(action(state,'next').changed,false);
});

test('resposta completa incorreta incrementa erro sem destruir a construção',()=>{
 let state=initialState(DATA);
 const wrong=[...getRound('metodo-cientifico').acceptedOrders[0]];
 [wrong[0],wrong[1]]=[wrong[1],wrong[0]];
 state=addAll(state,wrong);
 const result=action(state,'check');
 assert.equal(result.changed,true);
 assert.equal(result.kind,'warn');
 assert.equal(result.state.rounds[0].errors,1);
 assert.equal(result.state.rounds[0].solved,false);
 assert.deepEqual(result.state.rounds[0].selected,wrong);
 assert.equal(validState(result.state,DATA),true);
});

test('correção da ordem após erro permite acertar e bloqueia novas edições',()=>{
 let state=initialState(DATA);
 const wrong=[...getRound('metodo-cientifico').acceptedOrders[0]];
 [wrong[0],wrong[1]]=[wrong[1],wrong[0]];
 state=addAll(state,wrong);
 state=action(state,'check').state;
 state=action(state,'move',{id:'b',delta:1}).state;
 const solved=action(state,'check');
 assert.equal(solved.changed,true);
 assert.equal(solved.kind,'good');
 assert.equal(solved.state.rounds[0].solved,true);
 assert.equal(solved.state.rounds[0].errors,1);
 assert.equal(solved.state.rounds[0].previous,null);
 assert.equal(action(solved.state,'add',{id:'a'}).changed,false);
 assert.equal(action(solved.state,'move',{id:'a',delta:1}).changed,false);
 assert.equal(validState(solved.state,DATA),true);
});

test('dica gramatical não entrega gabarito, não altera estado nem desconta pontos',()=>{
 const state=initialState(DATA);
 const outcome=action(state,'hint');
 assert.equal(outcome.changed,false);
 assert.match(outcome.message,/Dica/);
 assert.ok(outcome.message.includes(DATA.rounds[0].clue));
 assert.deepEqual(outcome.state,state);
});

test('todas as cinco frases podem ser montadas e finalizadas',()=>{
 let state=initialState(DATA);
 for(let i=0;i<DATA.rounds.length;i++){
  state=addAll(state,DATA.rounds[i].acceptedOrders[0]);
  const checked=action(state,'check');
  assert.equal(checked.kind,'good');
  state=checked.state;
  assert.equal(validState(state,DATA),true);
  state=action(state,'next').state;
  assert.equal(validState(state,DATA),true);
 }
 assert.equal(state.completed,true);
 assert.equal(state.index,5);
 assert.equal(state.rounds.filter(x=>x.solved).length,5);
 assert.equal(action(state,'next').changed,false);
 assert.deepEqual(action(state,'restart').state,initialState(DATA));
});

test('a ordem alternativa da terceira frase também conclui rodada',()=>{
 let state=initialState(DATA);
 for(let i=0;i<2;i++){
  state=addAll(state,DATA.rounds[i].acceptedOrders[0]);
  state=action(state,'check').state;
  state=action(state,'next').state;
 }
 state=addAll(state,getRound('colaboracao').acceptedOrders[1]);
 assert.equal(action(state,'check').state.rounds[2].solved,true);
});

test('estado adulterado com ID externo, token duplicado ou conclusão forjada é rejeitado',()=>{
 const outside=initialState(DATA);
 outside.rounds[0].selected=['unknown'];
 assert.equal(validState(outside,DATA),false);
 const duplicate=initialState(DATA);
 duplicate.rounds[0].selected=['a','a'];
 assert.equal(validState(duplicate,DATA),false);
 const forced=initialState(DATA);
 forced.rounds[0].solved=true;
 assert.equal(validState(forced,DATA),false);
 const final=initialState(DATA);
 final.completed=true;
 assert.equal(validState(final,DATA),false);
});

test('estado restaurado não pode pular rodadas nem preencher rodadas futuras',()=>{
 const advanced=initialState(DATA);
 advanced.index=2;
 assert.equal(validState(advanced,DATA),false);
 const future=initialState(DATA);
 future.rounds[4].selected=['a'];
 assert.equal(validState(future,DATA),false);
 const malformed=initialState(DATA);
 malformed.rounds[0].previous=['unknown'];
 assert.equal(validState(malformed,DATA),false);
 const highErrors=initialState(DATA);
 highErrors.rounds[0].errors=1001;
 assert.equal(validState(highErrors,DATA),false);
});

test('tokens são construídos somente com DOM seguro, sem injeção de HTML',()=>{
 assert.match(script,/function el\(tag,cls,value\)/);
 assert.match(script,/document\.createElement\(tag\)/);
 assert.match(script,/textContent=value/);
 assert.doesNotMatch(script,/\.innerHTML\s*=/);
 assert.match(script,/dataset\.add=id/);
 assert.match(script,/dataset\.remove=id/);
 assert.match(script,/dataset\.move=id/);
 assert.match(script,/dataset\.delta=/);
 const htmlToken=clone(DATA);
 htmlToken.rounds[0].tokens[0].text='<img src=x onerror=alert(1)>';
 assert.equal(validate(htmlToken),true,'JSON aceita texto literal; nunca é inserido como HTML');
});

test('acessibilidade contempla botões, foco, leitores de tela e telas pequenas',()=>{
 assert.match(html,/id="bank"[^>]+role="group"/);
 assert.match(html,/id="sentence"[^>]+role="group"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="preview"/);
 assert.match(html,/id="undo"/);
 assert.match(html,/id="clear"/);
 assert.match(html,/id="check"/);
 assert.match(html,/id="hint"/);
 assert.match(script,/button\.setAttribute\("aria-label"/);
 assert.match(script,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/@media\(max-width:560px\)/);
});

test('SDK opcional persiste revisão sem gerar nota Moodle',()=>{
 assert.match(script,/function getState\(/);
 assert.match(script,/function restoreState\(/);
 assert.match(script,/function destroy\(/);
 assert.match(script,/function handleAction\(/);
 assert.match(script,/progress\.load\(/);
 assert.match(script,/progress\.save\(/);
 assert.match(script,/version:remote\.version/);
 assert.match(script,/TextEncoder/);
 assert.doesNotMatch(script,/completion\.complete\(/);
 assert.doesNotMatch(script,/attempt\.finish\(/);
});

test('manifestos têm exatamente o mesmo prompt e aguardam tema do professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-monte-a-frase/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='monte-a-frase-ciencia-em-palavras';
 const first=local.examples.find(e=>e.id===id),second=global.examples.find(e=>e.id===id);
 assert.ok(first&&second);
 assert.deepEqual(first,second);
 assert.equal(first.path,file);
 assert.match(first.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(first.prompt,/Aguarde a resposta/);
 assert.match(first.prompt,/\.chatgpt\/games\/21-monte-a-frase\.md/);
});
