/**
 * Exemplo 6/10 - Do Cacau ao Chocolate.
 * node --test docs/pt_br/tests/cocoa-chocolate.test.mjs
 * Testes autocontidos: sem navegador, rede, SDK ou Moodle.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/do-cacau-ao-chocolate.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scripts[0]?.[1]??'';
const records=JSON.parse(js.match(/const records=(\[[^\n]+\]);/)?.[1]??'null');
const phases=JSON.parse(js.match(/const phases=(\[[^\n]+\]);/)?.[1]??'null');

test('HTML completo, autossuficiente, acessível e responsivo',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scripts.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(|localStorage/i);
 assert.match(html,/@media\(max-width:650px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/aria-labelledby="history-art-title history-art-desc"/);
 assert.match(html,/aria-labelledby="process-art-title process-art-desc"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/role="progressbar"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'do-cacau-ao-chocolate.html'}));
});

test('14 fatos históricos datados, fontes e ressalvas, sem invenções isoladas',()=>{
 assert.equal(records.length,14);
 assert.equal(new Set(records.map(r=>r.id)).size,14);
 assert.deepEqual(records.map(r=>r.year),[...records.map(r=>r.year)].sort((a,b)=>a-b));
 assert.equal(records[0].id,'equador');
 assert.equal(records.at(-1).id,'desafios');
 for(const r of records){
  for(const key of ['id','date','short','place','title','description','impact','caveat','sourceLabel','sourceUrl','motif','category'])assert.ok(r[key],r.id+': '+key);
  assert.match(r.sourceUrl,/^https:\/\//);
  assert.ok(html.includes('id="cacao-'+r.motif+'"'),r.motif);
 }
 assert.deepEqual([...new Set(records.map(r=>r.category))].sort(),['comercio','culturas','industria','origens']);
});

test('9 etapas técnicas, 23 SVGs autorais, fermentação e cuidado didático',()=>{
 assert.equal(phases.length,9);
 assert.equal(new Set(phases.map(p=>p.id)).size,9);
 assert.equal((html.match(/<symbol id="cacao-/g)||[]).length,23);
 assert.deepEqual(phases.map(p=>p.id),['colheita','abertura','fermentacao','secagem','torra','descasque','moagem','formulacao','temperagem']);
 for(const p of phases){
  for(const key of ['n','name','subtitle','description','why','caveat','motif'])assert.ok(p[key],p.id+': '+key);
  assert.ok(html.includes('id="cacao-'+p.motif+'"'),p.id);
 }
 assert.match(phases[2].description,/leveduras, bactérias/);
 assert.match(phases[4].caveat,/tempo\/temperatura/);
});

test('manifesto acrescenta só o sexto registro e prompt pergunta antes de criar',()=>{
 assert.deepEqual(manifest.examples.slice(0,6).map(e=>e.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
  'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo','do-cacau-ao-chocolate'
 ]);
 assert.equal(new Set(manifest.examples.map(e=>e.path)).size,manifest.examples.length);
 const e=manifest.examples.find(e=>e.id==='do-cacau-ao-chocolate');
 assert.equal(e.path,'timeline/do-cacau-ao-chocolate.html');
 assert.ok(e.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(e.prompt.includes('Aguarde a resposta do professor'));
 assert.ok(e.features.length>=5);
});
class Element{
 constructor(tag='div'){
  this.tag=tag;this.className='';this.children=[];this.events={};this.attrs={};this.classes=new Set();
  this.classList={toggle:(name,on)=>on?this.classes.add(name):this.classes.delete(name)};
  this.style={};this.clientWidth=650;this.clientHeight=410;this.offsetLeft=0;this.offsetTop=0;this.disabled=false;this.hidden=false;
 }
 replaceChildren(...c){this.children=c;}
 append(...c){this.children.push(...c);}
 setAttribute(k,v){this.attrs[k]=String(v);}
 addEventListener(k,fn){this.events[k]=fn;}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 querySelectorAll(sel){return this.children.filter(c=>c.className.split(' ').includes(sel.slice(1)));}
 focus(){this.focused=true;}
 scrollTo(){}
}
function mounted(){
 const ids=['mode-history','mode-process','history-view','process-view','announcement','filters','timeline','folio','place','history-title','history-date','history-description','history-impact','history-caveat','history-source','history-art-title','history-art-desc','progress-label','history-use','history-prev','history-next','history-progress','progress-fill','process-steps','process-index','process-title','process-subtitle','process-description','process-why','process-caveat','process-art-title','process-art-desc','process-use','process-prev','process-next','questions','reset-history','reset-process','reset-questions'];
 const dom=new Map(ids.map(id=>[id,new Element()]));
 const document={
  getElementById:id=>{assert.ok(dom.has(id),'Missing id '+id);return dom.get(id);},
  createElement:tag=>new Element(tag)
 };
 new Function('document','window',js)(document,{matchMedia:q=>({matches:q.includes('reduced-motion')})});
 return id=>dom.get(id);
}
test('história: filtros, botões, teclas, progresso e reinício',()=>{
 const get=mounted();
 assert.equal(get('timeline').children.length,14);
 assert.match(get('history-title').textContent,/semente já tinha/);
 assert.equal(get('history-prev').disabled,true);
 get('history-next').click();
 assert.match(get('history-title').textContent,/Bebidas e tradições/);
 get('filters').children[3].click();
 assert.equal(get('timeline').children.length,4);
 assert.match(get('history-title').textContent,/Separar componentes/);
 get('timeline').children[1].key('End');
 assert.match(get('history-title').textContent,/Refinar aroma/);
 get('timeline').children[3].key('Home');
 assert.match(get('history-title').textContent,/Separar componentes/);
 get('reset-history').click();
 assert.equal(get('timeline').children.length,14);
 assert.equal(get('history-progress').attrs['aria-valuenow'],'1');
 assert.match(get('history-title').textContent,/semente já tinha/);
});

test('processamento: troca de modo, nove etapas, teclado e reinício',()=>{
 const get=mounted();
 assert.equal(get('process-steps').children.length,9);
 assert.equal(get('mode-history').attrs['aria-pressed'],'true');
 assert.equal(get('process-view').hidden,true);
 get('mode-process').click();
 assert.equal(get('mode-process').attrs['aria-pressed'],'true');
 assert.equal(get('history-view').hidden,true);
 assert.equal(get('process-view').hidden,false);
 get('process-next').click();
 assert.equal(get('process-title').textContent,'Extração');
 get('process-steps').children[0].key('End');
 assert.equal(get('process-title').textContent,'Temperagem e moldagem');
 get('process-steps').children[8].key('Home');
 assert.equal(get('process-title').textContent,'Colheita');
 get('process-next').click();
 get('reset-process').click();
 assert.equal(get('process-title').textContent,'Colheita');
 get('mode-history').click();
 assert.equal(get('history-view').hidden,false);
});

test('questões formativas: feedback certo e errado, sem persistência',()=>{
 const get=mounted();
 assert.equal(get('questions').children.length,3);
 get('questions').children[0].children[1].children[0].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 get('questions').children[1].children[1].children[0].click();
 assert.match(get('questions').children[1].children[2].textContent,/^Reveja/);
 get('reset-questions').click();
 assert.equal(get('questions').children[0].children[2].textContent,'Escolha uma resposta.');
});
