/**
 * Regressões do jogo 07 - Descubra a palavra.
 * Execução: node --test docs/pt_br/tests/word-guess.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-descubra-a-palavra/ciencias-naturais.html';
const html=await readFile(resolve(root,file),'utf8');
const local=JSON.parse(await readFile(resolve(root,'game-descubra-a-palavra/examples.json'),'utf8'));
const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O exemplo deve conter um script inline');
const start=js.indexOf('const gameData=');
const pureStart=js.indexOf('// BEGIN PURE GAME LOGIC');
const pureEnd=js.indexOf('// END PURE GAME LOGIC');
assert.ok(start>=0&&pureStart>start&&pureEnd>pureStart,'A lógica testável deve estar identificada');
const extract=js.slice(start,pureStart)+js.slice(pureStart,pureEnd)+'\n';
const {gameData,normalize,canonicalAnswer,letterKey,validConfig,blankState,isSolvedByLetters,isValidState,updateState}=
 new Function(extract+'return {gameData,normalize,canonicalAnswer,letterKey,validConfig,blankState,isSolvedByLetters,isValidState,updateState};')();

const act=(state,type,value)=>updateState(state,{type,value},gameData);

test('HTML independente e semanticamente limitado à atividade',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity"/);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<img\b/i);
 assert.match(html,/id="answer-input"/);
 assert.match(html,/id="keyboard"/);
 assert.match(html,/id="hint-button"/);
 assert.match(html,/id="summary-restart"/);
 assert.match(html,/aria-live="polite"/);
 assert.doesNotThrow(()=>new Script(js));
});

test('configuração tem quatro rodadas com dados válidos e limitação de erros',()=>{
 assert.equal(validConfig(gameData),true);
 assert.equal(gameData.type,'word_guess');
 assert.equal(gameData.rounds.length,4);
 assert.equal(gameData.settings.maxErrors,6);
 assert.deepEqual(new Set(gameData.rounds.map(r=>r.id)).size,4);
 const invalid=JSON.parse(JSON.stringify(gameData));
 invalid.rounds[0].answer='??';
 assert.equal(validConfig(invalid),false);
 invalid.rounds[0].answer='ÁGUA';
 invalid.rounds[1].id=invalid.rounds[0].id;
 assert.equal(validConfig(invalid),false);
});

test('normalização preserva hífen e espaço e ignora acentos na comparação',()=>{
 assert.equal(normalize('áÇÊÍ'), 'ACEI');
 assert.equal(letterKey('í'),'I');
 assert.equal(canonicalAnswer(' ÁGUA potável '),'AGUA POTAVEL');
 assert.equal(canonicalAnswer('micro - organismo'),'MICRO-ORGANISMO');
 assert.equal(letterKey('7'),null);
 assert.equal(letterKey('-'),null);
 assert.equal(letterKey('ab'),null);
});

test('letras revelam todas as ocorrências, erros e repetição não cobram nova tentativa',()=>{
 let s=blankState();
 const result=act(s,'letter','O');
 s=result.state;
 assert.equal(result.changed,true);
 assert.equal(s.guessed.length,1);
 assert.equal(s.errors,0);
 assert.equal(s.status,'active');
 const repeated=act(s,'letter','o');
 assert.equal(repeated.changed,false);
 assert.equal(repeated.state.errors,0);
 s=act(s,'letter','Z').state;
 assert.equal(s.errors,1);
 assert.equal(act(s,'letter','Z').state.errors,1);
 assert.equal(isSolvedByLetters('FOTOSSÍNTESE', ['F','O','T','S','I','N','E']),true);
});

test('resposta completa valida ortografia sem cobrar respostas repetidas ou inválidas',()=>{
 let s=blankState();
 assert.equal(act(s,'answer','Teste8').changed,false);
 assert.equal(act(s,'answer','').changed,false);
 s=act(s,'answer','RESPOSTA').state;
 assert.equal(s.errors,1);
 assert.equal(act(s,'answer','resposta').changed,false);
 s=act(s,'answer','Fotossintese').state;
 assert.equal(s.status,'solved');
 assert.equal(isValidState(s,gameData),true);
 s=act(s,'next').state;
 assert.equal(s.round,1);
 s=act(s,'answer','Água potável').state;
 assert.equal(s.status,'solved');
 s=act(s,'next').state;
 s=act(s,'answer','micro - organismo').state;
 assert.equal(s.status,'solved');
});

test('limite de seis erros encerra a rodada e bloqueia novas ações',()=>{
 let s=blankState();
 for(const key of ['Z','Y','X','W','Q','K'])s=act(s,'letter',key).state;
 assert.equal(s.errors,6);
 assert.equal(s.status,'missed');
 assert.equal(act(s,'letter','F').changed,false);
 assert.equal(act(s,'hint').changed,false);
 s=act(s,'next').state;
 assert.equal(s.round,1);
 assert.deepEqual(s.resolved,[{id:'fotossintese',outcome:'missed'}]);
});

test('dica adicional só pode ser utilizada uma vez',()=>{
 let s=blankState();
 s=act(s,'hint').state;
 assert.equal(s.hintUsed,true);
 const again=act(s,'hint');
 assert.equal(again.changed,false);
 assert.equal(again.state.hintUsed,true);
});

test('quatro rodadas terminam em resumo e reinício limpa o estado',()=>{
 let s=blankState();
 for(let i=0;i<gameData.rounds.length;i++){
  s=act(s,'answer',gameData.rounds[i].answer).state;
  assert.equal(s.status,'solved');
  s=act(s,'next').state;
 }
 assert.equal(s.status,'summary');
 assert.equal(s.resolved.length,4);
 assert.equal(isValidState(s,gameData),true);
 s=act(s,'restart').state;
 assert.deepEqual(s,blankState());
 assert.equal(isValidState(s,gameData),true);
});

test('estado adulterado ou malformado não pode ser restaurado',()=>{
 const s=blankState();s.guessed=['A','A'];
 assert.equal(isValidState(s,gameData),false);
 const badRound=blankState();badRound.round=123;
 assert.equal(isValidState(badRound,gameData),false);
 const badError=blankState();badError.errors=6;
 assert.equal(isValidState(badError,gameData),false);
});

test('o catálogo local e o geral registram o mesmo exemplo e prompt de autoria',()=>{
 const id='descubra-a-palavra-ciencias-naturais';
 const a=local.examples.find(e=>e.id===id);
 const b=general.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.ok(a.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(a.prompt.includes('Aguarde a resposta'));
 assert.ok(a.prompt.includes('.chatgpt/games/07-descubra-a-palavra.md'));
});
