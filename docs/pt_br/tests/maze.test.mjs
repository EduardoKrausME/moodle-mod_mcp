/**
 * Regressões do jogo 20 - Labirinto educativo.
 * Execute: node --test docs/pt_br/tests/maze.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-labirinto-educativo/missao-energia-limpa.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O exemplo precisa de um script incorporado');
const start=js.indexOf('const DATA='),pure=js.indexOf('function key(r,c)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'Regras de labirinto ausentes');
const code=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,DIRS,key,marker,passable,gateAt,reachable,validate,initialState,validState,transition}=
 new Function(code+'return {DATA,DIRS,key,marker,passable,gateAt,reachable,validate,initialState,validState,transition};')();
const clone=val=>structuredClone(val);
const act=(state,type,extra={})=>transition(state,{type,...extra},DATA);
const route=[
 [1,1],...[...Array(8)].map((_,i)=>[1,i+2]),
 ...[...Array(4)].map((_,i)=>[i+2,9]),
 ...[...Array(8)].map((_,i)=>[5,8-i]),
 ...[...Array(4)].map((_,i)=>[6+i,1]),
 ...[...Array(8)].map((_,i)=>[9,2+i])
];

test('HTML completo, isolado, responsivo, sem menus nem imagens',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/@media\(max-width:520px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(js));
});

test('grade 11×11 é retangular, com uma entrada e uma saída',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'maze');
 assert.equal(DATA.grid.length,11);
 assert.ok(DATA.grid.every(row=>row.length===11));
 assert.deepEqual(marker(DATA,'S'),{row:1,col:1});
 assert.deepEqual(marker(DATA,'E'),{row:9,col:9});
 assert.ok(DATA.grid.every(row=>/^[.#SE]+$/.test(row)));
 assert.equal(DATA.grid.join('').split('S').length,2);
 assert.equal(DATA.grid.join('').split('E').length,2);
});

test('BFS encontra uma rota válida e todos os três portões são obrigatórios',()=>{
 assert.equal(reachable(DATA),true);
 assert.equal(DATA.challenges.length,3);
 assert.equal(DATA.settings.requireAllGates,true);
 assert.ok(DATA.challenges.every(q=>!reachable(DATA,q)));
 assert.deepEqual(DATA.challenges.map(q=>q.id),['solar','eolica','eficiencia']);
 assert.deepEqual(DATA.challenges.map(q=>[q.row,q.col]),[[1,5],[5,5],[9,5]]);
});

test('um labirinto sem saída alcançável é rejeitado pela busca em largura',()=>{
 const broken=clone(DATA);
 broken.settings.requireAllGates=false;
 broken.grid[4]='###########';
 assert.equal(reachable(broken),false);
 assert.equal(validate(broken),false);
});

test('ausência da saída ou duas saídas invalidam a grade',()=>{
 const none=clone(DATA);
 none.grid[9]=none.grid[9].replace('E','.');
 assert.equal(validate(none),false);
 const duplicated=clone(DATA);
 duplicated.grid[1]=duplicated.grid[1].replace('S','E');
 assert.equal(validate(duplicated),false);
 const extra=clone(DATA);
 extra.grid[1]=extra.grid[1].replace('S','S'.repeat(1));
 extra.grid[5]=extra.grid[5].replace('.........','E........');
 assert.equal(validate(extra),false);
});

test('linhas não retangulares e símbolos desconhecidos são rejeitados',()=>{
 const jagged=clone(DATA);
 jagged.grid[5]='####';
 assert.equal(validate(jagged),false);
 const illegal=clone(DATA);
 illegal.grid[3]=illegal.grid[3].replace('.','G');
 assert.equal(validate(illegal),false);
 const tiny=clone(DATA);
 tiny.grid=['S.E'];
 assert.equal(validate(tiny),false);
});

test('portões sobre paredes, início ou saída não são permitidos',()=>{
 const wall=clone(DATA);
 wall.challenges[0].row=0;
 wall.challenges[0].col=0;
 assert.equal(validate(wall),false);
 const start=clone(DATA);
 start.challenges[0].row=1;
 start.challenges[0].col=1;
 assert.equal(validate(start),false);
 const exit=clone(DATA);
 exit.challenges[2].row=9;
 exit.challenges[2].col=9;
 assert.equal(validate(exit),false);
});

test('IDs de portões, posições e alternativas precisam ser consistentes',()=>{
 const dupId=clone(DATA);
 dupId.challenges[1].id=dupId.challenges[0].id;
 assert.equal(validate(dupId),false);
 const samePosition=clone(DATA);
 samePosition.challenges[1].row=samePosition.challenges[0].row;
 samePosition.challenges[1].col=samePosition.challenges[0].col;
 assert.equal(validate(samePosition),false);
 const incorrect=clone(DATA);
 incorrect.challenges[0].correctIndex=5;
 assert.equal(validate(incorrect),false);
 const duplicateChoices=clone(DATA);
 duplicateChoices.challenges[1].options[1]=duplicateChoices.challenges[1].options[0];
 assert.equal(validate(duplicateChoices),false);
 const noExplain=clone(DATA);
 noExplain.challenges[2].explanation='';
 assert.equal(validate(noExplain),false);
});

test('movimentos apenas nas quatro direções e sem diagonais',()=>{
 const state=initialState(DATA);
 assert.equal(validState(state,DATA),true);
 assert.deepEqual(Object.keys(DIRS).sort(),['down','left','right','up']);
 assert.equal(act(state,'move',{direction:'diagonal'}).changed,false);
 assert.equal(act(state,'move',{direction:'up'}).changed,false);
 assert.equal(act(state,'move',{direction:'left'}).changed,false);
 assert.equal(state.moves,0);
 assert.equal(state.row,1);
 assert.equal(state.col,1);
});

test('passo válido muda posição, registra visita e incrementa movimentos',()=>{
 const state=initialState(DATA);
 const next=act(state,'move',{direction:'right'});
 assert.equal(next.changed,true);
 assert.equal(next.state.row,1);
 assert.equal(next.state.col,2);
 assert.equal(next.state.moves,1);
 assert.deepEqual(next.state.visited,['1,1','1,2']);
 assert.equal(validState(next.state,DATA),true);
 const back=act(next.state,'move',{direction:'left'});
 assert.equal(back.state.moves,2);
 assert.equal(back.state.visited.length,2);
 assert.equal(validState(back.state,DATA),true);
});

test('portão bloqueia deslocamento e abre pergunta sem alterar posição',()=>{
 let state=initialState(DATA);
 for(let i=0;i<3;i++)state=act(state,'move',{direction:'right'}).state;
 assert.equal(state.col,4);
 const gate=act(state,'move',{direction:'right'});
 assert.equal(gate.changed,true);
 assert.equal(gate.kind,'question');
 assert.equal(gate.state.pending,'solar');
 assert.equal(gate.state.col,4);
 assert.equal(gate.state.moves,3);
 assert.equal(gate.state.unlocked.length,0);
 assert.equal(validState(gate.state,DATA),true);
 assert.equal(act(gate.state,'move',{direction:'right'}).changed,false);
});

test('resposta errada não libera passagem e informa explicação',()=>{
 let s=initialState(DATA);
 for(let i=0;i<4;i++)s=act(s,'move',{direction:'right'}).state;
 assert.equal(s.pending,'solar');
 const wrong=act(s,'answer',{index:1});
 assert.equal(wrong.changed,true);
 assert.equal(wrong.kind,'warn');
 assert.equal(wrong.state.col,4);
 assert.equal(wrong.state.pending,'solar');
 assert.equal(wrong.state.mistakes,1);
 assert.deepEqual(wrong.state.unlocked,[]);
 assert.match(wrong.message,/Células fotovoltaicas/);
 assert.equal(validState(wrong.state,DATA),true);
});

test('alternativa inexistente e resposta sem portão são recusadas',()=>{
 const initial=initialState(DATA);
 assert.equal(act(initial,'answer',{index:0}).changed,false);
 let s=initial;
 for(let i=0;i<4;i++)s=act(s,'move',{direction:'right'}).state;
 assert.equal(act(s,'answer',{index:-1}).changed,false);
 assert.equal(act(s,'answer',{index:200}).changed,false);
 assert.equal(act(s,'answer',{index:1.5}).changed,false);
 assert.equal(validState(s,DATA),true);
});

test('resposta correta desbloqueia, atravessa o portão e é persistível',()=>{
 let s=initialState(DATA);
 for(let i=0;i<4;i++)s=act(s,'move',{direction:'right'}).state;
 const right=act(s,'answer',{index:0});
 assert.equal(right.changed,true);
 assert.equal(right.kind,'good');
 assert.equal(right.state.pending,null);
 assert.equal(right.state.col,5);
 assert.equal(right.state.moves,4);
 assert.deepEqual(right.state.unlocked,['solar']);
 assert.ok(right.message.includes('Portão liberado'));
 assert.equal(validState(right.state,DATA),true);
});

test('fechar a pergunta mantém o portão bloqueado e permite tentar depois',()=>{
 let s=initialState(DATA);
 for(let i=0;i<4;i++)s=act(s,'move',{direction:'right'}).state;
 assert.equal(s.pending,'solar');
 const dismissed=act(s,'dismiss');
 assert.equal(dismissed.changed,true);
 assert.equal(dismissed.state.pending,null);
 assert.equal(dismissed.state.col,4);
 assert.deepEqual(dismissed.state.unlocked,[]);
 s=act(dismissed.state,'move',{direction:'right'}).state;
 assert.equal(s.pending,'solar');
 assert.equal(validState(s,DATA),true);
});

test('percorrer a rota completa libera os três portões e conclui o jogo',()=>{
 let state=initialState(DATA),found=0;
 for(const [row,col] of route.slice(1)){
  const dir=Object.keys(DIRS).find(k=>DIRS[k][0]===row-state.row&&DIRS[k][1]===col-state.col);
  assert.ok(dir,'a rota só pode ter passos adjacentes');
  state=act(state,'move',{direction:dir}).state;
  if(state.pending){
   const q=DATA.challenges.find(x=>x.id===state.pending);
   state=act(state,'answer',{index:q.correctIndex}).state;
   found++;
  }
  assert.equal(validState(state,DATA),true);
 }
 assert.equal(found,3);
 assert.equal(state.finished,true);
 assert.equal(state.row,9);
 assert.equal(state.col,9);
 assert.equal(state.moves,32);
 assert.equal(state.unlocked.length,3);
 assert.equal(state.visited.length,33);
 assert.equal(act(state,'move',{direction:'left'}).changed,false);
 assert.deepEqual(act(state,'restart').state,initialState(DATA));
});

test('saída não é alcançada por simples aproximação visual',()=>{
 const init=initialState(DATA);
 assert.equal(init.finished,false);
 let s=init;
 for(let i=0;i<4;i++)s=act(s,'move',{direction:'right'}).state;
 assert.equal(s.finished,false);
 assert.equal(s.pending,'solar');
 assert.equal(act(s,'move',{direction:'right'}).changed,false);
 assert.equal(s.finished,false);
});

test('estado restaurado exige posição válida, início visitado e consistência',()=>{
 const badCell=initialState(DATA);
 badCell.row=0;
 badCell.col=0;
 assert.equal(validState(badCell,DATA),false);
 const noStart=initialState(DATA);
 noStart.visited=['1,2'];
 assert.equal(validState(noStart,DATA),false);
 const future=initialState(DATA);
 future.visited.push('1,5');
 assert.equal(validState(future,DATA),false);
 const wrongCoords=initialState(DATA);
 wrongCoords.visited.push('foo');
 assert.equal(validState(wrongCoords,DATA),false);
});

test('progresso não pode forjar portão, diálogo ou vitória',()=>{
 const unlocked=initialState(DATA);
 unlocked.unlocked=['solarinexistente'];
 assert.equal(validState(unlocked,DATA),false);
 const duplicate=initialState(DATA);
 duplicate.unlocked=['solar','solar'];
 assert.equal(validState(duplicate,DATA),false);
 const distant=initialState(DATA);
 distant.pending='eficiencia';
 assert.equal(validState(distant,DATA),false);
 const fakeExit=initialState(DATA);
 fakeExit.finished=true;
 assert.equal(validState(fakeExit,DATA),false);
 const impossible=initialState(DATA);
 impossible.row=9;
 impossible.col=9;
 impossible.visited.push('9,9');
 assert.equal(validState(impossible,DATA),false);
});

test('modal nativo identifica pergunta e captura Escape sem abrir a porta',()=>{
 assert.match(html,/<dialog id="gate-dialog"/);
 assert.match(html,/aria-labelledby="dialog-heading"/);
 assert.match(js,/dialog\.showModal\(\)/);
 assert.match(js,/dialog\.close\(\)/);
 assert.match(js,/addEventListener\("cancel",event=>/);
 assert.match(js,/event\.preventDefault\(\);handleAction\(\{type:"dismiss"\}\)/);
 assert.match(js,/dialog-feedback/);
 assert.match(js,/data\.settings\.moves/);
});

test('alternativa textual lista as quatro passagens e os botões móveis',()=>{
 assert.match(html,/id="board" role="group" tabindex="0"/);
 assert.match(html,/id="neighbors"[^>]+aria-label/);
 assert.match(html,/aria-label="Controles direcionais"/);
 assert.match(html,/data-dir="up"/);
 assert.match(html,/data-dir="down"/);
 assert.match(html,/data-dir="left"/);
 assert.match(html,/data-dir="right"/);
 assert.match(js,/function describe\(dir\)/);
 assert.match(js,/function renderNeighbors\(\)/);
 assert.match(js,/Parede: não é possível avançar/);
 assert.match(js,/Portão com pergunta/);
 assert.match(js,/Saída do labirinto/);
});

test('controle por setas e WASD, foco visível e toque em celular',()=>{
 assert.match(js,/ArrowUp:"up",ArrowDown:"down",ArrowLeft:"left",ArrowRight:"right"/);
 assert.match(js,/w:"up",a:"left",s:"down",d:"right"/);
 assert.match(js,/event\.preventDefault\(\);handleAction\(\{type:"move",direction\}\)/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/@media\(max-width:520px\)/);
});

test('DOM seguro não injeta HTML e SDK é opcional e versionado',()=>{
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
});

test('JSON local e geral compartilham prompt que aguarda o assunto do professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-labirinto-educativo/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='labirinto-educativo-missao-energia-limpa';
 const one=local.examples.find(x=>x.id===id),two=global.examples.find(x=>x.id===id);
 assert.ok(one&&two);
 assert.deepEqual(one,two);
 assert.equal(one.path,file);
 assert.match(one.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(one.prompt,/Aguarde a resposta/);
 assert.match(one.prompt,/\.chatgpt\/games\/20-labirinto-educativo\.md/);
});
