/**
 * Regressões do jogo 14 - Quem sou eu?
 * Execute: node --test docs/pt_br/tests/who-am-i.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-quem-sou-eu/fundamentos-da-computacao.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'É necessário um script inline');
const start=js.indexOf('const DATA='),pure=js.indexOf('function normalize(value)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'Regras puras ausentes');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,normalize,validate,initialState,isAnswer,pointsFor,totalPoints,validState,transition}=
 new Function(source+'return {DATA,normalize,validate,initialState,isAnswer,pointsFor,totalPoints,validState,transition};')();
const act=(s,type,value)=>transition(s,{type,value},DATA);

test('HTML responsivo, autossuficiente e sem menus, rodapés ou imagens',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.match(html,/<script>[\s\S]+<\/script>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/@media\(max-width:515px\)/);
 assert.doesNotThrow(()=>new Script(js));
});

test('dados contêm seis rodadas, respostas distintas e quatro dicas por rodada',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'who_am_i');
 assert.equal(DATA.settings.maxGuesses,3);
 assert.equal(DATA.rounds.length,6);
 assert.ok(DATA.rounds.every(r=>r.clues.length===4));
 assert.equal(new Set(DATA.rounds.map(r=>r.id)).size,6);
 assert.ok(DATA.rounds.every(r=>r.explanation&&r.aliases.length));
});

test('validação rejeita dicas vazias, IDs duplicados e alias equivalente depois de normalizar',()=>{
 const blank=structuredClone(DATA);
 blank.rounds[0].clues[0]='  ';
 assert.equal(validate(blank),false);
 const repeatedId=structuredClone(DATA);
 repeatedId.rounds[1].id=repeatedId.rounds[0].id;
 assert.equal(validate(repeatedId),false);
 const sameAlias=structuredClone(DATA);
 sameAlias.rounds[5].aliases.push('codigo fonte');
 assert.equal(validate(sameAlias),false);
 const repeatedClue=structuredClone(DATA);
 repeatedClue.rounds[0].clues[1]=repeatedClue.rounds[0].clues[0];
 assert.equal(validate(repeatedClue),false);
 const missing=structuredClone(DATA);
 missing.rounds[0].answer='';
 assert.equal(validate(missing),false);
});

test('normalização aceita aliases, variações de caixa, acentos, hífen e pontuação',()=>{
 assert.equal(normalize('  CÓDIGO\u2013FONTE! '),'codigo fonte');
 assert.equal(isAnswer(DATA.rounds[5],'CODIGO FONTE'),true);
 assert.equal(isAnswer(DATA.rounds[2],'so'),true);
 assert.equal(isAnswer(DATA.rounds[0],' um ALGORITMO! '),true);
 assert.equal(isAnswer(DATA.rounds[4],'rede informática'),true);
 assert.equal(isAnswer(DATA.rounds[3],'banco de dados'),false);
 assert.equal(isAnswer(DATA.rounds[0],'   '),false);
});

test('estados iniciais são mínimos e não revelam respostas no progresso',()=>{
 const s=initialState(DATA);
 assert.equal(validState(s,DATA),true);
 assert.equal(s.index,0);
 assert.equal(s.rounds.length,6);
 assert.equal(s.rounds[0].shown,1);
 assert.deepEqual(s.rounds[0].guesses,[]);
 assert.equal(s.rounds[0].result,null);
 assert.equal(totalPoints(s),0);
 assert.equal(JSON.stringify(s).includes('Código-fonte'),false);
});

test('uma resposta correta na primeira dica recebe 100 pontos e a rodada trava',()=>{
 const start=initialState(DATA),round=DATA.rounds[0];
 const result=act(start,'guess',round.answer);
 assert.equal(result.changed,true);
 assert.equal(result.kind,'good');
 assert.equal(result.state.rounds[0].result,'correct');
 assert.equal(result.state.rounds[0].points,100);
 assert.ok(result.message.includes(round.explanation));
 assert.equal(validState(result.state,DATA),true);
 assert.equal(act(result.state,'guess',round.answer).changed,false);
 assert.equal(act(result.state,'clue').changed,false);
});

test('erros e dicas custam pontos, mas um acerto conserva mínimo de vinte',()=>{
 let s=initialState(DATA);
 s=act(s,'guess','internet').state;
 s=act(s,'clue').state;
 s=act(s,'guess','um algoritmo').state;
 assert.equal(s.rounds[0].points,65);
 assert.equal(validState(s,DATA),true);
 const priced={shown:3,guesses:['a','b','algoritmo']};
 assert.equal(pointsFor(DATA,priced),30);
 const minimum={shown:3,guesses:['a','b','algoritmo']};
 assert.equal(pointsFor({...DATA,settings:{...DATA.settings,revealCost:50}},minimum),20);
});

test('resposta vazia, palpite duplicado e clique repetido não gastam tentativa',()=>{
 let s=initialState(DATA);
 assert.equal(act(s,'guess','   ').changed,false);
 assert.equal(act(s,'next').changed,false);
 s=act(s,'guess','internet').state;
 assert.equal(act(s,'guess','INTERNET').changed,false);
 assert.equal(s.rounds[0].guesses.length,1);
 assert.equal(validState(s,DATA),true);
});

test('três erros encerram a rodada, revelam a resposta e impedem novo envio',()=>{
 let s=initialState(DATA);
 for(const value of ['invenção','nuvem','operador'])s=act(s,'guess',value).state;
 assert.equal(s.rounds[0].guesses.length,3);
 assert.equal(s.rounds[0].result,'attempts');
 assert.equal(s.rounds[0].points,0);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'guess','algoritmo').changed,false);
 assert.equal(act(s,'clue').changed,false);
});

test('a última pista encerra a rodada; não ocorre acerto automático',()=>{
 let s=initialState(DATA);
 for(let i=0;i<3;i++)s=act(s,'clue').state;
 assert.equal(s.rounds[0].shown,4);
 assert.equal(s.rounds[0].result,'clues');
 assert.equal(s.rounds[0].points,0);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'guess','algoritmo').changed,false);
 assert.equal(act(s,'next').state.index,1);
});

test('desistência revela a resposta e permite avançar sem pontuar',()=>{
 let s=act(initialState(DATA),'giveup').state;
 assert.equal(s.rounds[0].result,'gaveup');
 assert.equal(s.rounds[0].points,0);
 assert.equal(validState(s,DATA),true);
 s=act(s,'next').state;
 assert.equal(s.index,1);
 assert.equal(s.rounds[1].shown,1);
});

test('partida mista de seis rodadas chega a resumo consistente e pode reiniciar',()=>{
 let s=initialState(DATA);
 s=act(s,'guess','internet').state;
 s=act(s,'clue').state;
 s=act(s,'guess','um algoritmo').state;
 s=act(s,'next').state;
 for(const guess of ['termo1','termo2','termo3'])s=act(s,'guess',guess).state;
 s=act(s,'next').state;
 for(let i=0;i<3;i++)s=act(s,'clue').state;
 s=act(s,'next').state;
 s=act(s,'giveup').state;
 s=act(s,'next').state;
 s=act(s,'guess','rede informática').state;
 s=act(s,'next').state;
 s=act(s,'guess','codigo fonte').state;
 s=act(s,'next').state;
 assert.equal(s.completed,true);
 assert.equal(s.index,6);
 assert.equal(totalPoints(s),265);
 assert.equal(validState(s,DATA),true);
 assert.equal(act(s,'next').changed,false);
 assert.deepEqual(act(s,'restart').state,initialState(DATA));
});

test('o progresso rejeita vitórias falsas, palpites duplicados e dicas fora do limite',()=>{
 const invalid=initialState(DATA);
 invalid.rounds[0].result='correct';
 invalid.rounds[0].points=100;
 assert.equal(validState(invalid,DATA),false);
 const repeat=initialState(DATA);
 repeat.rounds[0].guesses=['abc','ABC'];
 assert.equal(validState(repeat,DATA),false);
 const range=initialState(DATA);
 range.rounds[0].shown=7;
 assert.equal(validState(range,DATA),false);
 const forged=initialState(DATA);
 forged.completed=true;
 assert.equal(validState(forged,DATA),false);
});

test('segurança usa DOM seguro, SDK opcional e não atribui nota oficial',()=>{
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
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/id="answer"[^>]+maxlength="80"/);
 assert.match(html,/id="feedback"[^>]+tabindex="-1"/);
});

test('os manifestos usam o mesmo exemplo e perguntam ao professor antes de gerar',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-quem-sou-eu/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='quem-sou-eu-fundamentos-computacao';
 const one=local.examples.find(e=>e.id===id),two=general.examples.find(e=>e.id===id);
 assert.ok(one&&two);
 assert.deepEqual(one,two);
 assert.equal(one.path,file);
 assert.match(one.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(one.prompt,/Aguarde a resposta/);
 assert.match(one.prompt,/\.chatgpt\/games\/14-quem-sou-eu\.md/);
});
