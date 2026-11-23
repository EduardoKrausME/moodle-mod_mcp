/**
 * Exemplo 3/10: Grandes Invenções da Humanidade
 * Execute: node --test docs/pt_br/tests/inventions.test.mjs
 * Não depende de DOM real, rede, bibliotecas, Moodle ou navegador.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'timeline/grandes-invencoes-da-humanidade.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
const js=scripts[0]?.[1]??'';
const data=js.match(/const inventions = (\[[^\n]+\]);/);
const inventions=JSON.parse(data?.[1]??'null');

test('HTML autossuficiente, independente, responsivo e acessível',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<meta name="viewport"/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal(scripts.length,1);
 assert.doesNotMatch(html,/<script[^>]+src=|<link[^>]+href=|<footer\b|fetch\(|localStorage/i);
 assert.match(html,/@media\(max-width:540px\)/);
 assert.match(html,/prefers-reduced-motion/);
 assert.match(html,/role="tablist"/);
 assert.match(html,/role="tabpanel"/);
 assert.match(html,/aria-live="polite"/);
 assert.match(html,/aria-labelledby="drawing-title drawing-desc"/);
 assert.match(html,/<ol class="order-list"/);
 assert.doesNotThrow(()=>new Script(js,{filename:'grandes-invencoes-da-humanidade.html'}));
});

test('14 invenções em ordem cronológica, ilustradas e contextualizadas com fontes',()=>{
 assert.equal(inventions.length,14);
 assert.equal(new Set(inventions.map(item=>item.id)).size,14);
 assert.equal(new Set(inventions.map(item=>item.motif)).size,14);
 assert.equal((html.match(/<symbol id="sk-/g)||[]).length,14);
 assert.deepEqual(inventions.map(item=>item.year),[...inventions.map(item=>item.year)].sort((a,b)=>a-b));
 for(const item of inventions){
  assert.match(item.id,/^[a-z0-9-]+$/);
  for(const field of ['when','short','phase','region','label','title','problem','solution','impact','careful','source','url','motif'])assert.ok(item[field],item.id+": "+field);
  assert.ok(html.includes('id="sk-'+item.motif+'"'),"SVG ausente: "+item.motif);
  assert.match(item.url,/^https:\/\//);
 }
 assert.deepEqual([...new Set(inventions.map(x=>x.phase))].sort(),['conhecimento','digital','industrial','origens']);
});

test('catálogo preserva os três primeiros exemplos e admite novas timelines',()=>{
 assert.deepEqual(manifest.examples.slice(0,3).map(x=>x.id),['historia-do-mundo','corrida-espacial','grandes-invencoes-da-humanidade']);
 const last=manifest.examples[2];
 assert.equal(last.path,'timeline/grandes-invencoes-da-humanidade.html');
 assert.ok(last.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(last.prompt.includes('Aguarde a resposta do professor'));
 assert.equal(new Set(manifest.examples.map(x=>x.path)).size,manifest.examples.length);
});

class FakeElement{
 constructor(tag='div'){
  this.tag=tag;
  this.children=[];
  this.events={};
  this.attrs={};
  this.className='';
  this.classes=new Set();
  this.classList={
   toggle:(key,enabled)=>enabled?this.classes.add(key):this.classes.delete(key),
   remove:key=>this.classes.delete(key),
   add:key=>this.classes.add(key)
  };
  this.style={};
  this.clientWidth=400;
  this.clientHeight=70;
  this.offsetLeft=0;
  this.offsetTop=0;
  this.disabled=false;
 }
 append(...nodes){this.children.push(...nodes);}
 replaceChildren(...nodes){this.children=nodes;}
 setAttribute(key,value){this.attrs[key]=String(value);}
 addEventListener(name,callback){this.events[name]=callback;}
 querySelectorAll(selector){
  const found=this.children.filter(c=>c.tag===selector||c.className.split(' ').includes(selector.slice(1)));
  return found.concat(this.children.flatMap(c=>c.querySelectorAll(selector)));
 }
 click(){this.events.click?.({});}
 key(key){this.events.keydown?.({key,preventDefault(){}});}
 scrollTo(){}
 focus(){this.focused=true;}
}
function mount(){
 const ids=['filters','timeline','tab-problem','tab-solution','tab-impact','explanation','explanation-title','explanation-text','sheet-index','invention-region','invention-title','invention-date','invention-caveat','invention-source','drawing-use','drawing-title','drawing-desc','previous','following','progress-label','meter','meter-fill','announcer','reset','order-list','order-feedback','check-order','restart-order'];
 const dom=new Map(ids.map(id=>[id,new FakeElement()]));
 const document={
  getElementById:id=>{
   assert.ok(dom.has(id),'id de elemento ausente: '+id);
   return dom.get(id);
  },
  createElement:tag=>new FakeElement(tag)
 };
 new Function('document','window',js)(document,{matchMedia:()=>({matches:true})});
 return id=>dom.get(id);
}
test('filtros, abas, teclado e progresso funcionam sem serviços externos',()=>{
 const get=mount();
 assert.equal(get('timeline').children.length,14);
 assert.match(get('invention-title').textContent,/roda e eixo/);
 assert.equal(get('previous').disabled,true);
 get('following').click();
 assert.match(get('invention-title').textContent,/Registrar informações/);
 get('tab-solution').click();
 assert.match(get('explanation-title').textContent,/tecnologia/);
 assert.equal(get('tab-solution').attrs['aria-selected'],'true');
 get('tab-solution').key('ArrowRight');
 assert.equal(get('tab-impact').attrs['aria-selected'],'true');
 get('filters').children[2].click();
 assert.equal(get('timeline').children.length,4);
 get('timeline').children[0].key('End');
 assert.match(get('invention-title').textContent,/Produzir livros/);
 get('timeline').children[0].key('Home');
 assert.match(get('invention-title').textContent,/suporte leve/);
 get('reset').click();
 assert.equal(get('timeline').children.length,14);
 assert.equal(get('meter').attrs['aria-valuenow'],'1');
});

test('ordenação por botões gera feedback correto, aceita correção e reinicia',()=>{
 const get=mount();
 const feedback=get('order-feedback');
 const list=()=>get('order-list').children;
 assert.equal(list().length,4);
 get('check-order').click();
 assert.match(feedback.textContent,/^Ainda não/);
 // web, papel, transistor, vapor -> papel, vapor, transistor, web
 list()[1].children[2].children[0].click();
 list()[3].children[2].children[0].click();
 list()[2].children[2].children[0].click();
 list()[3].children[2].children[0].click();
 get('check-order').click();
 assert.match(feedback.textContent,/^Correto!/);
 get('restart-order').click();
 assert.equal(feedback.textContent,'Desafio reiniciado.');
 get('check-order').click();
 assert.match(feedback.textContent,/^Ainda não/);
});
