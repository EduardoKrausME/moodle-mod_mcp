/**
 * Example 8/10 — História da Astrologia.
 * node --test docs/pt_br/tests/astrology-history.test.mjs
 * Sem rede, Moodle, browser ou pacotes de terceiros.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/historia-da-astrologia.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scripts[0]?.[1]??'';
const model=JSON.parse(js.match(/const model=(\{[^\n]+\});/)?.[1]??'null');
const {milestones,claims,glossary}=model;

test('HTML acessível, independente, sem CDNs e com SVGs descritos',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scripts.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(|localStorage/i);
 assert.match(html,/@media\(max-width:650px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/role="progressbar"/);
 assert.match(html,/aria-labelledby="art-title art-desc"/);
 assert.match(html,/aria-labelledby="wheel-title wheel-desc"/);
 assert.match(html,/aria-live="polite"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'historia-da-astrologia.html'}));
});

test('16 marcos históricos com referências, limites e SVG próprio',()=>{
 assert.equal(milestones.length,16);
 assert.equal((html.match(/<symbol id="astro-/g)||[]).length,16);
 assert.equal(new Set(milestones.map(x=>x.id)).size,16);
 assert.equal(new Set(milestones.map(x=>x.motif)).size,16);
 assert.deepEqual(milestones.map(x=>x.year),[...milestones.map(x=>x.year)].sort((a,b)=>a-b));
 assert.equal(milestones[0].id,'omen');
 assert.equal(milestones.at(-1).id,'critical');
 assert.deepEqual([...new Set(milestones.map(x=>x.era))],['mesopotamia','helenistica','india','islamica','europa','moderna']);
 for(const item of milestones){
  for(const field of ['id','date','short','era','place','title','description','impact','caution','source','url','motif']){
   assert.ok(item[field],item.id+': '+field);
  }
  assert.match(item.url,/^https:\/\//);
  assert.ok(html.includes('id="astro-'+item.motif+'"'),item.motif);
 }
 assert.match(milestones[2].caution,/setores de 30 graus/);
 assert.match(milestones.at(-1).caution,/não é evidência/);
});

test('análise de ciência, história e crença tem seis afirmações e glossário',()=>{
 assert.equal(claims.length,6);
 assert.equal(glossary.length,5);
 assert.equal(new Set(claims.map(x=>x.id)).size,6);
 assert.deepEqual([...new Set(claims.map(x=>x.answer))].sort(),['crenca','historia','observacao']);
 assert.deepEqual(glossary.map(x=>x.id),['ecliptica','signo','constelacao','precessao','mapa']);
 for(const c of claims){
  assert.ok(c.text&&c.why);
  assert.ok(['crenca','historia','observacao'].includes(c.answer));
 }
 assert.match(html,/const wheelViews=\[/);
 assert.match(html,/const categories=\[/);
 assert.match(html,/Não é um mapa astronômico/);
});

test('catálogo preserva os sete exemplos anteriores; prompt pergunta e aguarda',()=>{
 assert.deepEqual(manifest.examples.slice(0,8).map(x=>x.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
  'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo','do-cacau-ao-chocolate',
  'da-flor-a-fruta','historia-da-astrologia'
 ]);
 assert.equal(new Set(manifest.examples.map(x=>x.path)).size,manifest.examples.length);
 const entry=manifest.examples.find(e=>e.id==='historia-da-astrologia');
 assert.equal(entry.path,'timeline/historia-da-astrologia.html');
 assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(entry.prompt,/Aguarde a resposta do professor/);
 assert.ok(entry.features.length>=6);
});

class Node{
 constructor(tag='div'){
  this.tag=tag;this.children=[];this.attrs={};this.events={};this.className='';
  this.classes=new Set();
  this.classList={toggle:(key,on)=>on?this.classes.add(key):this.classes.delete(key)};
  this.style={opacity:'1'};this.clientWidth=600;this.clientHeight=380;
  this.offsetLeft=0;this.offsetTop=0;this.disabled=false;
 }
 replaceChildren(...nodes){this.children=nodes;}
 append(...nodes){this.children.push(...nodes);}
 setAttribute(key,value){this.attrs[key]=String(value);}
 addEventListener(key,handler){this.events[key]=handler;}
 click(){this.events.click?.({});}
 key(name){this.events.keydown?.({key:name,preventDefault(){}});}
 querySelectorAll(selector){return this.children.filter(n=>n.className.split(' ').includes(selector.slice(1)));}
 focus(){this.focused=true;}
 scrollTo(){}
}
function mount(){
 const ids=['filters','timeline','archive-index','archive-place','archive-title','archive-date',
  'archive-description','archive-impact','archive-caution','archive-source','art-title','art-desc',
  'visited-label','visited-meter','visited-fill','prev','next','announcement','art-use','wheel-buttons',
  'wheel-explain','wheel-signs','wheel-stars','wheel-drift','wheel-desc','gloss-buttons','gloss-info',
  'score','claims','reset-history','reset-claims'];
 const nodes=new Map(ids.map(id=>[id,new Node()]));
 const document={
  getElementById:id=>{assert.ok(nodes.has(id),'Missing element '+id);return nodes.get(id);},
  createElement:tag=>new Node(tag)
 };
 new Function('document','window',js)(document,{matchMedia:q=>({matches:q.includes('reduced-motion')})});
 return id=>nodes.get(id);
}

test('timeline com filtros, anterior/próximo, teclado e progresso',()=>{
 const get=mount();
 assert.equal(get('timeline').children.length,16);
 assert.equal(get('archive-title').textContent,'Observação e adivinhação');
 assert.equal(get('prev').disabled,true);
 get('next').click();
 assert.equal(get('archive-title').textContent,'Tradições escritas');
 get('filters').children[3].click();
 assert.equal(get('timeline').children.length,2);
 assert.equal(get('archive-title').textContent,'Jyotiṣa em transformação');
 get('timeline').children[0].key('End');
 assert.equal(get('archive-title').textContent,'Conhecimento astral em sânscrito');
 get('timeline').children[1].key('Home');
 assert.equal(get('archive-title').textContent,'Jyotiṣa em transformação');
 get('reset-history').click();
 assert.equal(get('timeline').children.length,16);
 assert.equal(get('visited-meter').attrs['aria-valuenow'],'1');
 assert.equal(get('archive-title').textContent,'Observação e adivinhação');
});

test('diagrama muda camadas e glossário informa sem simular previsão',()=>{
 const get=mount();
 assert.equal(get('wheel-buttons').children.length,3);
 get('wheel-buttons').children[1].click();
 assert.equal(get('wheel-stars').style.opacity,'1');
 assert.equal(get('wheel-signs').style.opacity,'.14');
 assert.match(get('wheel-explain').children[0].textContent,/céu/);
 get('wheel-buttons').children[2].click();
 assert.equal(get('wheel-drift').style.opacity,'1');
 assert.match(get('wheel-desc').textContent,/26 mil anos/);
 assert.equal(get('gloss-buttons').children.length,5);
 get('gloss-buttons').children[3].click();
 assert.equal(get('gloss-info').children[0].textContent,'Precessão');
 assert.equal(get('gloss-buttons').children[3].attrs['aria-pressed'],'true');
});

test('seis classificações com pontuação, feedback e reinício',()=>{
 const get=mount();
 assert.equal(get('claims').children.length,6);
 get('claims').children[0].children[1].children[0].click();
 assert.match(get('score').textContent,/1 de 6 afirmações classificadas · 1 acerto$/);
 assert.match(get('claims').children[0].children[2].textContent,/^Correto/);
 get('claims').children[1].children[1].children[0].click();
 assert.match(get('claims').children[1].children[2].textContent,/^Reveja/);
 assert.match(get('score').textContent,/2 de 6 afirmações classificadas · 1 acerto$/);
 get('claims').children[2].children[1].children[1].click();
 assert.match(get('score').textContent,/3 de 6 afirmações classificadas · 2 acertos$/);
 get('reset-claims').click();
 assert.equal(get('score').textContent,'0 de 6 afirmações classificadas · 0 acertos');
 assert.equal(get('claims').children[0].children[2].textContent,'Escolha uma categoria.');
});
