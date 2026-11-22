/**
 * Static gallery checks. Run: node --test docs/pt-BR/tests/catalog.test.mjs
 * No third-party dependencies are required.
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {join,dirname,resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';
const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const read=async path=>readFile(join(root,path),'utf8');
const json=async path=>JSON.parse(await read(path));
const allowPath=/^(page|quiz|survey|timeline|case|simulation|custom|games)\/[a-z0-9-]+\.html$/;

test('o catálogo tem exatamente os sete modos do Mod MCP e Games',async()=>{
 const c=await json('catalog.json');
 assert.equal(c.version,1);
 assert.deepEqual(c.categories.map(x=>x.id),['page','quiz','survey','timeline','case','simulation','custom','games']);
 for(const cat of c.categories)assert.equal(cat.manifest,cat.id+'/examples.json');
});

test('cada manifesto tem HTML próprio, identificadores únicos e descrição',async()=>{
 const c=await json('catalog.json'),all=new Set();let total=0;
 for(const category of c.categories){
  const m=await json(category.manifest);
  assert.equal(m.version,1);
  assert.equal(m.category,category.id);
  assert.ok(m.description);
  assert.ok(m.examples.length>=4);
  const local=new Set();
  for(const e of m.examples){
   assert.match(e.id,/^[a-z0-9-]+$/);
   assert.ok(!local.has(e.id),'ID repetido '+e.id);
   local.add(e.id);
   assert.equal(e.path,category.id+'/'+e.id+'.html');
   assert.match(e.path,allowPath);
   assert.ok(e.title&&e.description&&e.howItWorks);
   assert.ok(Number.isInteger(e.minutes)&&e.minutes>0);
   assert.ok(Array.isArray(e.features)&&e.features.length>0);
   if(category.id==='games')assert.ok(e.subtype);
   assert.ok(!all.has(e.path),'Caminho duplicado '+e.path);
   all.add(e.path);
   const html=await read(e.path);
   assert.match(html,/^<!doctype html>/i,e.path);
   assert.match(html,/<html lang="pt-BR">/,e.path);
   assert.match(html,/<style>[\s\S]+<\/style>/,e.path);
   assert.match(html,/<script>[\s\S]+<\/script>/,e.path);
   assert.doesNotMatch(html,/<footer\b/i,e.path);
   assert.doesNotMatch(html,/<script[^>]+src=/i,e.path);
   const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
   assert.equal(scripts.length,1,'um script inline em '+e.path);
   assert.doesNotThrow(()=>new Script(scripts[0][1],{filename:e.path}),e.path);
   total++;
  }
 }
 assert.ok(total>=50,'a galeria deve manter dezenas de exemplos');
});

test('o site oferece navegação por JSON, isolamento dos iframes e versão reduzida',async()=>{
 const html=await read('index.html');
 const js=await read('app.js');
 assert.match(html,/id="category-nav"/);
 assert.match(html,/id="preview-dialog"/);
 assert.match(html,/sandbox="allow-scripts"/);
 assert.doesNotMatch(html,/allow-same-origin/);
 assert.match(js,/fetch\(path/);
 assert.match(js,/ResizeObserver/);
 assert.match(js,/IntersectionObserver/);
 assert.match(js,/preview-scale/);
 assert.match(js,/new Option/);
 assert.doesNotThrow(()=>new Script(js,{filename:'app.js'}));
});
