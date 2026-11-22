/**
 * Regressões do jogo 19 - Imagem misteriosa.
 * Execute: node --test docs/pt_br/tests/mystery-image.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-imagem-misteriosa/biodiversidade.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'A atividade deve possuir script inline');
const dataStart=js.indexOf('const DATA='),pureStart=js.indexOf('function normalize(value)'),pureEnd=js.indexOf('// End pure rules.');
assert.ok(dataStart>=0&&pureStart>dataStart&&pureEnd>pureStart,'As regras puras não foram encontradas');
const pure=js.slice(dataStart,js.indexOf('const root=',dataStart))+js.slice(pureStart,pureEnd);
const {DATA,normalize,validate,revealOrder,isAnswer,initialState,shownCount,validState,transition}=
 new Function(pure+'return {DATA,normalize,validate,revealOrder,isAnswer,initialState,shownCount,validState,transition};')();
const action=(s,type,value)=>transition(s,{type,value},DATA);
const clone=x=>structuredClone(x);

test('HTML completo, isolado, responsivo e sem menus ou recursos externos',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:530px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(js));
});

test('três figuras SVG originais e distintas, sem dependências externas',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'reveal_image');
 assert.equal(DATA.rounds.length,3);
 assert.deepEqual(DATA.rounds.map(r=>r.answer),['Cacto','Borboleta','Polvo']);
 assert.equal(new Set(DATA.rounds.map(r=>r.image.src)).size,3);
 for(const round of DATA.rounds){
  assert.match(round.image.src,/^data:image\/svg\+xml;charset=utf-8,/);
  const xml=decodeURIComponent(round.image.src.split(',').slice(1).join(','));
  assert.match(xml,/^<svg xmlns="http:\/\/www\.w3\.org\/2000\/svg"/);
  assert.match(xml,/viewBox="0 0 640 400"/);
  assert.match(xml,/<\/svg>$/);
  assert.doesNotMatch(xml.replace('http://www.w3.org/2000/svg',''),/<(?:script|image|foreignObject)\b|https?:\/\/|href=/i);
  assert.match(round.image.credit,/original/);
  assert.ok(round.image.alt.length>35);
 }
});

test('mosaico possui 16 peças, quatro colunas, quatro linhas e proporção 8:5',()=>{
 assert.equal(DATA.settings.tiles,16);
 assert.equal(DATA.settings.columns,4);
 assert.equal(DATA.settings.rows,4);
 assert.equal(DATA.settings.initialShown,2);
 assert.equal(DATA.settings.revealPerHint,3);
 assert.equal(DATA.settings.revealPerMiss,2);
 assert.match(html,/aspect-ratio:8\/5/);
 assert.match(html,/\.image-wrap #image-slot\{position:absolute;inset:0\}/);
 assert.match(html,/grid-template-columns:repeat\(4,minmax\(0,1fr\)\)/);
 assert.match(html,/grid-template-rows:repeat\(4,minmax\(0,1fr\)\)/);
 assert.match(html,/object-fit:contain/);
});

test('validação rejeita zero peças ou grade incompatível',()=>{
 const noTiles=clone(DATA);
 noTiles.settings.tiles=0;
 assert.equal(validate(noTiles),false);
 const mismatch=clone(DATA);
 mismatch.settings.tiles=15;
 assert.equal(validate(mismatch),false);
 const invalidRows=clone(DATA);
 invalidRows.settings.rows=0;
 assert.equal(validate(invalidRows),false);
 const tooMany=clone(DATA);
 tooMany.settings.columns=10;
 assert.equal(validate(tooMany),false);
});

test('validação de quantidade revelada e limites de tentativas e dicas',()=>{
 const initial=clone(DATA);
 initial.settings.initialShown=initial.settings.tiles;
 assert.equal(validate(initial),false);
 const hint=clone(DATA);
 hint.settings.revealPerHint=0;
 assert.equal(validate(hint),false);
 const miss=clone(DATA);
 miss.settings.revealPerMiss=-1;
 assert.equal(validate(miss),false);
 const guesses=clone(DATA);
 guesses.settings.maxGuesses=0;
 assert.equal(validate(guesses),false);
 const hintCount=clone(DATA);
 hintCount.settings.maxHints=11;
 assert.equal(validate(hintCount),false);
});

test('respostas e aliases são normalizados por caixa, acento e pontuação',()=>{
 assert.equal(normalize(' CÁCTUS! '),'cactus');
 assert.equal(isAnswer(DATA.rounds[0],'CÁCTUS!'),true);
 assert.equal(isAnswer(DATA.rounds[1],'BUTTERFLY'),true);
 assert.equal(isAnswer(DATA.rounds[2],'octopus'),true);
 assert.equal(isAnswer(DATA.rounds[0],'banana'),false);
 assert.equal(isAnswer(DATA.rounds[1],'   '),false);
});

test('aliases duplicados e respostas equivalentes em rodadas distintas são rejeitados',()=>{
 const duplicate=clone(DATA);
 duplicate.rounds[0].aliases.push('CÁCTUS');
 assert.equal(validate(duplicate),false);
 const repeated=clone(DATA);
 repeated.rounds[1].answer=duplicate.rounds[0].answer;
 assert.equal(validate(repeated),false);
 const blank=clone(DATA);
 blank.rounds[1].aliases=[''];
 assert.equal(validate(blank),false);
 const tooLong=clone(DATA);
 tooLong.rounds[1].aliases=['a'.repeat(81)];
 assert.equal(validate(tooLong),false);
});

test('imagens ausentes, imagens repetidas e descrições vazias são inválidas',()=>{
 const missing=clone(DATA);
 missing.rounds[0].image.src='';
 assert.equal(validate(missing),false);
 const remote=clone(DATA);
 remote.rounds[0].image.src='https://example.com/cacto.jpg';
 assert.equal(validate(remote),false);
 const duplicates=clone(DATA);
 duplicates.rounds[2].image.src=duplicates.rounds[0].image.src;
 assert.equal(validate(duplicates),false);
 const noAlt=clone(DATA);
 noAlt.rounds[1].image.alt='';
 assert.equal(validate(noAlt),false);
 const noCredit=clone(DATA);
 noCredit.rounds[1].image.credit='';
 assert.equal(validate(noCredit),false);
});

test('IDs duplicados, pistas ausentes ou pistas vazias são recusados',()=>{
 const id=clone(DATA);
 id.rounds[1].id=id.rounds[0].id;
 assert.equal(validate(id),false);
 const missing=clone(DATA);
 missing.rounds[0].textClues.pop();
 assert.equal(validate(missing),false);
 const empty=clone(DATA);
 empty.rounds[1].textClues[0]='  ';
 assert.equal(validate(empty),false);
});

test('ordem de revelação é determinística e contém cada posição uma vez',()=>{
 for(const r of DATA.rounds){
  const first=revealOrder(DATA,r),again=revealOrder(DATA,r);
  assert.deepEqual(first,again);
  assert.equal(first.length,16);
  assert.equal(new Set(first).size,16);
  assert.deepEqual([...first].sort((a,b)=>a-b),Array.from({length:16},(_,i)=>i));
 }
 assert.notDeepEqual(revealOrder(DATA,DATA.rounds[0]),revealOrder(DATA,DATA.rounds[1]));
});

test('estado inicial revela apenas duas peças e não possui tentativa ou dica',()=>{
 const s=initialState(DATA);
 assert.equal(validState(s,DATA),true);
 assert.equal(s.index,0);
 assert.equal(shownCount(DATA,s.rounds[0]),2);
 assert.deepEqual(s.rounds[0].guesses,[]);
 assert.equal(s.rounds[0].hints,0);
 assert.equal(s.rounds[0].result,null);
 assert.equal(s.completed,false);
});

test('revelar dica expõe mais três peças e apresenta pista textual equivalente',()=>{
 const s=initialState(DATA);
 const result=action(s,'hint');
 assert.equal(result.changed,true);
 assert.equal(result.kind,'neutral');
 assert.equal(result.state.rounds[0].hints,1);
 assert.equal(shownCount(DATA,result.state.rounds[0]),5);
 assert.ok(result.message.includes(DATA.rounds[0].textClues[0]));
 assert.equal(validState(result.state,DATA),true);
});

test('revelar as cinco dicas mostra a imagem inteira sem concluir por adivinhação',()=>{
 let s=initialState(DATA);
 for(let i=0;i<5;i++){
  const result=action(s,'hint');
  assert.equal(result.changed,true);
  s=result.state;
 }
 assert.equal(shownCount(DATA,s.rounds[0]),16);
 assert.equal(s.rounds[0].result,null);
 assert.equal(validState(s,DATA),true);
 assert.equal(action(s,'hint').changed,false);
 assert.equal(action(s,'guess','cacto').state.rounds[0].result,'correct');
});

test('dicas textuais continuam disponíveis mesmo após revelar todas as peças',()=>{
 let s=initialState(DATA);
 // Com três palpites errados e três dicas, a grade fica completamente exposta.
 for(const wrong of ['gato','rato','coelho'])s=action(s,'guess',wrong).state;
 for(let i=0;i<3;i++)s=action(s,'hint').state;
 assert.equal(shownCount(DATA,s.rounds[0]),16);
 assert.equal(s.rounds[0].hints,3);
 assert.equal(s.rounds[0].result,null);
 const extra=action(s,'hint');
 assert.equal(extra.changed,true);
 assert.equal(extra.state.rounds[0].hints,4);
 assert.equal(shownCount(DATA,extra.state.rounds[0]),16);
 assert.ok(extra.message.includes(DATA.rounds[0].textClues[3]));
 assert.equal(validState(extra.state,DATA),true);
});

test('cada erro revela duas peças, sem ultrapassar 16 ou duplicar palpite',()=>{
 let s=initialState(DATA);
 s=action(s,'guess','coelho').state;
 assert.equal(shownCount(DATA,s.rounds[0]),4);
 assert.equal(s.rounds[0].guesses.length,1);
 assert.equal(action(s,'guess','COELHO').changed,false);
 s=action(s,'hint').state;
 assert.equal(shownCount(DATA,s.rounds[0]),7);
 s=action(s,'guess','gato').state;
 assert.equal(shownCount(DATA,s.rounds[0]),9);
 assert.equal(validState(s,DATA),true);
});

test('resposta vazia e entrada acima de 80 caracteres são recusadas',()=>{
 const s=initialState(DATA);
 assert.equal(action(s,'guess','  ').changed,false);
 assert.equal(action(s,'guess','x'.repeat(81)).changed,false);
 assert.equal(s.rounds[0].guesses.length,0);
 assert.equal(action(s,'next').changed,false);
});

test('resposta alternativa correta encerra a rodada e revela o restante sem custo extra',()=>{
 let s=initialState(DATA);
 s=action(s,'guess','cáCtUs').state;
 assert.equal(s.rounds[0].result,'correct');
 assert.equal(s.rounds[0].guesses.length,1);
 assert.equal(shownCount(DATA,s.rounds[0]),2);
 assert.equal(action(s,'guess','cacto').changed,false);
 assert.equal(action(s,'hint').changed,false);
 assert.equal(validState(s,DATA),true);
 s=action(s,'next').state;
 assert.equal(s.index,1);
});

test('quatro erros esgotam tentativas, preservam acertos e bloqueiam novas respostas',()=>{
 let s=initialState(DATA);
 for(const word of ['cabrito','lobo','gato','leão'])s=action(s,'guess',word).state;
 assert.equal(s.rounds[0].guesses.length,4);
 assert.equal(s.rounds[0].result,'attempts');
 assert.equal(shownCount(DATA,s.rounds[0]),10);
 assert.equal(validState(s,DATA),true);
 assert.equal(action(s,'guess','cacto').changed,false);
 assert.equal(action(s,'hint').changed,false);
 assert.equal(action(s,'next').state.index,1);
});

test('desistência encerra rodada sem resposta e revela descrição final',()=>{
 const start=initialState(DATA),end=action(start,'giveup');
 assert.equal(end.changed,true);
 assert.equal(end.kind,'warn');
 assert.equal(end.state.rounds[0].result,'gaveup');
 assert.equal(end.state.rounds[0].guesses.length,0);
 assert.equal(validState(end.state,DATA),true);
 assert.equal(action(end.state,'hint').changed,false);
 assert.equal(action(end.state,'next').state.index,1);
});

test('três rodadas mistas chegam ao resumo e podem ser reiniciadas',()=>{
 let s=initialState(DATA);
 s=action(s,'guess','Cacto').state;
 s=action(s,'next').state;
 s=action(s,'guess','gato').state;
 s=action(s,'giveup').state;
 s=action(s,'next').state;
 for(const wrong of ['gato','pato','tatu','ovelha'])s=action(s,'guess',wrong).state;
 s=action(s,'next').state;
 assert.equal(s.completed,true);
 assert.equal(s.index,3);
 assert.equal(s.rounds[0].result,'correct');
 assert.equal(s.rounds[1].result,'gaveup');
 assert.equal(s.rounds[2].result,'attempts');
 assert.equal(validState(s,DATA),true);
 assert.equal(action(s,'next').changed,false);
 assert.deepEqual(action(s,'restart').state,initialState(DATA));
});

test('progresso adulterado ou tentativa de forjar acerto é rejeitado',()=>{
 const fake=initialState(DATA);
 fake.rounds[0].result='correct';
 assert.equal(validState(fake,DATA),false);
 const already=initialState(DATA);
 already.rounds[0].guesses=['cacto'];
 assert.equal(validState(already,DATA),false);
 const duplicate=initialState(DATA);
 duplicate.rounds[0].guesses=['gato','GATO'];
 assert.equal(validState(duplicate,DATA),false);
 const future=initialState(DATA);
 future.rounds[2].hints=2;
 assert.equal(validState(future,DATA),false);
 const forged=initialState(DATA);
 forged.completed=true;
 assert.equal(validState(forged,DATA),false);
});

test('imagem não carregada mostra fallback sem exigir o recurso visual',()=>{
 assert.match(js,/figure\.addEventListener\("error"/);
 assert.match(js,/figure\.hidden=true;missing\.hidden=false/);
 assert.match(js,/Ilustração indisponível/);
 assert.match(js,/A atividade continua com as dicas textuais/);
 assert.match(js,/figure\.src=round\.image\.src/);
 assert.match(js,/figure\.width=640;figure\.height=400/);
 assert.match(html,/\[hidden\]\{display:none!important\}/);
});

test('carregamento lento não bloqueia tentativas nem recarrega a imagem a cada dica',()=>{
 assert.match(js,/loadGeneration/);
 assert.match(js,/roundStamp!==loadGeneration/);
 assert.match(js,/if\(\$\("image-slot"\)\.dataset\.round!==round\.id\|\|\$\("image-slot"\)\.dataset\.finished!==String\(finished\)\)/);
 assert.match(js,/buildImage\(round,finished\)/);
 assert.match(js,/root\.addEventListener\("submit"/);
 assert.doesNotMatch(js,/await figure\.decode\(/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
});

test('campo de texto, foco, leitor de tela e versão móvel não dependem da imagem',()=>{
 assert.match(html,/id="guess"[^>]+maxlength="80"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="clues"/);
 assert.match(html,/id="mosaic"[^>]+aria-hidden="true"/);
 assert.match(html,/Revelar mais e ler dica/);
 assert.match(html,/Desistir da imagem/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(js,/document\.createElement\(tag\)/);
 assert.match(js,/textContent=value/);
});

test('persistência é opcional, versionada e não conclui nem atribui nota oficial',()=>{
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

test('dois JSONs contêm mesmo prompt, que pergunta ao professor e espera a escolha',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-imagem-misteriosa/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='imagem-misteriosa-biodiversidade';
 const a=local.examples.find(x=>x.id===id),b=global.examples.find(x=>x.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/19-imagem-misteriosa\.md/);
});
