/**
 * Regressões do jogo 16 - Conecte os pontos.
 * Execute: node --test docs/pt_br/tests/connect-dots.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-conecte-os-pontos/viagem-da-agua.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O jogo precisa ter script próprio');
const start=js.indexOf('const DATA='),pure=js.indexOf('function validate(data)'),end=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pure>start&&end>pure,'As regras puras precisam estar disponíveis');
const source=js.slice(start,js.indexOf('const root=',start))+js.slice(pure,end);
const {DATA,validate,initialState,validState,transition}=
 new Function(source+'return {DATA,validate,initialState,validState,transition};')();
const act=(state,type,id)=>transition(state,{type,id},DATA);

test('HTML independente, responsivo, acessível e com SVG original incorporado',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.match(html,/<svg id="map"[^>]+viewBox="0 0 1000 650"/);
 assert.match(html,/role="group" aria-label="Mapa de oito pontos/);
 assert.match(html,/id="point-list"[^>]+aria-label/);
 assert.match(html,/@media\(max-width:525px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|picture)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotThrow(()=>new Script(js));
});

test('coordenadas válidas, rota completa de oito IDs e textos explicativos',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'connect_dots');
 assert.equal(DATA.path.length,8);
 assert.equal(DATA.points.length,8);
 assert.equal(DATA.settings.allowBacktrack,true);
 assert.ok(DATA.points.every(p=>p.x>=0&&p.x<=1&&p.y>=0&&p.y<=1));
 assert.equal(new Set(DATA.path).size,8);
 assert.ok(DATA.points.every(p=>p.label&&p.description));
});

test('coordenadas inválidas, fora da área ou não numéricas são rejeitadas',()=>{
 const off=structuredClone(DATA);
 off.points[0].x=-.1;
 assert.equal(validate(off),false);
 const outside=structuredClone(DATA);
 outside.points[1].y=1.4;
 assert.equal(validate(outside),false);
 const text=structuredClone(DATA);
 text.points[2].x='0.2';
 assert.equal(validate(text),false);
 const nonfinite=structuredClone(DATA);
 nonfinite.points[3].x=Number.POSITIVE_INFINITY;
 assert.equal(validate(nonfinite),false);
});

test('pontos muito próximos, IDs duplicados e rótulos vazios são inválidos',()=>{
 const overlapping=structuredClone(DATA);
 overlapping.points[1].x=overlapping.points[0].x+.001;
 overlapping.points[1].y=overlapping.points[0].y+.001;
 assert.equal(validate(overlapping),false);
 const duplicate=structuredClone(DATA);
 duplicate.points[1].id=duplicate.points[0].id;
 assert.equal(validate(duplicate),false);
 const empty=structuredClone(DATA);
 empty.points[1].label=' ';
 assert.equal(validate(empty),false);
 const repeatedName=structuredClone(DATA);
 repeatedName.points[1].label=repeatedName.points[0].label;
 assert.equal(validate(repeatedName),false);
});

test('rota com ponto desconhecido, ponto repetido ou ponto ausente não é aceita',()=>{
 const unknown=structuredClone(DATA);
 unknown.path[0]='inexistente';
 assert.equal(validate(unknown),false);
 const repeated=structuredClone(DATA);
 repeated.path[1]=repeated.path[0];
 assert.equal(validate(repeated),false);
 const short=structuredClone(DATA);
 short.path.pop();
 assert.equal(validate(short),false);
 const missing=structuredClone(DATA);
 missing.points.pop();
 assert.equal(validate(missing),false);
});

test('a sequência deve começar no primeiro ponto e proíbe avanços fora de ordem',()=>{
 const s=initialState();
 assert.equal(validState(s,DATA),true);
 const wrong=act(s,'point','rio');
 assert.equal(wrong.changed,true);
 assert.equal(wrong.kind,'warn');
 assert.equal(wrong.state.errors,1);
 assert.equal(wrong.state.path.length,0);
 assert.equal(validState(wrong.state,DATA),true);
 const correct=act(s,'point','sol');
 assert.equal(correct.changed,true);
 assert.deepEqual(correct.state.path,['sol']);
 assert.match(correct.message,/Próximo: Oceano/);
 assert.equal(validState(correct.state,DATA),true);
});

test('clique fora de um ponto não muda o estado e pontos desconhecidos não pontuam erros',()=>{
 const s=initialState();
 assert.equal(act(s,'point','background').changed,false);
 assert.equal(act(s,'point',undefined).changed,false);
 assert.deepEqual(s,initialState());
 assert.match(js,/event\.target\.closest\("\[data-point\]"\)/);
 assert.match(js,/if\(choice&&root\.contains\(choice\)\)/);
});

test('ponto repetido não causa erro ou nova linha; desfazer permite reconectar',()=>{
 let s=act(initialState(),'point','sol').state;
 const repeat=act(s,'point','sol');
 assert.equal(repeat.changed,false);
 assert.equal(repeat.state.errors,0);
 const back=act(s,'backtrack');
 assert.equal(back.changed,true);
 assert.deepEqual(back.state.path,[]);
 assert.equal(validState(back.state,DATA),true);
 s=act(back.state,'point','sol').state;
 assert.deepEqual(s.path,['sol']);
});

test('backtrack desativado e tentativa sem linha anterior são tratados',()=>{
 assert.equal(act(initialState(),'backtrack').changed,false);
 const data=structuredClone(DATA);
 data.settings.allowBacktrack=false;
 const one=transition(initialState(),{type:'point',id:'sol'},data).state;
 const blocked=transition(one,{type:'backtrack'},data);
 assert.equal(blocked.changed,false);
 assert.equal(blocked.state.path.length,1);
 assert.equal(validState(one,data),true);
});

test('dicas destacam próximo ponto, não se repetem e têm limite de três',()=>{
 let s=initialState();
 s=act(s,'point','sol').state;
 const first=act(s,'hint');
 assert.equal(first.changed,true);
 assert.deepEqual(first.state.hints,['oceano']);
 s=first.state;
 assert.equal(act(s,'hint').changed,false);
 s=act(s,'point','oceano').state;
 s=act(s,'hint').state;
 s=act(s,'point','evaporacao').state;
 s=act(s,'hint').state;
 assert.equal(s.hints.length,3);
 s=act(s,'point','condensacao').state;
 assert.equal(act(s,'hint').changed,false);
 assert.equal(validState(s,DATA),true);
});

test('todos os oito pontos produzem percurso completo e explicação final',()=>{
 let s=initialState();
 for(let i=0;i<DATA.path.length;i++){
  const step=act(s,'point',DATA.path[i]);
  assert.equal(step.changed,true);
  assert.equal(step.kind,'right');
  s=step.state;
  assert.equal(s.path.length,i+1);
  assert.equal(validState(s,DATA),true);
 }
 assert.equal(s.completed,true);
 assert.equal(s.path.length,8);
 assert.equal(act(s,'point','sol').changed,false);
 assert.equal(act(s,'backtrack').changed,false);
 assert.deepEqual(act(s,'restart').state,initialState());
});

test('estado restaurado deve ser prefixo da rota e não pode forjar conclusão',()=>{
 const reordered=initialState();
 reordered.path=['oceano'];
 assert.equal(validState(reordered,DATA),false);
 const forged=initialState();
 forged.completed=true;
 assert.equal(validState(forged,DATA),false);
 const invalidHint=initialState();
 invalidHint.hints=['inexistente'];
 assert.equal(validState(invalidHint,DATA),false);
 const dupHints=initialState();
 dupHints.hints=['sol','sol'];
 assert.equal(validState(dupHints,DATA),false);
 const invalidErrors=initialState();
 invalidErrors.errors=-1;
 assert.equal(validState(invalidErrors,DATA),false);
});

test('SVG desenha arestas a partir de IDs normalizados e mantém zoom proporcional',()=>{
 assert.match(js,/document\.createElementNS\(NS,tag\)/);
 assert.match(js,/x1:a\.x\*1000,y1:a\.y\*650,x2:b\.x\*1000,y2:b\.y\*650/);
 assert.match(js,/map\.style\.width=value\+"%"/);
 assert.match(js,/map\.style\.minWidth=700\*value\/100\+"px"/);
 assert.match(js,/!\[100,150,200\]\.includes\(value\)/);
 assert.match(html,/id="zoom-in"/);
 assert.match(html,/id="zoom-out"/);
});

test('teclado oferece Enter, Espaço, setas e controles textuais acessíveis',()=>{
 assert.match(js,/event\.key==="Enter"\|\|event\.key===" "/);
 assert.match(js,/ArrowLeft","ArrowRight","ArrowUp","ArrowDown/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(js,/button\.dataset\.point=id/);
 assert.match(js,/button\.setAttribute\("aria-label"/);
 assert.match(js,/role:"button",tabindex/);
 assert.match(html,/role="status" aria-live="polite"/);
});

test('persistência é opcional, respeita versão e não atribui nota oficial',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
});

test('mesmo exemplo em ambos os JSONs; prompt espera o assunto escolhido pelo professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-conecte-os-pontos/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='conecte-os-pontos-viagem-da-agua';
 const a=local.examples.find(x=>x.id===id),b=global.examples.find(x=>x.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/16-conecte-os-pontos\.md/);
});
