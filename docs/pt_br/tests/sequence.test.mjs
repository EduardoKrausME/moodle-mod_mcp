/**
 * Testes sem dependências: node --test docs/pt_br/tests/sequence.test.mjs
 * As regras são extraídas do HTML público para evitar um motor de testes paralelo.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'game-coloque-na-ordem-correta/desenvolvimento-de-software.html'),'utf8');
const catalog=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
const script=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(script,'exemplo sem JavaScript');
const part=(start,end)=>{
  const a=script.indexOf(start),b=script.indexOf(end,a+start.length);
  assert.ok(a>=0&&b>a,'trecho ausente: '+start);
  return script.slice(a,b);
};
const data=new Function(part('const data={','const $=id=>')+'return data;')();
const rules=new Function(
  part('function validate(config){','function closestReference(order,config){')+
  'return {validate,isPermutation,accepted,reorder,shuffleIds};'
)();
const ids=data.steps.map(step=>step.id);

test('apenas conteúdo pedagógico, sem menus, imagens ou dependências externas',()=>{
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.doesNotMatch(html,/<(?:nav|header|footer|img|picture|svg)\b/i);
  assert.doesNotMatch(html,/<script[^>]*src=/i);
  assert.doesNotThrow(()=>new Script(script));
  for(const id of ['steps','check','hint','undo','restart','progress','status']){
    assert.ok(html.includes('id="'+id+'"'),'controle ausente: '+id);
  }
});

test('JSON possui IDs únicos e sequência canônica e alternativa aceitas',()=>{
  assert.equal(data.type,'sequence');
  assert.equal(rules.validate(data),true);
  assert.equal(ids.length,7);
  assert.equal(rules.accepted(ids,data),true);
  assert.equal(data.acceptedOrders.length,1);
  assert.equal(rules.accepted(data.acceptedOrders[0],data),true);
  assert.equal(rules.accepted([ids[1],ids[0],...ids.slice(2)],data),false);
});

test('embaralhamento produz permutações válidas e não resolvidas',()=>{
  for(let i=0;i<200;i++){
    const shuffled=rules.shuffleIds(data);
    assert.equal(rules.isPermutation(shuffled,ids),true);
    assert.equal(rules.accepted(shuffled,data),false);
  }
});

test('reordenar antes e depois preserva cada etapa, inclusive extremos e inversões',()=>{
  const cases=[
    {from:ids[0],to:ids[2],after:false,order:[ids[1],ids[0],ids[2],...ids.slice(3)]},
    {from:ids[0],to:ids[2],after:true,order:[ids[1],ids[2],ids[0],...ids.slice(3)]},
    {from:ids[6],to:ids[0],after:false,order:[ids[6],...ids.slice(0,6)]},
    {from:ids[1],to:ids[2],after:true,order:[ids[0],ids[2],ids[1],...ids.slice(3)]}
  ];
  for(const item of cases){
    const moved=rules.reorder(ids,item.from,item.to,item.after);
    assert.deepEqual(moved,item.order);
    assert.equal(rules.isPermutation(moved,ids),true);
  }
  assert.deepEqual(rules.reorder(ids,ids[1],ids[1]),ids);
  assert.deepEqual(rules.reorder(ids,'inexistente',ids[2]),ids);
});

test('configurações inválidas são rejeitadas sem construir HTML inseguro',()=>{
  const copy=()=>JSON.parse(JSON.stringify(data));
  let broken=copy();broken.steps[1].id=broken.steps[0].id;
  assert.equal(rules.validate(broken),false);
  broken=copy();broken.acceptedOrders=[ids.slice(0,-1).concat(ids[0])];
  assert.equal(rules.validate(broken),false);
  broken=copy();broken.steps[0].label='';
  assert.equal(rules.validate(broken),false);
});

test('um único passo e todas as ordens alternativas válidas não criam laço infinito',()=>{
  const single={type:'sequence',settings:{allowRetry:true},steps:[{id:'a',label:'Etapa longa '+('texto '.repeat(100)),description:'Detalhes'}],acceptedOrders:[]};
  assert.equal(rules.validate(single),true);
  assert.deepEqual(rules.shuffleIds(single),['a']);
  const all={type:'sequence',settings:{allowRetry:true},steps:[
    {id:'a',label:'A',description:'A'}, {id:'b',label:'B',description:'B'}
  ],acceptedOrders:[['b','a']]};
  assert.equal(rules.validate(all),true);
  assert.equal(rules.isPermutation(rules.shuffleIds(all),['a','b']),true);
});

test('arraste por Pointer Events, controles com rótulos e progresso opcional',()=>{
  assert.match(script,/function startDrag\(/);
  assert.match(script,/function moveDrag\(/);
  assert.match(script,/function endDrag\(/);
  assert.match(script,/pointercancel/);
  assert.match(script,/aria-label/);
  assert.match(script,/function getState\(/);
  assert.match(script,/function restoreState\(/);
  assert.match(script,/function destroy\(/);
  assert.match(script,/sdk\.save\(/);
});

test('catálogo registra o novo exemplo e preserva a galeria',()=>{
  const entry=catalog.examples.find(example=>example.id==='coloque-na-ordem-correta');
  assert.ok(entry);
  assert.equal(entry.path,'game-coloque-na-ordem-correta/desenvolvimento-de-software.html');
  assert.ok(entry.description&&entry.howItWorks&&entry.subtype);
  assert.equal(catalog.examples.filter(example=>example.id===entry.id).length,1);
});
