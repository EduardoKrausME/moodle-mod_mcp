/**
 * Regressões do jogo 06: Encontrar as diferenças.
 * Execute: node --test docs/pt_br/tests/spot-difference.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve, dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(root,'game-encontre-as-diferencas/seguranca-no-laboratorio.html'),'utf8');
const local=JSON.parse(await readFile(resolve(root,'game-encontre-as-diferencas/examples.json'),'utf8'));
const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
const source=html.match(/<script>([\s\S]*?)<\/script>/i)?.[1];
assert.ok(source,'um script inline é necessário');
const section=(before,after)=>{
 const start=source.indexOf(before);
 const end=source.indexOf(after,start+before.length);
 assert.ok(start>=0&&end>start,'não foi possível localizar '+before);
 return source.slice(start,end);
};
const cfg=new Function(section('const cfg={',' const $=')+'return cfg;')();
const validate=new Function(section('function validate(data){',' function makeScene')+'return validate;')();
const makeScene=new Function(section('function makeScene(altered){',' function imageUri')+'return makeScene;')();
const {pointFromEvent,nearest}=new Function('cfg',
 section('function markPosition(img,x,y){',' function getState')+'return {pointFromEvent,nearest};')(cfg);

test('HTML contém somente atividade e duas cenas SVG geradas localmente',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity"/);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<img[^>]+src="https?:/i);
 assert.match(html,/id="image-before"[^>]+alt=/);
 assert.match(html,/id="image-after"[^>]+alt=/);
 assert.match(html,/id="regions" class="regions"/);
 assert.doesNotThrow(()=>new Script(source));
});

test('cinco diferenças distintas e sem sobreposição perigosa',()=>{
 assert.equal(validate(cfg),true);
 assert.equal(cfg.type,'spot_difference');
 assert.equal(cfg.differences.length,5);
 assert.equal(new Set(cfg.differences.map(item=>item.id)).size,5);
 const duplicate=JSON.parse(JSON.stringify(cfg));
 duplicate.differences[1].x=duplicate.differences[0].x;
 duplicate.differences[1].y=duplicate.differences[0].y;
 assert.equal(validate(duplicate),false);
 const outside=JSON.parse(JSON.stringify(cfg));
 outside.differences[0].x=1.1;
 assert.equal(validate(outside),false);
});

test('cenas compartilham tamanho e possuem diferenças reais',()=>{
 const sceneA=makeScene(false);
 const sceneB=makeScene(true);
 for(const scene of [sceneA,sceneB]){
  assert.match(scene,/<svg xmlns="http:\/\/www\.w3\.org\/2000\/svg" width="720" height="450" viewBox="0 0 720 450">/);
  assert.match(scene,/<\/svg>$/);
 }
 assert.notEqual(sceneA,sceneB);
 // Verifica se todas as cinco áreas mudaram entre as versões.
 for(const part of ['eyes','gloves','bottleLabel','sign','bin']){
  assert.ok(source.includes('const '+part+'=altered?'),'alteração visual ausente: '+part);
 }
});

test('toques funcionam com zoom, redimensionamento e margens de object-fit',()=>{
 for(const z of cfg.differences){
  const image={
   getBoundingClientRect:()=>({left:30,top:15,width:720,height:450})
  };
  const p=pointFromEvent(image,30+z.x*720,15+z.y*450);
  assert.ok(p);
  assert.equal(nearest(p)?.id,z.id);
  const compact={
   getBoundingClientRect:()=>({left:100,top:50,width:400,height:225})
  };
  const p2=pointFromEvent(compact,100+20+z.x*360,50+z.y*225);
  assert.equal(nearest(p2)?.id,z.id);
  assert.equal(pointFromEvent(compact,101,51),null,'margem não pode contar');
 }
 const image={getBoundingClientRect:()=>({left:0,top:0,width:720,height:450})};
 assert.equal(nearest(pointFromEvent(image,20,410)),null);
});

test('repetição, alternativa acessível e salvamento limitado a estado de jogo',()=>{
 assert.match(source,/if\(found\.has\(zone\.id\)\)/);
 assert.match(source,/button\.dataset\.region/);
 assert.match(source,/function getState\(/);
 assert.match(source,/function restoreState\(/);
 assert.match(source,/progress\.load\(/);
 assert.match(source,/progress\.save\(/);
 assert.match(source,/\.scene-hit"\)\)button\.disabled=false/);
 assert.match(html,/id="mode-alternate"/);
 assert.match(html,/id="restart"/);
 assert.match(html,/id="hint"/);
 assert.match(html,/id="progress"/);
 assert.doesNotMatch(source,/MoodleMCP\.completion\.complete\(/);
});

test('ambos os manifestos possuem exatamente o mesmo exemplo e prompt',()=>{
 const id='diferencas-seguranca-no-laboratorio';
 const a=local.examples.find(x=>x.id===id);
 const b=global.examples.find(x=>x.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,'game-encontre-as-diferencas/seguranca-no-laboratorio.html');
 assert.ok(a.prompt.includes('Qual tipo de conteúdo você deseja trabalhar'));
 assert.ok(a.prompt.includes('Aguarde a resposta'));
});
