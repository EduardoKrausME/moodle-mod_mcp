/**
 * Exemplo 4/10: Evolução da Vida na Terra.
 * Execute: node --test docs/pt_br/tests/evolution.test.mjs
 * Sem biblioteca externa, navegador, rede nem Moodle.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/evolucao-da-vida-na-terra.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const code=scripts[0]?.[1]??'';
const data=code.match(/const events = (\[[^\n]+\]);/);
const events=JSON.parse(data?.[1]??'null');

test('HTML acessível, independente e com arte SVG própria',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.equal(scripts.length,1);
 assert.match(html,/<style>[\s\S]*<\/style>/);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(|localStorage/i);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/@media\(max-width:580px\)/);
 assert.match(html,/aria-labelledby="specimen-title specimen-desc"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/role="progressbar"/);
 assert.doesNotThrow(()=>new Script(code,{filename:'evolucao-da-vida-na-terra.html'}));
});

test('17 eventos cronológicos com fontes e um símbolo original cada',()=>{
 assert.equal(events.length,17);
 assert.equal(new Set(events.map(e=>e.id)).size,17);
 assert.equal(new Set(events.map(e=>e.motif)).size,17);
 assert.deepEqual(events.map(e=>e.ageMa),[...events.map(e=>e.ageMa)].sort((a,b)=>b-a));
 assert.equal(events[0].ageMa,4600);
 assert.equal(events.at(-1).ageMa,0.3);
 assert.equal((html.match(/<symbol id="life-/g)||[]).length,17);
 for(const event of events){
  assert.match(event.id,/^[a-z0-9-]+$/);
  for(const field of ['ageMa','date','short','era','period','topic','title','body','impact','care','source','url','motif'])assert.ok(event[field]!==undefined&&event[field]!==null&&event[field]!=='',event.id+': '+field);
  assert.ok(html.includes('id="life-'+event.motif+'"'),event.motif);
  assert.match(event.url,/^https:\/\//);
 }
 assert.deepEqual([...new Set(events.map(e=>e.era))].sort(),['ceno','meso','paleo','pre']);
 assert.match(html,/As aves são a linhagem sobrevivente de dinossauros/);
 assert.match(html,/Seres humanos e chimpanzés atuais possuem ancestrais comuns/);
});

test('catálogo preserva os três exemplos anteriores e pergunta antes de criar',()=>{
 assert.deepEqual(manifest.examples.slice(0,4).map(e=>e.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade','evolucao-da-vida-na-terra'
 ]);
 assert.equal(new Set(manifest.examples.map(e=>e.path)).size,manifest.examples.length);
 const entry=manifest.examples.find(e=>e.id==='evolucao-da-vida-na-terra');
 assert.equal(entry.path,'timeline/evolucao-da-vida-na-terra.html');
 assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(entry.prompt,/Aguarde a resposta do professor/);
 assert.ok(entry.features.length>=5);
});

class Element{
 constructor(tag='div'){
  this.tag=tag;this.children=[];this.events={};this.attrs={};this.className='';this.classes=new Set();
  this.classList={toggle:(name,on)=>on?this.classes.add(name):this.classes.delete(name)};
  this.css={};this.style={setProperty:(name,value)=>{this.css[name]=value;}};
  this.clientWidth=700;this.clientHeight=400;this.offsetLeft=0;this.offsetTop=0;this.disabled=false;
 }
 setAttribute(name,value){this.attrs[name]=String(value);}
 addEventListener(name,handler){this.events[name]=handler;}
 replaceChildren(...nodes){this.children=nodes;}
 append(...nodes){this.children.push(...nodes);}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 querySelectorAll(selector){return this.children.filter(c=>c.className.split(' ').includes(selector.slice(1)));}
 scrollTo(){}
 focus(){this.focused=true;}
}
function mount(){
 const ids=['era-filters','strata-list','fossil-number','fossil-era','fossil-title','fossil-date','fossil-description','fossil-impact','fossil-limits','fossil-source','specimen-title','specimen-desc','specimen-use','earth-clock','visited-count','timebar','previous','next','visited-meter','visited-fill','announce','branch-choices','branch-explanation','questions','restart'];
 const dom=new Map(ids.map(id=>[id,new Element()]));
 const doc={
  getElementById:id=>{assert.ok(dom.has(id),'id ausente: '+id);return dom.get(id);},
  createElement:tag=>new Element(tag)
 };
 new Function('document','window',code)(doc,{matchMedia:query=>({matches:query.includes('reduced-motion')})});
 return id=>dom.get(id);
}
test('navegação, eras, teclado, links científicos e relógio de 24 horas',()=>{
 const get=mount();
 assert.equal(get('strata-list').children.length,17);
 assert.match(get('fossil-title').textContent,/formação da Terra/);
 assert.equal(get('earth-clock').textContent,'00:00:00');
 assert.equal(get('previous').disabled,true);
 get('next').click();
 assert.match(get('fossil-title').textContent,/Microrganismos/);
 assert.notEqual(get('earth-clock').textContent,'00:00:00');
 get('era-filters').children[3].click();
 assert.equal(get('strata-list').children.length,4);
 assert.match(get('fossil-title').textContent,/dinossauros/);
 get('strata-list').children[0].key('End');
 assert.match(get('fossil-title').textContent,/Angiospermas/);
 get('strata-list').children[0].key('Home');
 assert.match(get('fossil-title').textContent,/dinossauros/);
 get('era-filters').children[4].click();
 get('strata-list').children.at(-1).click();
 assert.equal(get('earth-clock').textContent,'23:59:54');
 assert.match(html,/id="visited-meter"[^>]*aria-valuemax="17"/);
 assert.match(get('fossil-source').href,/^https:\/\//);
});

test('relações ramificadas, quiz, progresso e reinício',()=>{
 const get=mount();
 get('branch-choices').children[0].click();
 assert.match(get('branch-explanation').textContent,/terópodes/);
 assert.equal(get('branch-choices').children[0].attrs['aria-pressed'],'true');
 assert.equal(get('questions').children.length,3);
 get('questions').children[0].children[1].children[1].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 get('era-filters').children[2].click();
 get('restart').click();
 assert.equal(get('strata-list').children.length,17);
 assert.equal(get('earth-clock').textContent,'00:00:00');
 assert.equal(get('visited-meter').attrs['aria-valuenow'],'1');
 assert.equal(get('questions').children[0].children[2].textContent,'Escolha uma alternativa.');
 assert.equal(get('branch-explanation').textContent,'Selecione uma relação evolutiva para investigar.');
});
