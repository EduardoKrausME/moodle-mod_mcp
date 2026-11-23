/**
 * Exemplo 7/10: Da Flor à Fruta.
 * node --test docs/pt_br/tests/flower-to-fruit.test.mjs
 * Funciona sem rede, navegador, Moodle ou dependências de terceiros.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/da-flor-a-fruta.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scripts[0]?.[1]??'';
const model=JSON.parse(js.match(/const model=(\{[^\n]+\});/)?.[1]??'null');
const {stages,types,anatomy,quiz}=model;

test('HTML autossuficiente com foco visível, acessibilidade e responsividade',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scripts.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|localStorage|fetch\(/i);
 assert.match(html,/@media\(max-width:560px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/role="progressbar"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/aria-labelledby="stage-art-title stage-art-desc"/);
 assert.match(html,/aria-labelledby="anatomy-title anatomy-desc"/);
 assert.match(html,/aria-labelledby="type-art-title type-art-desc"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'da-flor-a-fruta.html'}));
});

test('12 etapas únicas, em sequência e fundamentadas sem dias inventados',()=>{
 assert.equal(stages.length,12);
 assert.equal(new Set(stages.map(s=>s.id)).size,12);
 assert.deepEqual(stages.map(s=>s.n),Array.from({length:12},(_,i)=>String(i+1).padStart(2,'0')));
 assert.deepEqual([...new Set(stages.map(s=>s.group))],['flor','polinizacao','fecundacao','fruto']);
 for(const stage of stages){
  for(const field of ['title','subtitle','description','importance','caution','source','url','motif'])assert.ok(stage[field],stage.id+': '+field);
  assert.ok(html.includes('id="fd-'+stage.motif+'"'),'Ilustração ausente: '+stage.motif);
  assert.match(stage.url,/^https:\/\//);
 }
 assert.match(stages[4].caution,/não é fecundação/);
 assert.match(stages[7].caution,/partenocárpicos/);
 assert.match(html,/não um calendário/i);
});

test('18 ilustrações SVG originais, seis frutos e cinco estruturas',()=>{
 assert.equal((html.match(/<symbol id="fd-/g)||[]).length,18);
 assert.equal(types.length,6);
 assert.equal(anatomy.length,5);
 assert.equal(quiz.length,4);
 assert.deepEqual(types.map(x=>x.id),['tomate','pessego','morango','abacaxi','maca','feijao']);
 assert.deepEqual(anatomy.map(x=>x.id),['estigma','estilete','antera','ovario','ovulo']);
 for(const type of types){
  assert.ok(html.includes('id="fd-'+type.motif+'"'),type.motif);
  assert.match(type.url,/^https:\/\//);
  assert.ok(type.origin&&type.description&&type.caution);
 }
 assert.match(types[2].explanation??types[2].description,/aquênio/);
 assert.match(types[3].origin,/Muitas flores/);
});

test('catálogo preserva os seis exemplos anteriores e pergunta antes de gerar',()=>{
 assert.deepEqual(manifest.examples.slice(0,7).map(x=>x.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
  'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo',
  'do-cacau-ao-chocolate','da-flor-a-fruta'
 ]);
 assert.equal(new Set(manifest.examples.map(x=>x.path)).size,manifest.examples.length);
 const entry=manifest.examples.find(e=>e.id==='da-flor-a-fruta');
 assert.equal(entry.path,'timeline/da-flor-a-fruta.html');
 assert.ok(entry.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(entry.prompt.includes('Aguarde a resposta do professor'));
 assert.ok(entry.features.length>=6);
});

class FakeElement{
 constructor(tag='div'){
  this.tag=tag;this.children=[];this.events={};this.attrs={};this.className='';
  this.classes=new Set();
  this.classList={toggle:(k,on)=>on?this.classes.add(k):this.classes.delete(k)};
  this.style={};this.clientWidth=600;this.clientHeight=500;this.offsetTop=0;this.offsetLeft=0;
  this.disabled=false;
 }
 append(...v){this.children.push(...v);}
 replaceChildren(...v){this.children=v;}
 setAttribute(k,v){this.attrs[k]=String(v);}
 addEventListener(k,fn){this.events[k]=fn;}
 querySelectorAll(sel){return this.children.filter(x=>x.className.split(' ').includes(sel.slice(1)));}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 focus(){this.focused=true;}
 scrollTo(){}
}
function mount(){
 const ids=['stage-filters','stages','stage-number','stage-group','stage-title',
 'stage-subtitle','stage-description','stage-importance','stage-caution','stage-source',
 'stage-art-title','stage-art-desc','progress-label','progress','progress-fill',
 'stage-art-use','previous','next','announcement','anatomy-buttons','anatomy-info',
 'type-buttons','type-title','type-kind','type-origin','type-description','type-caution',
 'type-art-title','type-art-desc','type-art-use','type-source','questions',
 'reset-quiz','reset-all'];
 const nodes=new Map(ids.map(id=>[id,new FakeElement()]));
 const document={
  getElementById:id=>{assert.ok(nodes.has(id),'Missing DOM node '+id);return nodes.get(id);},
  createElement:tag=>new FakeElement(tag)
 };
 new Function('document','window',js)(document,{matchMedia:q=>({matches:q.includes('reduced-motion')})});
 return id=>nodes.get(id);
}

test('timeline: filtros, botões, setas, Home/End, visita e reinício',()=>{
 const get=mount();
 assert.equal(get('stages').children.length,12);
 assert.equal(get('stage-title').textContent,'Botão floral');
 assert.equal(get('progress').attrs['aria-valuenow'],'1');
 assert.equal(get('previous').disabled,true);
 get('next').click();
 assert.equal(get('stage-title').textContent,'A flor se abre');
 get('stage-filters').children[2].click();
 assert.equal(get('stages').children.length,3);
 assert.equal(get('stage-title').textContent,'O pólen é transportado');
 get('stages').children[0].key('End');
 assert.equal(get('stage-title').textContent,'Tubo polínico');
 get('stages').children[2].key('Home');
 assert.equal(get('stage-title').textContent,'O pólen é transportado');
 get('reset-all').click();
 assert.equal(get('stages').children.length,12);
 assert.equal(get('progress').attrs['aria-valuenow'],'1');
 assert.equal(get('stage-title').textContent,'Botão floral');
});

test('anatomia, comparação e SVG mudam de forma segura com clique',()=>{
 const get=mount();
 assert.equal(get('anatomy-buttons').children.length,5);
 get('anatomy-buttons').children[0].click();
 assert.equal(get('anatomy-info').children[0].textContent,'Estigma');
 assert.equal(get('anatomy-buttons').children[0].attrs['aria-pressed'],'true');
 assert.equal(get('type-buttons').children.length,6);
 get('type-buttons').children[2].click();
 assert.equal(get('type-title').textContent,'Morango');
 assert.equal(get('type-art-use').attrs.href,'#fd-strawberry');
 assert.equal(get('type-buttons').children[2].attrs['aria-pressed'],'true');
 get('type-buttons').children[3].click();
 assert.equal(get('type-title').textContent,'Abacaxi');
 assert.match(get('type-caution').textContent,/várias flores/);
 get('reset-all').click();
 assert.equal(get('type-title').textContent,'Tomate');
});

test('quatro questões oferecem feedback correto/errado e reinício',()=>{
 const get=mount();
 assert.equal(get('questions').children.length,4);
 get('questions').children[0].children[1].children[0].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 get('questions').children[1].children[1].children[0].click();
 assert.match(get('questions').children[1].children[2].textContent,/^Reveja/);
 get('reset-quiz').click();
 assert.equal(get('questions').children[0].children[2].textContent,'Selecione uma alternativa.');
 get('questions').children[2].children[1].children[2].click();
 get('reset-all').click();
 assert.equal(get('questions').children[2].children[2].textContent,'Selecione uma alternativa.');
});
