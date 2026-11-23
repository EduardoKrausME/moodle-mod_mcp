/**
 * História da Astronomia (exemplo 10/10).
 * node --test docs/pt_br/tests/astronomy-history.test.mjs
 * Sem navegador, Moodle, internet ou dependências externas.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/historia-da-astronomia.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scriptBlocks=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scriptBlocks[0]?.[1]??'';
const model=JSON.parse(js.match(/const data=(\{[^\n]+\});/)?.[1]??'null');
const {events,models,signals,quiz}=model;

test('HTML completo, autossuficiente, responsivo e acessível',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scriptBlocks.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|localStorage|fetch\(/i);
 assert.match(html,/@media\(max-width:650px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/role="progressbar"/);
 assert.match(html,/aria-labelledby="illustration-title illustration-desc"/);
 assert.match(html,/aria-labelledby="models-art-title models-art-desc"/);
 assert.match(html,/aria-labelledby="signal-art-title signal-art-desc"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'historia-da-astronomia.html'}));
});

test('24 marcos cronológicos com datas, fontes científicas e ressalvas',()=>{
 assert.equal(events.length,24);
 assert.equal(new Set(events.map(e=>e.id)).size,24);
 assert.equal(new Set(events.map(e=>e.motif)).size,24);
 assert.deepEqual(events.map(e=>e.year),[...events.map(e=>e.year)].sort((a,b)=>a-b));
 assert.equal(events[0].id,'babylon');
 assert.equal(events.at(-1).id,'webb');
 for(const e of events){
  for(const key of ['id','date','short','era','classification','place','title','what','impact','caution','source','url','motif'])assert.ok(e[key],e.id+': missing '+key);
  assert.match(e.url,/^https:\/\//);
 }
 assert.deepEqual([...new Set(events.map(e=>e.era))],['antiga','global','revolucao','cosmos','multimensageiro']);
});

test('registros, modelos substituídos e instrumentos são explicitamente distintos',()=>{
 assert.deepEqual([...new Set(events.map(e=>e.classification))].sort(),['instrumento','modelo','registro']);
 assert.equal(events.find(e=>e.id==='ptolemy').classification,'modelo');
 assert.match(events.find(e=>e.id==='ptolemy').caution,/superado/);
 assert.match(events.find(e=>e.id==='kepler').caution,/aproximações/);
 assert.equal(events.find(e=>e.id==='galileo').classification,'registro');
 assert.equal(events.find(e=>e.id==='hubble-space').classification,'instrumento');
 assert.match(events.find(e=>e.id==='exoplanet').caution,/pulsares/);
 assert.match(events.find(e=>e.id==='gravitational').caution,/2015/);
 assert.match(events.find(e=>e.id==='black-hole').caution,/rádio/);
});

test('24 SVGs autorais e esquemas honestamente declarados sem escala',()=>{
 assert.equal((html.match(/<symbol id="obs-/g)||[]).length,24);
 for(const e of events)assert.ok(html.includes('id="obs-'+e.motif+'"'),'Ilustração ausente: '+e.motif);
 assert.match(html,/não são usados para calcular a posição real/);
 assert.match(html,/sem frequência, amplitude, escala nem dados medidos/);
 assert.match(html,/ESQUEMA · NÃO É DOCUMENTO/);
 assert.match(html,/sem recursos remotos/);
});

test('catálogo mantém nove exemplos e décimo prompt pergunta e espera',()=>{
 assert.deepEqual(manifest.examples.slice(0,10).map(e=>e.id),[
  'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
  'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo','do-cacau-ao-chocolate',
  'da-flor-a-fruta','historia-da-astrologia','doze-signos','historia-da-astronomia'
 ]);
 assert.equal(new Set(manifest.examples.map(e=>e.path)).size,manifest.examples.length);
 const e=manifest.examples.find(e=>e.id==='historia-da-astronomia');
 assert.equal(e.path,'timeline/historia-da-astronomia.html');
 assert.match(e.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(e.prompt,/Aguarde a resposta do professor/);
 assert.ok(e.features.length>=7);
 assert.ok(manifest.examples.slice(0,9).every(x=>x.prompt&&x.path));
});

class Node{
 constructor(tag='div'){
  this.tag=tag;this.children=[];this.events={};this.attrs={};this.className='';
  this.classes=new Set();
  this.classList={toggle:(name,on)=>on?this.classes.add(name):this.classes.delete(name)};
  this.style={};this.disabled=false;this.clientWidth=640;this.clientHeight=480;
  this.offsetLeft=0;this.offsetTop=0;
 }
 replaceChildren(...nodes){this.children=nodes;}
 append(...nodes){this.children.push(...nodes);}
 setAttribute(name,value){this.attrs[name]=String(value);}
 addEventListener(name,callback){this.events[name]=callback;}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 querySelectorAll(selector){return this.children.filter(x=>x.className.split(' ').includes(selector.slice(1)));}
 focus(){this.focused=true;}
 scrollTo(){}
}
function mount(){
 const ids=[
  'filters','timeline','event-index','event-place','event-title','event-date',
  'event-what','event-impact','event-caution','event-source','evidence-label',
  'evidence-explain','illustration-title','illustration-desc','progress-label',
  'progress','progress-fill','illustration-use','previous','next','announcement',
  'model-controls','model-description','models-art-desc','model-geocentric',
  'model-heliocentric','model-kepler','signal-controls','signal-path',
  'signal-graphic-label','signal-art-title','signal-art-desc','signal-description',
  'score','questions','reset-history','reset-quiz'
 ];
 const elements=new Map(ids.map(id=>[id,new Node()]));
 const document={
  getElementById:id=>{assert.ok(elements.has(id),'Missing '+id);return elements.get(id);},
  createElement:tag=>new Node(tag)
 };
 new Function('document','window',js)(document,{matchMedia:q=>({matches:q.includes('reduced-motion')})});
 return id=>elements.get(id);
}

test('linha do tempo, filtro, toque, anterior/próximo e Home/End',()=>{
 const get=mount();
 assert.equal(get('timeline').children.length,24);
 assert.equal(get('event-title').textContent,'Astronomia registrada em tabuletas');
 assert.equal(get('previous').disabled,true);
 get('next').click();
 assert.equal(get('event-title').textContent,'Posições de estrelas podem ser comparadas');
 get('filters').children[2].click();
 assert.equal(get('timeline').children.length,4);
 assert.equal(get('event-title').textContent,'Astronomia matemática em sânscrito');
 get('timeline').children[0].key('End');
 assert.equal(get('event-title').textContent,'Catálogos e grandes instrumentos');
 get('timeline').children[3].key('Home');
 assert.equal(get('event-title').textContent,'Astronomia matemática em sânscrito');
 get('filters').children[3].click();
 assert.equal(get('timeline').children.length,6);
 get('timeline').children[0].click();
 assert.equal(get('evidence-label').textContent,'Modelo ou explicação teórica');
 assert.equal(get('illustration-use').attrs.href,'#obs-heliocentric');
});

test('mesa dos modelos compara três arranjos com seus limites',()=>{
 const get=mount();
 assert.equal(get('model-controls').children.length,3);
 assert.equal(get('model-geocentric').style.display,'');
 get('model-controls').children[1].click();
 assert.equal(get('model-controls').children[1].attrs['aria-pressed'],'true');
 assert.equal(get('model-geocentric').style.display,'none');
 assert.equal(get('model-heliocentric').style.display,'');
 get('model-controls').children[2].click();
 assert.equal(get('model-kepler').style.display,'');
 assert.match(get('model-description').children[1].textContent,/Kepler/);
 assert.match(get('models-art-desc').textContent,/sem escala/);
});

test('quatro sinais mudam diagrama acessível, seis questões têm feedback',()=>{
 const get=mount();
 assert.equal(get('signal-controls').children.length,4);
 get('signal-controls').children[3].click();
 assert.equal(get('signal-controls').children[3].attrs['aria-pressed'],'true');
 assert.match(get('signal-description').children[0].textContent,/gravitacionais/);
 assert.match(get('signal-path').attrs.d,/M30 145 Q90/);
 assert.match(get('signal-art-desc').textContent,/sem frequência/);
 assert.equal(get('questions').children.length,6);
 get('questions').children[0].children[1].children[0].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 assert.equal(get('score').textContent,'1 de 6 respondidas · 1 acerto');
 get('questions').children[1].children[1].children[0].click();
 assert.match(get('questions').children[1].children[2].textContent,/^Reveja/);
 assert.equal(get('score').textContent,'2 de 6 respondidas · 1 acerto');
 get('reset-quiz').click();
 assert.equal(get('score').textContent,'0 de 6 respondidas · 0 acertos');
 assert.equal(get('questions').children[0].children[2].textContent,'Escolha uma alternativa.');
});

test('visitas acumulam até 24; reinício limpa apenas histórico',()=>{
 const get=mount();
 for(let index=1;index<24;index++)get('timeline').children[index].click();
 assert.equal(get('progress').attrs['aria-valuenow'],'24');
 assert.equal(get('next').disabled,true);
 get('questions').children[0].children[1].children[0].click();
 get('reset-history').click();
 assert.equal(get('progress').attrs['aria-valuenow'],'1');
 assert.equal(get('timeline').children.length,24);
 assert.equal(get('event-title').textContent,'Astronomia registrada em tabuletas');
 assert.equal(get('score').textContent,'1 de 6 respondidas · 1 acerto');
});
