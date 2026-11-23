/**
 * Histórico da banana: validação independente do Moodle e do navegador.
 * Execute: node --test docs/pt_br/tests/banana-history.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/historia-da-banana-pelo-mundo.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const code=scripts[0]?.[1]??'';
const events=JSON.parse(code.match(/const events=(\[[^\n]+\]);/)?.[1]??'null');

test('HTML único, responsivo, isolado, sem dependências remotas, com SVG acessível',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scripts.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|localStorage|fetch\(/i);
 assert.match(html,/@media\(max-width:640px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/aria-labelledby="illustration-title illustration-desc"/);
 assert.match(html,/aria-labelledby="map-title map-desc"/);
 assert.match(html,/aria-live="polite"/);
 assert.doesNotThrow(()=>new Script(code,{filename:'historia-da-banana-pelo-mundo.html'}));
});

test('15 marcos datados e 15 SVGs originais com fontes e ressalvas',()=>{
 assert.equal(events.length,15);
 assert.equal(new Set(events.map(x=>x.id)).size,15);
 assert.equal(new Set(events.map(x=>x.motif)).size,15);
 assert.equal((html.match(/<symbol id="ban-/g)||[]).length,15);
 assert.deepEqual(events.map(x=>x.year),[...events.map(x=>x.year)].sort((a,b)=>a-b));
 assert.equal(events[0].id,'kuk');
 assert.equal(events.at(-1).id,'future');
 for(const e of events){
  assert.match(e.id,/^[a-z0-9-]+$/);
  for(const key of ['date','title','short','region','chapter','subject','motif','route','what','change','care','source','url'])assert.ok(e[key],e.id+': '+key);
  assert.ok(html.includes('id="ban-'+e.motif+'"'),e.id);
  assert.match(e.url,/^https:\/\//);
 }
 assert.deepEqual([...new Set(events.map(x=>x.chapter))].sort(),['comercio','futuro','origens','rotas','saude']);
});

test('manifesto conserva quatro exemplos e seu novo prompt espera o professor',()=>{
 assert.deepEqual(manifest.examples.slice(0,5).map(e=>e.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
  'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo'
 ]);
 assert.equal(new Set(manifest.examples.map(e=>e.path)).size,manifest.examples.length);
 const entry=manifest.examples.find(e=>e.id==='historia-da-banana-pelo-mundo');
 assert.equal(entry.path,'timeline/historia-da-banana-pelo-mundo.html');
 assert.ok(entry.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(entry.prompt.includes('Aguarde a resposta do professor'));
 assert.ok(entry.features.length>=5);
});

class Element{
 constructor(tag='div'){
  this.tag=tag;
  this.children=[];
  this.attrs={};
  this.events={};
  this.className='';
  this.classes=new Set();
  this.classList={toggle:(name,on)=>on?this.classes.add(name):this.classes.delete(name)};
  this.css={};
  this.style={setProperty:(name,value)=>{this.css[name]=value;},opacity:'1',strokeWidth:'5'};
  this.clientWidth=650;this.clientHeight=480;this.offsetLeft=0;this.offsetTop=0;
  this.disabled=false;
 }
 setAttribute(name,value){this.attrs[name]=String(value);}
 addEventListener(name,callback){this.events[name]=callback;}
 append(...nodes){this.children.push(...nodes);}
 replaceChildren(...nodes){this.children=nodes;}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 scrollTo(){}
 focus(){this.focused=true;}
 querySelectorAll(selector){return this.children.filter(node=>node.className.split(' ').includes(selector.slice(1)));}
}
function mount(){
 const ids=['chapters','journey','folio','region','event-title','event-date','event-what','event-change','event-care','event-source','illustration-title','illustration-desc','drawing-use','previous','next','meter','meter-fill','count','announcement','route-buttons','route-copy','route-path-pacifico','route-path-indico','route-path-atlantico','cultivar-buttons','cultivar-copy','questions','reset'];
 const els=new Map(ids.map(id=>[id,new Element()]));
 const document={
  getElementById:id=>{assert.ok(els.has(id),'id de controle ausente: '+id);return els.get(id);},
  createElement:tag=>new Element(tag),
  createElementNS:(_namespace,tag)=>new Element(tag)
 };
 new Function('document','window',code)(document,{matchMedia:query=>({matches:query.includes('reduced-motion')})});
 return id=>els.get(id);
}

test('linha do tempo, filtro, toque e teclado mantêm navegação e cronologia',()=>{
 const get=mount();
 assert.equal(get('journey').children.length,15);
 assert.match(get('event-title').textContent,/Nova Guiné/);
 assert.equal(get('previous').disabled,true);
 get('next').click();
 assert.match(get('event-title').textContent,/parentes silvestres/);
 get('chapters').children[3].click();
 assert.equal(get('journey').children.length,4);
 assert.equal(get('event-date').textContent,'década de 1830');
 get('journey').children[0].key('End');
 assert.equal(get('event-date').textContent,'início do século XX');
 get('journey').children[0].key('Home');
 assert.equal(get('event-date').textContent,'década de 1830');
 assert.equal(get('meter').attrs['aria-valuemax'],undefined);
 assert.match(html,/id="meter"[^>]*aria-valuemax="15"/);
});

test('mapa de rotas e comparador de três variedades operam por botões e texto acessível',()=>{
 const get=mount();
 assert.equal(get('route-buttons').children.length,3);
 assert.equal(get('route-buttons').children[0].attrs['aria-pressed'],'true');
 get('route-buttons').children[1].click();
 assert.match(get('route-copy').textContent,/Madagascar/);
 assert.equal(get('route-path-indico').style.opacity,'1');
 assert.equal(get('route-path-atlantico').style.opacity,'.18');
 assert.equal(get('cultivar-buttons').children.length,3);
 get('cultivar-buttons').children[0].click();
 assert.match(get('cultivar-copy').children[0].textContent,/Cavendish/);
 assert.equal(get('cultivar-buttons').children[0].attrs['aria-pressed'],'true');
 get('cultivar-buttons').children[2].click();
 assert.match(get('cultivar-copy').children[0].textContent,/Banana-da-terra/);
 assert.match(get('cultivar-copy').children[1].textContent,/AAB/);
});

test('perguntas explicadas e reinício limpam respostas, filtros e progresso',()=>{
 const get=mount();
 assert.equal(get('questions').children.length,3);
 get('questions').children[0].children[1].children[0].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 get('chapters').children[4].click();
 get('reset').click();
 assert.equal(get('journey').children.length,15);
 assert.match(get('event-title').textContent,/Nova Guiné/);
 assert.equal(get('meter').attrs['aria-valuenow'],'1');
 assert.equal(get('questions').children[0].children[2].textContent,'Escolha uma alternativa.');
 assert.equal(get('cultivar-copy').children.length,1);
});
