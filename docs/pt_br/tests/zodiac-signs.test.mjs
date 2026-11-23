/**
 * Example 9 of 10 — 12 Signos: Atlas do Zodíaco Cultural.
 * node --test docs/pt_br/tests/zodiac-signs.test.mjs
 * Sem navegador, Moodle, rede ou dependências de terceiros.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const base=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(base,'timeline/12-signos.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(base,'timeline/examples.json'),'utf8'));
const scriptNodes=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scriptNodes[0]?.[1]??'';
const data=JSON.parse(js.match(/const model=(\{[^\n]+\});/)?.[1]??'null');
const {signs,history,quiz}=data;

test('HTML autossuficiente, acessível, responsivo e sem dependências',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scriptNodes.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(|localStorage/i);
 assert.match(html,/@media\(max-width:550px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/button:focus-visible/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/role="progressbar"/);
 assert.match(html,/aria-labelledby="art-title art-desc"/);
 assert.match(html,/aria-labelledby="wheel-title wheel-desc"/);
 assert.match(html,/aria-labelledby="hero-title hero-desc"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'12-signos.html'}));
});

test('12 signos, 12 SVGs originais e exatamente 12 setores de 30 graus',()=>{
 assert.equal(signs.length,12);
 assert.equal((html.match(/<symbol id="z-/g)||[]).length,12);
 assert.equal(new Set(signs.map(s=>s.id)).size,12);
 assert.equal(new Set(signs.map(s=>s.motif)).size,12);
 assert.deepEqual(signs.map(s=>s.start),Array.from({length:12},(_,i)=>i*30));
 assert.deepEqual(signs.map(s=>s.end),Array.from({length:12},(_,i)=>(i+1)*30));
 assert.deepEqual(signs.map(s=>s.id),['aries','taurus','gemini','cancer','leo','virgo','libra','scorpio','sagittarius','capricorn','aquarius','pisces']);
 assert.deepEqual(signs.map(s=>s.latin),['Aries','Taurus','Gemini','Cancer','Leo','Virgo','Libra','Scorpius','Sagittarius','Capricornus','Aquarius','Pisces']);
 for(const sign of signs){
  for(const key of ['id','name','latin','glyph','figure','element','mode','cultural','astronomy','motif','url'])assert.ok(sign[key],sign.id+': '+key);
  assert.ok(html.includes('id="z-'+sign.motif+'"'),sign.motif);
  assert.match(sign.url,/^https:\/\//);
 }
});

test('13 constelações atravessadas pela eclíptica, com Ofiúco e cautela sobre escalas',()=>{
 assert.equal(history.length,4);
 assert.equal(quiz.length,5);
 assert.match(js,/const constellations=\[/);
 assert.match(js,/name:"Ofiúco"/);
 assert.match(js,/const comparisons=\[/);
 assert.match(js,/NÃO representam extensões reais/);
 assert.match(js,/trinta graus|30°/i);
 assert.match(html,/IAU/);
 assert.match(html,/precessão/i);
 assert.match(html,/não são cientificamente|não estabelecem que signos|não valida/i);
 assert.deepEqual(history.map(h=>h.id),['babylon','hellenic','iau-list','iau-limits']);
 assert.match(history[2].date,/1922/);
 assert.match(history[3].date,/1928/);
 for(const item of history){
  assert.ok(item.date&&item.detail&&item.title&&item.url);
  assert.match(item.url,/^https:\/\//);
 }
});

test('novo registro acrescentado ao final; prompt pergunta ao professor e aguarda',()=>{
 assert.deepEqual(manifest.examples.slice(0,9).map(x=>x.id),[
 'historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade',
 'evolucao-da-vida-na-terra','historia-da-banana-pelo-mundo','do-cacau-ao-chocolate',
 'da-flor-a-fruta','historia-da-astrologia','doze-signos'
 ]);
 assert.equal(new Set(manifest.examples.map(x=>x.path)).size,manifest.examples.length);
 const entry=manifest.examples.find(x=>x.id==='doze-signos');
 assert.equal(entry.path,'timeline/12-signos.html');
 assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(entry.prompt,/Aguarde a resposta do professor/);
 assert.ok(entry.features.length>=6);
});
class FakeElement{
 constructor(tag='div'){
  this.tag=tag;this.children=[];this.events={};this.attrs={};this.className='';
  this.classes=new Set();
  this.classList={toggle:(name,on)=>on?this.classes.add(name):this.classes.delete(name)};
  this.style={};this.disabled=false;
 }
 replaceChildren(...nodes){this.children=nodes;}
 append(...nodes){this.children.push(...nodes);}
 addEventListener(name,callback){this.events[name]=callback;}
 setAttribute(name,value){this.attrs[name]=String(value);}
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 querySelectorAll(selector){return this.children.filter(x=>x.className.split(' ').includes(selector.slice(1)));}
 focus(){this.focused=true;}
}
function mount(){
 const ids=['sign-list','sign-index','sign-figure','sign-title','sign-degrees','sign-cultural',
 'sign-astronomy','sign-tradition','art-title','art-desc','visited-label','wheel-glyph',
 'wheel-name','wheel-note','sign-use','sign-reference','wheel-selected','wheel-desc',
 'previous','next','meter','meter-fill','announcement','compare-buttons','compare-panel',
 'compare-caution','compare-band','history-list','history-title','history-detail',
 'history-source','questions','score','reset-quiz','reset-all'];
 const nodes=new Map(ids.map(id=>[id,new FakeElement()]));
 const document={getElementById:id=>{assert.ok(nodes.has(id),'Missing element: '+id);return nodes.get(id);},createElement:tag=>new FakeElement(tag)};
 new Function('document','window',js)(document,{});
 return id=>nodes.get(id);
}

test('navegação dos 12 signos por toque e teclado, roda e progresso',()=>{
 const get=mount();
 assert.equal(get('sign-list').children.length,12);
 assert.equal(get('sign-title').textContent,'Áries');
 assert.equal(get('sign-use').attrs.href,'#z-ram');
 assert.equal(get('previous').disabled,true);
 assert.equal(get('meter').attrs['aria-valuenow'],'1');
 assert.match(get('wheel-selected').attrs.d,/A213 213/);
 get('next').click();
 assert.equal(get('sign-title').textContent,'Touro');
 assert.equal(get('wheel-name').textContent,'Touro');
 get('sign-list').children[0].key('End');
 assert.equal(get('sign-title').textContent,'Peixes');
 assert.equal(get('next').disabled,true);
 get('sign-list').children[11].key('Home');
 assert.equal(get('sign-title').textContent,'Áries');
});

test('comparação alterna 12 signos, 13 constelações e precessão',()=>{
 const get=mount();
 assert.equal(get('compare-buttons').children.length,3);
 assert.equal(get('compare-band').children.length,12);
 get('compare-buttons').children[1].click();
 assert.equal(get('compare-band').children.length,13);
 assert.equal(get('compare-band').children[8].children[1].textContent,'Ofiúco');
 assert.match(get('compare-panel').children[1].textContent,/Ofiúco/);
 assert.match(get('compare-caution').textContent,/NÃO representam extensões reais/);
 get('compare-buttons').children[2].click();
 assert.equal(get('compare-band').children.length,12);
 assert.match(get('compare-panel').children[1].textContent,/26 mil anos/);
 get('compare-buttons').children[0].click();
 assert.equal(get('compare-buttons').children[0].attrs['aria-pressed'],'true');
});

test('cronologia histórica, quiz explicativo e reinícios independentes',()=>{
 const get=mount();
 assert.equal(get('history-list').children.length,4);
 get('history-list').children[3].click();
 assert.match(get('history-title').textContent,/Limites científicos/);
 assert.match(get('history-detail').textContent,/1928/);
 assert.equal(get('questions').children.length,5);
 get('questions').children[0].children[1].children[0].click();
 assert.match(get('questions').children[0].children[2].textContent,/^Correto/);
 assert.equal(get('score').textContent,'1 de 5 respondidas · 1 acerto');
 get('questions').children[1].children[1].children[0].click();
 assert.match(get('questions').children[1].children[2].textContent,/^Reveja/);
 get('reset-quiz').click();
 assert.equal(get('score').textContent,'0 de 5 respondidas · 0 acertos');
 get('compare-buttons').children[1].click();
 get('next').click();
 get('reset-all').click();
 assert.equal(get('sign-title').textContent,'Áries');
 assert.equal(get('meter').attrs['aria-valuenow'],'1');
 assert.equal(get('compare-band').children.length,12);
 assert.match(get('history-title').textContent,/Doze setores/);
 assert.equal(get('score').textContent,'0 de 5 respondidas · 0 acertos');
});
