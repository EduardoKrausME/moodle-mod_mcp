/**
 * Regressões do jogo 09 - Classifique os itens.
 * Execute: node --test docs/pt_br/tests/categorize.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const path='game-classifique-os-itens/habitos-sustentaveis.html';
const html=await readFile(resolve(root,path),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'HTML precisa conter um script próprio');
const start=js.indexOf('const CONFIG=');
const functionsStart=js.indexOf('function validConfig(data)');
const functionsEnd=js.indexOf('// End pure rules.');
assert.ok(start>=0&&functionsStart>start&&functionsEnd>functionsStart,'regras testáveis ausentes');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(functionsStart,functionsEnd);
const {CONFIG,validConfig,initialState,cloneAssignments,sameSet,correctItem,evaluate,validState,toggle,inspect}=
 new Function(source+'return {CONFIG,validConfig,initialState,cloneAssignments,sameSet,correctItem,evaluate,validState,toggle,inspect};')();

test('HTML acessível, isolado e sem imagens ou estrutura institucional',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.match(html,/id="items"/);
 assert.match(html,/id="categories"/);
 assert.match(html,/id="undo"/);
 assert.match(html,/id="check"/);
 assert.match(html,/id="reset"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.doesNotThrow(()=>new Script(js));
});

test('configuração possui três categorias, nove atitudes e dois itens multiclasse',()=>{
 assert.equal(validConfig(CONFIG),true);
 assert.equal(CONFIG.type,'categorize');
 assert.equal(CONFIG.categories.length,3);
 assert.equal(CONFIG.items.length,9);
 assert.equal(CONFIG.settings.allowMultiCategory,true);
 assert.equal(CONFIG.items.filter(item=>item.categoryIds.length===2).length,2);
 assert.deepEqual([...new Set(CONFIG.items.map(item=>item.id))].length,9);
});

test('IDs repetidos, categoria vazia, destino desconhecido e multiclasse indevida são rejeitados',()=>{
 const duplicateItem=structuredClone(CONFIG);
 duplicateItem.items[1].id=duplicateItem.items[0].id;
 assert.equal(validConfig(duplicateItem),false);
 const duplicateCat=structuredClone(CONFIG);
 duplicateCat.categories[1].id=duplicateCat.categories[0].id;
 assert.equal(validConfig(duplicateCat),false);
 const unknown=structuredClone(CONFIG);
 unknown.items[0].categoryIds=['desconhecida'];
 assert.equal(validConfig(unknown),false);
 const empty=structuredClone(CONFIG);
 empty.items=empty.items.filter(item=>!item.categoryIds.includes('residuos'));
 assert.equal(validConfig(empty),false);
 const single=structuredClone(CONFIG);
 single.settings.allowMultiCategory=false;
 assert.equal(validConfig(single),false);
});

test('associações multi-categoria não dependem da ordem e não duplicam o destino',()=>{
 let state=initialState();
 assert.equal(validState(state,CONFIG),true);
 state=toggle(state,CONFIG,'banho','energia').state;
 state=toggle(state,CONFIG,'banho','agua').state;
 assert.equal(sameSet(state.assignments.banho,['agua','energia']),true);
 assert.equal(correctItem(CONFIG.items.find(item=>item.id==='banho'),state.assignments),true);
 const old=JSON.stringify(state);
 state=toggle(state,CONFIG,'banho','agua').state;
 assert.equal(sameSet(state.assignments.banho,['energia']),true,'toggle deve remover destino repetido');
 assert.equal(JSON.stringify(toggle(initialState(),CONFIG,'falso','agua').state),JSON.stringify(initialState()));
 assert.notEqual(JSON.stringify(state),old);
});

test('sem multiclasse, selecionar novo destino substitui o anterior',()=>{
 const single=structuredClone(CONFIG);
 single.settings.allowMultiCategory=false;
 single.items.forEach(item=>{item.categoryIds=[item.categoryIds[0]];});
 assert.equal(validConfig(single),true);
 let state=initialState();
 state=toggle(state,single,'torneira','agua').state;
 state=toggle(state,single,'torneira','energia').state;
 assert.deepEqual(state.assignments.torneira,['energia']);
 state=toggle(state,single,'torneira','energia').state;
 assert.deepEqual(state.assignments,{});
});

test('desfazer restaura a alocação sem alterar o estado anterior',()=>{
 let state=initialState();
 const original=cloneAssignments(state.assignments);
 state=toggle(state,CONFIG,'luz','energia').state;
 const last=cloneAssignments(state.assignments);
 state=toggle(state,CONFIG,'luz','agua').state;
 assert.deepEqual(last.luz,['energia']);
 assert.deepEqual(original,{});
 assert.deepEqual(state.assignments.luz,['energia','agua']);
 const restored={...state,assignments:last,checked:false,completed:false};
 assert.deepEqual(restored.assignments.luz,['energia']);
 assert.equal(evaluate(CONFIG,restored.assignments).correct,1);
});

test('verificação exata de conjuntos identifica erros e só conclui quando tudo está correto',()=>{
 let state=initialState();
 let res=inspect(state,CONFIG);
 assert.equal(res.result.pending,9);
 assert.equal(res.result.correct,0);
 assert.equal(res.state.completed,false);
 for(const item of CONFIG.items)for(const cat of item.categoryIds)state=toggle(state,CONFIG,item.id,cat).state;
 const stats=evaluate(CONFIG,state.assignments);
 assert.equal(stats.correct,9);
 assert.equal(stats.pending,0);
 res=inspect(state,CONFIG);
 assert.equal(res.state.completed,true);
 assert.equal(res.state.checked,true);
 assert.equal(res.state.errors,0);
 assert.equal(validState(res.state,CONFIG),true);
 assert.equal(toggle(res.state,CONFIG,'luz','agua').changed,false,'jogo concluído deve bloquear edições');
});

test('estado adulterado ou com categoria desconhecida não é recuperável',()=>{
 let state=initialState();
 state.assignments={naoexiste:['agua']};
 assert.equal(validState(state,CONFIG),false);
 state=initialState();
 state.assignments={luz:['desconhecida']};
 assert.equal(validState(state,CONFIG),false);
 state=initialState();
 state.assignments={luz:['energia','energia']};
 assert.equal(validState(state,CONFIG),false);
 state=initialState();
 state.completed=true;state.checked=true;
 assert.equal(validState(state,CONFIG),false);
});

test('interfaces de toque e teclado, feedback por categoria e SDK opcional estão no código',()=>{
 assert.match(js,/data\.settings\.allowMultiCategory/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function renderReview\(/);
 assert.match(js,/associações corretas/);
 assert.match(js,/replacement\.focus\(/);
 assert.match(js,/setAttribute\("aria-pressed"/);
 assert.match(js,/dataTransfer\.setData/);
 assert.match(js,/addEventListener\("drop"/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.match(html,/@media\(max-width:800px\)/);
});

test('JSON local e índice geral têm o mesmo registro e prompt que pergunta primeiro ao professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-classifique-os-itens/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='classifique-os-itens-habitos-sustentaveis';
 const a=local.examples.find(item=>item.id===id),b=global.examples.find(item=>item.id===id);
 assert.ok(a&&b,'novo jogo deve aparecer nos dois catálogos');
 assert.deepEqual(a,b);
 assert.equal(a.path,path);
 assert.ok(a.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(a.prompt.includes('Aguarde a resposta'));
 assert.ok(a.prompt.includes('.chatgpt/games/09-classifique-os-itens.md'));
});
