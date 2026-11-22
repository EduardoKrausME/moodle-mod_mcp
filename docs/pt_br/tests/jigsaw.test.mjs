/**
 * Quebra-cabeça de imagens: regressões do exemplo standalone.
 * Execução: node --test docs/pt-BR/tests/jigsaw.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const docs=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const html=await readFile(resolve(docs,'games/quebra-cabeca-de-imagens.html'),'utf8');
const manifest=JSON.parse(await readFile(resolve(docs,'games/examples.json'),'utf8'));
const match=html.match(/<script>([\s\S]*?)<\/script>/i);
assert.ok(match,'exemplo precisa conter JavaScript embutido');
const script=match[1];
const section=(from,to)=>{
  const start=script.indexOf(from);
  const end=script.indexOf(to,start+from.length);
  assert.ok(start>=0&&end>start,'função ausente: '+from);
  return script.slice(start,end);
};
const pure=(name,from,to)=>new Function(section(from,to)+'return '+name+';')();
const validate=pure('validate','function validate(data){','function isPermutation(');
const isPermutation=pure('isPermutation','function isPermutation(values,size){','function isSolved()');
const shuffle=pure('shuffleIndexes','function shuffleIndexes(count){','function background(');
const config=()=>({
  type:'jigsaw',
  image:{width:601,height:599},
  settings:{mode:'swap',rows:3,cols:3,availableSizes:[2,3,4,5]}
});

test('a página contém somente a atividade, ilustração SVG incorporada e script standalone',()=>{
  assert.match(html,/^<!doctype html>/i);
  assert.match(html,/<html lang="pt-BR">/);
  assert.doesNotMatch(html,/<(?:nav|header|footer)\b/i);
  assert.doesNotMatch(html,/<script[^>]+src=/i);
  assert.doesNotMatch(html,/<img[^>]+src="https?:/i);
  assert.match(html,/<template id="picture-source"><svg/);
  assert.match(html,/<svg[^>]+width="601" height="599"/);
  assert.match(html,/id="reference-image"[^>]+alt=/);
  assert.doesNotThrow(()=>new Script(script));
});

test('o jogo cobre grades 2×2, 3×3, 4×4 e 5×5 sem iniciar resolvido',()=>{
  for(const level of [2,3,4,5]){
    const data=config();
    data.settings.rows=level;
    data.settings.cols=level;
    assert.equal(validate(data),true,'configuração válida '+level);
    for(let i=0;i<200;i++){
      const pieces=shuffle(level*level);
      assert.equal(isPermutation(pieces,level*level),true);
      assert.equal(pieces.every((value,index)=>value===index),false);
    }
    assert.match(html,new RegExp('<option value="'+level+'"'));
  }
});

test('configurações e permutações inválidas não são aceitas',()=>{
  const wrong=config();wrong.settings.cols=4;
  assert.equal(validate(wrong),false);
  wrong.settings.cols=3;wrong.image.width=0;
  assert.equal(validate(wrong),false);
  assert.equal(isPermutation([0,1,1,3],4),false);
  assert.equal(isPermutation([0,1,2,5],4),false);
  assert.equal(isPermutation([0,1,2],4),false);
});

test('o JSON da galeria registra o exemplo e documenta o uso de imagem',()=>{
  const entry=manifest.examples.find(example=>example.id==='quebra-cabeca-de-imagens');
  assert.ok(entry,'exemplo não cadastrado');
  assert.equal(entry.path,'games/quebra-cabeca-de-imagens.html');
  assert.ok(entry.description&&entry.howItWorks&&entry.subtype);
  assert.ok(entry.features.some(feature=>/SVG/.test(feature)),'imagem SVG deve estar documentada');
});

test('a interface disponibiliza troca, arraste, desfazer e retomada opcional',()=>{
  for(const marker of ['id="board"','id="undo"','id="restart"','id="show-numbers"','id="level"','id="reference-image"']){
    assert.ok(html.includes(marker),marker);
  }
  assert.match(script,/function swap\(/);
  assert.match(script,/function startPointer\(/);
  assert.match(script,/function movePointer\(/);
  assert.match(script,/function endPointer\(/);
  assert.match(script,/function getState\(/);
  assert.match(script,/function restoreState\(/);
  assert.match(script,/service\.save\(/);
});
