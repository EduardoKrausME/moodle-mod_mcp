/**
 * Regressões para o jogo 10 - Identifique na imagem.
 * Execute: node --test docs/pt_br/tests/image-hotspots.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const path='game-identifique-na-imagem/componentes-da-placa-mae.html';
const html=await readFile(resolve(root,path),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'a atividade deve conter JavaScript incorporado');
const configStart=js.indexOf('const DATA='),configEnd=js.indexOf('const root=',configStart);
const helpersStart=js.indexOf('function validate(data)'),helpersEnd=js.indexOf('// End pure logic.',helpersStart);
assert.ok(configStart>=0&&configEnd>configStart&&helpersStart>configEnd&&helpersEnd>helpersStart);
const code=js.slice(configStart,configEnd)+js.slice(helpersStart,helpersEnd);
const {DATA,LABEL_ORDER,TARGET_ORDER,validate,initialState,countSolved,nextIndex,target,validState,guess}=
 new Function(code+'return {DATA,LABEL_ORDER,TARGET_ORDER,validate,initialState,countSolved,nextIndex,target,validState,guess};')();

test('HTML completo sem navegação institucional ou arquivos remotos',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.match(html,/<svg id="board-svg"[\s\S]+<\/svg>/);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotThrow(()=>new Script(js));
});

test('imagem original e seis hotspots com coordenadas válidas e sem sobreposição',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'image_hotspots');
 assert.equal(DATA.image.src,'#board-svg');
 assert.equal(DATA.hotspots.length,6);
 assert.deepEqual([...new Set(DATA.hotspots.map(p=>p.number))],[1,2,3,4,5,6]);
 for(const p of DATA.hotspots){
  assert.ok(p.x>0&&p.x<1&&p.y>0&&p.y<1);
  assert.ok(p.region&&p.explanation&&p.label);
 }
 const overlap=structuredClone(DATA);
 overlap.hotspots[1].x=overlap.hotspots[0].x;
 overlap.hotspots[1].y=overlap.hotspots[0].y;
 assert.equal(validate(overlap),false);
 const outside=structuredClone(DATA);
 outside.hotspots[2].x=1.3;
 assert.equal(validate(outside),false);
});

test('IDs, rótulos e números duplicados são rejeitados',()=>{
 const duplicateId=structuredClone(DATA);
 duplicateId.hotspots[1].id=duplicateId.hotspots[0].id;
 assert.equal(validate(duplicateId),false);
 const duplicateNumber=structuredClone(DATA);
 duplicateNumber.hotspots[1].number=duplicateNumber.hotspots[0].number;
 assert.equal(validate(duplicateNumber),false);
 const duplicateLabel=structuredClone(DATA);
 duplicateLabel.hotspots[1].label=duplicateLabel.hotspots[0].label;
 assert.equal(validate(duplicateLabel),false);
});

test('lista de rótulos e sequência de perguntas contêm seis IDs distintos',()=>{
 assert.equal(new Set(LABEL_ORDER).size,6);
 assert.equal(new Set(TARGET_ORDER).size,6);
 const ids=new Set(DATA.hotspots.map(p=>p.id));
 for(const id of LABEL_ORDER.concat(TARGET_ORDER))assert.ok(ids.has(id));
 assert.notDeepEqual(LABEL_ORDER,DATA.hotspots.map(p=>p.id),'a posição do rótulo não deve dar a resposta');
});

test('acerto e erro são verificados por IDs, sem aproximação de texto',()=>{
 const start=initialState();
 assert.equal(validState(start,DATA),true);
 const miss=guess(start,'cpu','ram',DATA);
 assert.equal(miss.changed,true);
 assert.equal(miss.correct,false);
 assert.equal(miss.state.errors,1);
 assert.equal(miss.state.attempts,1);
 assert.equal(countSolved(miss.state),0);
 assert.equal(validState(miss.state,DATA),true);
 const right=guess(miss.state,'cpu','cpu',DATA);
 assert.equal(right.changed,true);
 assert.equal(right.correct,true);
 assert.deepEqual(right.state.assignments,{cpu:'cpu'});
 assert.equal(right.state.errors,1);
 assert.equal(validState(right.state,DATA),true);
 assert.equal(guess(right.state,'cpu','cpu',DATA).changed,false,'o ponto encontrado não pode contar novamente');
 assert.equal(guess(start,'nada','cpu',DATA).changed,false,'ID desconhecido deve ser ignorado');
});

test('o modo de perguntas avança para o próximo componente não resolvido',()=>{
 let state=initialState();
 assert.equal(target(state),TARGET_ORDER[0]);
 state=guess(state,TARGET_ORDER[0],TARGET_ORDER[0],DATA).state;
 assert.equal(target(state),TARGET_ORDER[1]);
 assert.equal(nextIndex(state),1);
 for(const id of TARGET_ORDER.slice(1))state=guess(state,id,id,DATA).state;
 assert.equal(state.completed,true);
 assert.equal(state.targetIndex,6);
 assert.equal(target(state),null);
 assert.equal(countSolved(state),6);
 assert.equal(validState(state,DATA),true);
 assert.equal(guess(state,'cpu','ram',DATA).changed,false);
});

test('estado malformado ou adulterado não é restaurável',()=>{
 const unknown=initialState();
 unknown.assignments={intruso:'cpu'};
 assert.equal(validState(unknown,DATA),false);
 const swapped=initialState();
 swapped.assignments={cpu:'ram'};
 swapped.attempts=1;
 swapped.targetIndex=1;
 assert.equal(validState(swapped,DATA),false);
 const corrupt=initialState();
 corrupt.completed=true;
 assert.equal(validState(corrupt,DATA),false);
 const invalidCounter=initialState();
 invalidCounter.errors=2;invalidCounter.attempts=1;
 assert.equal(validState(invalidCounter,DATA),false);
});

test('responsividade, zoom e listagem textual acessível estão presentes',()=>{
 assert.match(html,/id="viewport"[^>]*tabindex="0"/);
 assert.match(html,/id="points"[^>]*aria-label=/);
 assert.match(html,/id="mode-label"/);
 assert.match(html,/id="mode-locate"/);
 assert.match(html,/id="zoom-in"/);
 assert.match(html,/id="zoom-out"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/@media\(max-width:820px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(js,/pin\.style\.left=spot\.x\*100\+"%"/);
 assert.match(js,/pin\.style\.top=spot\.y\*100\+"%"/);
 assert.match(js,/stage\.style\.width=zoom\+"%"/);
 assert.match(js,/event\.key==="Escape"/);
 assert.match(js,/ArrowDown/);
});

test('persistência Moodle é opcional e não atribui conclusão acadêmica',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.doesNotMatch(js,/completion\.complete\(/);
});

test('JSON local e geral incluem o mesmo exemplo com prompt que aguarda o professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-identifique-na-imagem/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='identifique-na-imagem-placa-mae';
 const a=local.examples.find(e=>e.id===id),b=general.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,path);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/10-identifique-na-imagem\.md/);
});
