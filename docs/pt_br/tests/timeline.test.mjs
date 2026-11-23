import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(fileURLToPath(new URL('..',import.meta.url)));
const html=await readFile(resolve(root,'timeline/historia-do-mundo.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(root,'timeline/examples.json'),'utf8'));
const embedded=html.match(/const milestones = (\[[^\n]+\]);/);
const milestones=JSON.parse(embedded?.[1]||'null');

test('timeline preserva 12 marcos em ordem, com representações próprias e conteúdo contextual',()=>{
  assert.equal(milestones.length,12);
  assert.deepEqual(milestones.map(x=>x.sort),[...milestones.map(x=>x.sort)].sort((a,b)=>a-b));
  assert.equal(new Set(milestones.map(x=>x.short)).size,12);
  assert.equal(new Set(milestones.map(x=>x.art)).size,12);
  for(const item of milestones){
    for(const field of ['date','region','short','title','subtitle','summary','impact','nuance','art','color'])assert.ok(item[field],field);
    assert.ok(html.includes('id="art-'+item.art+'"'),item.art);
  }
});

test('controles acessíveis, feedback, reinício e JavaScript válido',()=>{
  for(const id of ['track','next','previous','reset','questions','announcement','meter'])assert.match(html,new RegExp('id="'+id+'"'));
  assert.match(html,/aria-live="polite"/);
  assert.match(html,/prefers-reduced-motion/);
  assert.match(html,/ArrowRight/);
  assert.match(html,/ArrowLeft/);
  assert.match(html,/Home/);
  assert.match(html,/End/);
  assert.doesNotMatch(html,/<script[^>]+src=/i);
  assert.doesNotMatch(html,/<footer\b/i);
  const scripts=[...html.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/gi)];
  assert.equal(scripts.length,1);
  assert.doesNotThrow(()=>new Script(scripts[0][1]));
});

test('manifesto aponta para atividade e preserva a pergunta inicial do professor',()=>{
  const entry=manifest.examples.find(e=>e.id==='historia-do-mundo');
  assert.equal(entry.path,'timeline/historia-do-mundo.html');
  assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
  assert.match(entry.prompt,/Aguarde a resposta/);
});
