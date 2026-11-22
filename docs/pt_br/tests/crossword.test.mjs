/**
 * Regressões do jogo 08 - Palavras cruzadas.
 * Execute: node --test docs/pt_br/tests/crossword.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const path='game-palavras-cruzadas/ciencias-naturais.html';
const html=await readFile(resolve(root,path),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'script inline necessário');
const begin=js.indexOf('const DATA='),start=js.indexOf('function normalize(value)'),end=js.indexOf('// End pure helpers.');
assert.ok(begin>=0&&start>begin&&end>start,'helpers do jogo não encontrados');
const pure=js.slice(begin,js.indexOf('const root=',begin))+js.slice(start,end);
const {DATA,normalize,letter,key,createBoard,initialState,readWord,isCorrect,checkState}=
 new Function(pure+'return {DATA,normalize,letter,key,createBoard,initialState,readWord,isCorrect,checkState};')();
const puzzle=createBoard(DATA);

test('HTML completo, sem recursos externos ou elementos institucionais',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity"/);
 assert.match(html,/<style>[\s\S]*<\/style>/);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<(?:img|svg|canvas)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotThrow(()=>new Script(js));
});

test('grade válida, numeração coerente e exatamente sete cruzamentos',()=>{
 assert.ok(puzzle,'a grade deve validar');
 assert.equal(puzzle.rows,9);
 assert.equal(puzzle.cols,11);
 assert.equal(puzzle.entries.length,8);
 assert.equal(puzzle.cells.size,45);
 assert.equal([...puzzle.cells.values()].filter(cell=>cell.entries.length===2).length,7);
 assert.deepEqual(puzzle.entries.map(e=>e.number),[1,2,3,4,5,6,7,8]);
});

test('acentos são normalizados sem perder a resposta de exibição',()=>{
 assert.equal(normalize('CÉLULA'),'CELULA');
 assert.equal(normalize('ÁGUA'),'AGUA');
 assert.equal(letter('ê'),'E');
 assert.equal(letter('Á'),'A');
 assert.equal(letter('6'),'');
 assert.equal(puzzle.byId.get('celula').answer,'CÉLULA');
 assert.equal(puzzle.byId.get('celula').answerNormalized,'CELULA');
});

test('todas as respostas podem ser preenchidas corretamente por célula',()=>{
 const values={};
 for(const [position,cell] of puzzle.cells)values[position]=cell.char;
 for(const entry of puzzle.entries){
  assert.equal(readWord(entry,values),entry.answerNormalized,entry.id);
  assert.equal(isCorrect(entry,values),true,entry.id);
 }
 const shared=puzzle.cells.get(key(4,2));
 assert.ok(shared.entries.includes('oxigenio'));
 assert.ok(shared.entries.includes('celula'));
 assert.equal(shared.char,'E');
 const s=initialState();
 s.cells=values;
 s.verified=puzzle.entries.map(e=>e.id);
 assert.equal(checkState(s,puzzle),true);
});

test('cruzamento incompatível e palavra fora da grade são rejeitados',()=>{
 const crossing=structuredClone(DATA);
 crossing.entries[1].answer='AXIGÊNIO';
 assert.equal(createBoard(crossing),null);
 const outOfBounds=structuredClone(DATA);
 outOfBounds.entries[0].col=5;
 assert.equal(createBoard(outOfBounds),null);
 const duplicate=structuredClone(DATA);
 duplicate.entries[1].id=duplicate.entries[0].id;
 assert.equal(createBoard(duplicate),null);
});

test('estado inválido ou adulterado não passa pela restauração',()=>{
 let bad=initialState();
 bad.cells['19,23']='A';
 assert.equal(checkState(bad,puzzle),false);
 bad=initialState();
 bad.cells['0,0']='X';
 bad.verified=['ecossistema'];
 assert.equal(checkState(bad,puzzle),false);
 bad=initialState();
 bad.revealed=['0,0'];
 assert.equal(checkState(bad,puzzle),false);
 bad=initialState();
 bad.hints=DATA.settings.maxHints+1;
 assert.equal(checkState(bad,puzzle),false);
});

test('teclado, toque, paste, Backspace, Tab e foco são previstos',()=>{
 assert.match(js,/event\.key==="Backspace"/);
 assert.match(js,/event\.key==="Tab"/);
 assert.match(js,/event\.key==="Enter"/);
 assert.match(js,/ArrowUp/);
 assert.match(js,/ArrowDown/);
 assert.match(js,/addEventListener\("paste"/);
 assert.match(js,/function focusCell\(/);
 assert.match(js,/function selectEntry\(/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/id="across"/);
 assert.match(html,/id="down"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
});

test('verificação sob demanda, dicas, limpeza e progresso opcional',()=>{
 assert.match(js,/function verifyEntry\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(html,/id="check-word"/);
 assert.match(html,/id="check-all"/);
 assert.match(html,/id="hint"/);
 assert.match(html,/id="clear"/);
 assert.match(html,/id="reset"/);
 assert.doesNotMatch(js,/completion\.complete\(/);
});

test('JSON local e geral registram os mesmos dados e prompt de autoria',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-palavras-cruzadas/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='palavras-cruzadas-ciencias-naturais';
 const a=local.examples.find(e=>e.id===id),b=global.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,path);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/08-palavras-cruzadas\.md/);
 assert.ok(local.examples.some(e=>e.id==='palavras-cruzadas'),'exemplo anterior preservado');
});
