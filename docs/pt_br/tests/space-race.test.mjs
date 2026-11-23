/**
 * Regressões do exemplo 2 de 10: Corrida Espacial.
 * Execute: node --test docs/pt_br/tests/space-race.test.mjs
 * Não exige dependências, navegador nem Moodle.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/corrida-espacial.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const previous=await readFile(resolve(root,'timeline/historia-do-mundo.html'),'utf8');
const inline=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const javascript=inline[0]?.[1]??'';
const data=javascript.match(/const missions = (\[[^\n]+\]);/);
const missions=JSON.parse(data?.[1]??'null');

test('HTML autossuficiente e responsivo preserva o exemplo anterior',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.equal(inline.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(/i);
 assert.match(html,/@media\(max-width:500px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/aria-labelledby="craft-title craft-desc"/);
 assert.doesNotThrow(()=>new Script(javascript,{filename:'corrida-espacial.html'}));
 assert.match(previous,/12 viradas que conectam o mundo/);
});

test('14 marcos históricos em ordem, com metadados e um SVG por missão',()=>{
 assert.equal(missions.length,14);
 const ids=missions.map(m=>m.id);
 assert.equal(new Set(ids).size,14);
 assert.deepEqual(missions.map(m=>m.year),[...missions.map(m=>m.year)].sort((a,b)=>a-b));
 assert.equal(missions.at(0).year,1957);
 assert.equal(missions.at(-1).year,2023);
 for(const mission of missions){
  for(const field of ['id','year','date','name','group','origin','kind','title','summary','impact','caveat','color'])assert.ok(mission[field],mission.id+': '+field);
  assert.match(mission.id,/^[a-z0-9-]+$/);
  assert.ok(html.includes('id="ship-'+mission.id+'"'),mission.id);
 }
 assert.deepEqual([...new Set(missions.map(m=>m.group))].sort(),['observatorios','orbita','pioneiros','sondas']);
});

test('manifesto preserva os dois exemplos originais e admite novos registros',()=>{
 assert.deepEqual(manifest.examples.slice(0,2).map(e=>e.id),['historia-do-mundo','corrida-espacial']);
 assert.equal(new Set(manifest.examples.map(e=>e.path)).size,manifest.examples.length);
 const entry=manifest.examples[1];
 assert.equal(entry.path,'timeline/corrida-espacial.html');
 assert.ok(entry.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(entry.prompt.includes('Aguarde a resposta'));
 assert.equal(entry.features.length>=4,true);
});

class FakeElement{
 constructor(){
  this.children=[];
  this.events={};
  this.attrs={};
  this.className='';
  this.classList={toggle(){}};
  this.style={};
  this.clientWidth=600;
  this.offsetLeft=0;
  this.disabled=false;
 }
 setAttribute(name,value){this.attrs[name]=String(value);}
 addEventListener(name,callback){this.events[name]=callback;}
 replaceChildren(...children){this.children=children;}
 append(...children){this.children.push(...children);}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 querySelectorAll(selector){return this.children.filter(c=>c.className.split(' ').includes(selector.slice(1)));}
 scrollTo(){}
 focus(){this.focused=true;}
}
function mounted(){
 const ids=['visual-kind','date','year','number','mission-title','subtitle','summary','impact','caveat','origin','counter','craft-title','craft-desc','craft-use','prev','next','progress-text','progress-bar','progress-fill','live','filters','track','reset','questions'];
 const nodes=new Map(ids.map(id=>[id,new FakeElement()]));
 const document={
  documentElement:{style:{setProperty(){}}},
  getElementById:id=>{
   assert.ok(nodes.has(id),'elemento ausente: '+id);
   return nodes.get(id);
  },
  createElement:()=>new FakeElement()
 };
 new Function('document','window',javascript)(document,{matchMedia:()=>({matches:true})});
 return id=>nodes.get(id);
}
test('filtros, teclado, navegação, feedback e reinício funcionam',()=>{
 const get=mounted();
 assert.equal(get('track').children.length,14);
 assert.equal(get('year').textContent,'1957');
 assert.equal(get('prev').disabled,true);
 get('next').click();
 assert.equal(get('year').textContent,'1961');
 get('filters').children[3].click();
 assert.equal(get('track').children.length,5);
 assert.equal(get('year').textContent,'1977');
 get('track').children[2].key('End');
 assert.equal(get('year').textContent,'2023');
 get('track').children[2].key('Home');
 assert.equal(get('year').textContent,'1977');
 get('questions').children[0].children[1].children[1].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 get('reset').click();
 assert.equal(get('track').children.length,14);
 assert.equal(get('year').textContent,'1957');
 assert.equal(get('progress-bar').attrs['aria-valuenow'],'1');
 assert.equal(get('questions').children[0].children[2].textContent,'Selecione uma alternativa.');
});
