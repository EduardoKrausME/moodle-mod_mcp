/**
 * Jogo 25 - Jogo dos sete erros.
 * Execute: node --test docs/pt_br/tests/seven-errors.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-jogo-dos-sete-erros/laboratorio-seguro.html';
const html=await readFile(resolve(root,file),'utf8');
const script=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(script,'O exemplo precisa ter JavaScript inline.');
const rules=script.slice(script.indexOf('const DATA='),script.indexOf('const root='))+
 script.slice(script.indexOf('function errorById(data,id)'),script.indexOf('// End pure rules.'));
const {DATA,errorById,decoyById,distance,validRegion,validate,toNormalized,findRegion,initialState,validState,transition}=
 new Function(rules+'return {DATA,errorById,decoyById,distance,validRegion,validate,toNormalized,findRegion,initialState,validState,transition};')();
const clone=x=>structuredClone(x);
const act=(s,type,other={})=>transition(s,{type,...other},DATA);
const byPoint=(s,error)=>act(s,'tap',{point:{x:error.x,y:error.y}});
const findAll=()=>{
 let s=initialState();
 for(const e of DATA.errors)s=byPoint(s,e).state;
 return s;
};

test('HTML standalone com atividade única, sem menus, CDN, cabeçalho ou rodapé',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]*<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.doesNotMatch(html,/<img\b[^>]+src=/i);
 assert.match(html,/@media\(max-width:550px\)/);
 assert.match(html,/@media\(max-width:380px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-contrast:more/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(script));
});

test('ilustração SVG original embutida, acessível e sem recurso remoto',()=>{
 assert.match(html,/<svg id="lab-scene"[^>]+viewBox="0 0 1000 620"/);
 assert.match(html,/role="img" aria-labelledby="scene-title scene-description"/);
 assert.match(html,/<title id="scene-title">/);
 assert.match(html,/<desc id="scene-description">/);
 assert.match(DATA.image.src,/^#lab-scene$/);
 assert.ok(DATA.image.alt.length>50);
 assert.doesNotMatch(html,/<(?:image|use)\b[^>]+(?:href|xlink:href)="(?:http|\/\/)/i);
 assert.doesNotMatch(html,/<(?:audio|video|iframe|canvas)\b/i);
});

test('contrato válido exige sete erros e sete regiões em coordenadas normalizadas',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'seven_errors');
 assert.equal(DATA.settings.expectedCount,7);
 assert.equal(DATA.errors.length,7);
 assert.equal(DATA.decoys.length,6);
 assert.equal(DATA.errors.every(x=>x.x>=0&&x.x<=1&&x.y>=0&&x.y<=1&&x.radius>0),true);
 assert.ok(DATA.errors.every(e=>validRegion(e.hintRegion,e)));
 assert.equal(new Set(DATA.errors.map(x=>x.id)).size,7);
});

test('somente seis erros é inválido, mesmo sem alterar expectedCount',()=>{
 const data=clone(DATA);
 data.errors.pop();
 assert.equal(data.errors.length,6);
 assert.equal(validate(data),false);
});

test('oito erros são recusados em vez de serem descartados silenciosamente',()=>{
 const data=clone(DATA);
 const added=clone(data.errors[0]);
 added.id='e-oitavo';added.x=.72;added.y=.28;
 added.hintRegion={x:.67,y:.23,w:.17,h:.16};
 data.errors.push(added);
 assert.equal(data.errors.length,8);
 assert.equal(validate(data),false);
});

test('expectedCount=6 e expectedCount=8 são ambos inválidos',()=>{
 for(const number of [0,6,8,10]){
  const data=clone(DATA);
  data.settings.expectedCount=number;
  assert.equal(validate(data),false);
 }
});

test('IDs duplicados, ausentes e formatos inválidos são rejeitados',()=>{
 const duplicate=clone(DATA);
 duplicate.errors[1].id=duplicate.errors[0].id;
 assert.equal(validate(duplicate),false);
 const duplicateDecoy=clone(DATA);
 duplicateDecoy.decoys[0].id=duplicate.errors[0].id;
 assert.equal(validate(duplicateDecoy),false);
 const empty=clone(DATA);
 empty.errors[0].id='';
 assert.equal(validate(empty),false);
 const invalid=clone(DATA);
 invalid.errors[2].id='um id inválido';
 assert.equal(validate(invalid),false);
});

test('coordenadas, raio e regiões inválidos são rejeitados',()=>{
 const outside=clone(DATA);outside.errors[0].x=-.01;assert.equal(validate(outside),false);
 const right=clone(DATA);right.errors[0].x=1.01;assert.equal(validate(right),false);
 const bigRadius=clone(DATA);bigRadius.errors[1].radius=.40;assert.equal(validate(bigRadius),false);
 const zeroRadius=clone(DATA);zeroRadius.errors[1].radius=0;assert.equal(validate(zeroRadius),false);
 const invalidRegion=clone(DATA);invalidRegion.errors[0].hintRegion={x:.7,y:.8,w:.15,h:.1};assert.equal(validate(invalidRegion),false);
 const overRegion=clone(DATA);overRegion.errors[0].hintRegion={x:.2,y:.2,w:.95,h:.3};assert.equal(validate(overRegion),false);
 const nan=clone(DATA);nan.errors[0].y=NaN;assert.equal(validate(nan),false);
});

test('dois centros exatamente iguais ou indistinguíveis não são aceitos',()=>{
 const identical=clone(DATA);
 identical.errors[1].x=identical.errors[0].x;
 identical.errors[1].y=identical.errors[0].y;
 assert.equal(validate(identical),false);
 const tiny=clone(DATA);
 tiny.errors[1].x=tiny.errors[0].x+.008;
 tiny.errors[1].y=tiny.errors[0].y;
 assert.equal(validate(tiny),false);
});

test('rótulos vazios e explicações vazias não passam validação',()=>{
 const blank=clone(DATA);blank.errors[0].label=' ';assert.equal(validate(blank),false);
 const missing=clone(DATA);missing.errors[0].explanation='';assert.equal(validate(missing),false);
 const decoy=clone(DATA);decoy.decoys[0].object='';assert.equal(validate(decoy),false);
});

test('mídia não referenciada ou zoom mal configurado falha antes do jogo',()=>{
 const img=clone(DATA);img.image.src='https://site.exemplo/figura.png';assert.equal(validate(img),false);
 const alt=clone(DATA);alt.image.alt='';assert.equal(validate(alt),false);
 const levels=clone(DATA);levels.settings.zoomLevels=[1,1,2];assert.equal(validate(levels),false);
 const high=clone(DATA);high.settings.zoomLevels=[1,5];assert.equal(validate(high),false);
 const noZoom=clone(DATA);noZoom.settings.zoomLevels=[1];assert.equal(validate(noZoom),false);
});

test('estado inicial é mínimo, determinístico e incompleto',()=>{
 const s=initialState();
 assert.deepEqual(s,{found:[],tried:[],hints:0,highlight:null,complete:false});
 assert.equal(validState(s,DATA),true);
});

test('primeiro acerto por coordenadas identifica um e só um erro',()=>{
 const e=DATA.errors[0],s=initialState(),result=byPoint(s,e);
 assert.equal(result.changed,true);
 assert.equal(result.kind,'good');
 assert.deepEqual(result.state.found,[e.id]);
 assert.equal(result.state.complete,false);
 assert.equal(validState(result.state,DATA),true);
});

test('clique repetido exatamente sobre um acerto é idempotente',()=>{
 const e=DATA.errors[0],once=byPoint(initialState(),e).state;
 const again=byPoint(once,e);
 assert.equal(again.changed,false);
 assert.deepEqual(again.state,once);
 assert.equal(again.state.found.length,1);
 assert.match(again.message,/Já identificado/);
});

test('clicar fora da ilustração ou longe de região não conta erros',()=>{
 const s=initialState();
 for(const point of [null,{x:-.1,y:.3},{x:1.2,y:.2},{x:.02,y:.02},{x:.75,y:.05}]){
  const result=act(s,'tap',{point});
  assert.equal(result.changed,false);
  assert.deepEqual(result.state,s);
 }
});

test('toNormalized converte coordenadas em área real sem incluir margens',()=>{
 const rect={left:100,top:200,width:500,height:310};
 assert.deepEqual(toNormalized(350,355,rect),{x:.5,y:.5});
 assert.equal(toNormalized(99,355,rect),null);
 assert.equal(toNormalized(601,355,rect),null);
 assert.equal(toNormalized(350,199,rect),null);
 assert.equal(toNormalized(350,511,rect),null);
 assert.equal(toNormalized(100,200,rect).x,0);
 assert.equal(toNormalized(600,510,rect).y,1);
});

test('zoom 100%, 150% e 200% conservam o mesmo ponto lógico',()=>{
 const spot=DATA.errors[3];
 const base={left:40,top:90,width:500,height:310};
 for(const zoom of DATA.settings.zoomLevels){
  const rect={...base,width:base.width*zoom,height:base.height*zoom};
  const screen={x:rect.left+spot.x*rect.width,y:rect.top+spot.y*rect.height};
  const point=toNormalized(screen.x,screen.y,rect);
  assert.ok(point);
  assert.ok(Math.abs(point.x-spot.x)<1e-12);
  assert.ok(Math.abs(point.y-spot.y)<1e-12);
  assert.equal(findRegion(DATA,initialState(),point).id,spot.id);
 }
});

test('imagem ampliada com rolagem não desloca hotspots',()=>{
 const e=DATA.errors[5];
 // getBoundingClientRect já embute deslocamento causado por scroll.
 const rect={left:-233,top:-85,width:1000,height:620};
 const p=toNormalized(rect.left+e.x*rect.width,rect.top+e.y*rect.height,rect);
 assert.equal(findRegion(DATA,initialState(),p).id,e.id);
});

test('duas regiões sobrepostas escolhem a não encontrada mais próxima',()=>{
 const data=clone(DATA);
 data.errors[1].x=data.errors[0].x+.032;
 data.errors[1].y=data.errors[0].y;
 data.errors[1].hintRegion={x:.26,y:.24,w:.20,h:.23};
 assert.equal(validate(data),true);
 let s=initialState();
 assert.equal(findRegion(data,s,{x:data.errors[0].x,y:data.errors[0].y}).id,data.errors[0].id);
 s=transition(s,{type:'inspect',id:data.errors[0].id},data).state;
 const next=findRegion(data,s,{x:data.errors[1].x,y:data.errors[1].y});
 assert.deepEqual(next,{kind:'error',id:data.errors[1].id});
 const found=transition(s,{type:'tap',point:{x:data.errors[1].x,y:data.errors[1].y}},data);
 assert.equal(found.changed,true);
 assert.equal(found.state.found.length,2);
});

test('clique duplicado no centro encontrado não captura outra região sobreposta',()=>{
 const data=clone(DATA);
 data.errors[1].x=data.errors[0].x+.032;
 data.errors[1].y=data.errors[0].y;
 data.errors[1].hintRegion={x:.26,y:.24,w:.20,h:.23};
 let s=transition(initialState(),{type:'inspect',id:data.errors[0].id},data).state;
 const overlap=findRegion(data,s,{x:data.errors[0].x,y:data.errors[0].y});
 assert.equal(overlap.kind,'already');
 const out=transition(s,{type:'tap',point:{x:data.errors[0].x,y:data.errors[0].y}},data);
 assert.equal(out.changed,false);
 assert.equal(out.state.found.length,1);
});

test('alternativa textual ativa a mesma transição de acerto da imagem',()=>{
 const e=DATA.errors[1],byList=act(initialState(),'inspect',{id:e.id});
 const byImage=byPoint(initialState(),e);
 assert.equal(byList.changed,true);
 assert.deepEqual(byList.state,byImage.state);
 assert.ok(byList.message.includes(e.explanation));
});

test('objeto seguro é explicado, sem simular a descoberta de um dos sete erros',()=>{
 const d=DATA.decoys[0],out=act(initialState(),'inspect',{id:d.id});
 assert.equal(out.changed,true);
 assert.equal(out.state.found.length,0);
 assert.deepEqual(out.state.tried,[d.id]);
 assert.equal(out.state.complete,false);
 assert.ok(out.message.includes(d.explanation));
 assert.equal(validState(out.state,DATA),true);
});

test('objeto seguro repetido não duplica contagem nem tentativas',()=>{
 const d=DATA.decoys[0],s=act(initialState(),'inspect',{id:d.id}).state;
 const again=act(s,'inspect',{id:d.id});
 assert.equal(again.changed,false);
 assert.deepEqual(again.state,s);
 assert.equal(s.tried.length,1);
});

test('todos os seis objetos corretos podem ser inspecionados sem concluir',()=>{
 let s=initialState();
 for(const d of DATA.decoys)s=act(s,'inspect',{id:d.id}).state;
 assert.equal(s.tried.length,6);
 assert.equal(s.found.length,0);
 assert.equal(s.complete,false);
 assert.equal(validState(s,DATA),true);
});

test('objeto inexistente não altera progresso ou contagem',()=>{
 const s=initialState(),out=act(s,'inspect',{id:'outro-objeto'});
 assert.equal(out.changed,false);
 assert.deepEqual(out.state,s);
});

test('dica destaca um setor e não revela nem registra o erro',()=>{
 const s=initialState(),out=act(s,'hint');
 assert.equal(out.changed,true);
 assert.equal(out.persist,true);
 assert.equal(out.kind,'neutral');
 assert.equal(out.state.hints,1);
 assert.equal(out.state.found.length,0);
 assert.equal(out.state.complete,false);
 assert.equal(out.state.highlight,DATA.errors[0].id);
 assert.ok(out.message.includes(DATA.errors[0].sector));
 assert.match(out.message,/nenhum erro foi registrado/);
});

test('dicas subsequentes escolhem setores ainda não encontrados',()=>{
 let s=initialState();
 s=act(s,'inspect',{id:DATA.errors[0].id}).state;
 const out=act(s,'hint');
 assert.notEqual(out.state.highlight,DATA.errors[0].id);
 assert.equal(out.state.found.length,1);
 assert.equal(validState(out.state,DATA),true);
});

test('encontrar região destacada remove realce sem perder histórico de dicas',()=>{
 let s=act(initialState(),'hint').state;
 const id=s.highlight;
 s=act(s,'inspect',{id}).state;
 assert.equal(s.highlight,null);
 assert.equal(s.hints,1);
 assert.equal(s.found.length,1);
 assert.equal(validState(s,DATA),true);
});

test('não é possível ultrapassar limite de dicas',()=>{
 let s=initialState();
 for(let i=0;i<DATA.settings.maxHints;i++)s=act(s,'hint').state;
 assert.equal(s.hints,7);
 const again=act(s,'hint');
 assert.equal(again.changed,false);
 assert.equal(again.state.hints,7);
 assert.equal(validState(s,DATA),true);
});

test('somente sétima identificação válida conclui o jogo',()=>{
 let s=initialState();
 for(let i=0;i<6;i++){
  s=act(s,'inspect',{id:DATA.errors[i].id}).state;
  assert.equal(s.complete,false);
  assert.equal(s.found.length,i+1);
 }
 const last=act(s,'inspect',{id:DATA.errors[6].id});
 assert.equal(last.changed,true);
 assert.equal(last.state.complete,true);
 assert.equal(last.state.found.length,7);
 assert.equal(validState(last.state,DATA),true);
});

test('após a conclusão os sete IDs e explicações continuam revisáveis',()=>{
 const s=findAll();
 const e=DATA.errors[3],review=act(s,'inspect',{id:e.id});
 assert.equal(review.changed,false);
 assert.ok(review.message.includes(e.explanation));
 assert.deepEqual(review.state,s);
 assert.equal(act(s,'hint').changed,false);
 assert.equal(act(s,'inspect',{id:DATA.decoys[0].id}).changed,false);
 assert.equal(s.complete,true);
});

test('progresso restaurado com seis achados pode receber o sétimo uma vez',()=>{
 let s=initialState();
 for(let i=0;i<6;i++)s=act(s,'inspect',{id:DATA.errors[i].id}).state;
 const recovered=JSON.parse(JSON.stringify(s));
 assert.equal(validState(recovered,DATA),true);
 const last=act(recovered,'inspect',{id:DATA.errors[6].id}).state;
 assert.equal(last.complete,true);
 assert.equal(validState(last,DATA),true);
});

test('estado persistido mantém ID, dicas e objetos após JSON round-trip',()=>{
 let s=initialState();
 s=act(s,'inspect',{id:DATA.errors[0].id}).state;
 s=act(s,'inspect',{id:DATA.decoys[1].id}).state;
 s=act(s,'hint').state;
 const recovered=JSON.parse(JSON.stringify(s));
 assert.deepEqual(recovered,s);
 assert.equal(validState(recovered,DATA),true);
});

test('estado adulterado com IDs inexistentes, repetidos ou conclusão falsa é rejeitado',()=>{
 const unknown=initialState();unknown.found=['e-fantasma'];assert.equal(validState(unknown,DATA),false);
 const duplicate=initialState();duplicate.found=[DATA.errors[0].id,DATA.errors[0].id];assert.equal(validState(duplicate,DATA),false);
 const badDecoy=initialState();badDecoy.tried=['e-fantasma'];assert.equal(validState(badDecoy,DATA),false);
 const duplicateDecoy=initialState();duplicateDecoy.tried=[DATA.decoys[0].id,DATA.decoys[0].id];assert.equal(validState(duplicateDecoy,DATA),false);
 const forced=initialState();forced.complete=true;assert.equal(validState(forced,DATA),false);
 const falseIncomplete=findAll();falseIncomplete.complete=false;assert.equal(validState(falseIncomplete,DATA),false);
});

test('dica acima do limite ou apontando para erro encontrado é rejeitada',()=>{
 const extra=initialState();extra.hints=8;assert.equal(validState(extra,DATA),false);
 const unknown=initialState();unknown.highlight='outro';assert.equal(validState(unknown,DATA),false);
 const found=act(initialState(),'inspect',{id:DATA.errors[0].id}).state;found.highlight=DATA.errors[0].id;
 assert.equal(validState(found,DATA),false);
});

test('reiniciar limpa acertos, objetos inspecionados e dicas',()=>{
 let s=initialState();
 s=act(s,'inspect',{id:DATA.errors[0].id}).state;
 s=act(s,'inspect',{id:DATA.decoys[0].id}).state;
 s=act(s,'hint').state;
 const reset=act(s,'restart');
 assert.equal(reset.changed,true);
 assert.deepEqual(reset.state,initialState());
 assert.equal(validState(reset.state,DATA),true);
 assert.deepEqual(act(findAll(),'restart').state,initialState());
});

test('interface permite zoom independente do progresso e cálculo por retângulo real',()=>{
 assert.match(script,/function changeZoom\(delta\)/);
 assert.match(script,/rect=svg\.getBoundingClientRect\(\)/);
 assert.match(script,/toNormalized\(event\.clientX,event\.clientY,rect\)/);
 assert.match(script,/zoom\*100/);
 assert.match(script,/style\.width=\(zoom\*100\)\+"%"/);
 assert.match(html,/id="zoom-in"/);
 assert.match(html,/id="zoom-out"/);
 assert.match(html,/touch-action:pan-x pan-y/);
 assert.match(html,/aspect-ratio:1000\/620/);
 assert.match(html,/overflow:auto/);
});

test('acessibilidade oferece inspeção em texto sem reconhecimento de imagem',()=>{
 assert.match(html,/id="inspection"[^>]+role="group"/);
 assert.match(html,/id="scene-viewport"[^>]+role="region"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="completion"[^>]+tabindex="-1"/);
 assert.match(html,/id="found-list"/);
 assert.match(script,/function buildOptions\(/);
 assert.match(script,/function renderOptions\(/);
 assert.match(script,/setAttribute\("aria-label","Inspecionar "/);
 assert.match(script,/setAttribute\("aria-pressed"/);
 assert.match(script,/document\.createElement\(tag\)/);
 assert.match(script,/el\.textContent=text/);
 assert.doesNotMatch(script,/\.innerHTML\s*=/);
});

test('duplo clique na região já descoberta não cria pontos, timers ou eventos duplicados',()=>{
 let s=initialState();
 const e=DATA.errors[0];
 const first=byPoint(s,e);s=first.state;
 const second=byPoint(s,e);
 assert.equal(first.changed,true);
 assert.equal(second.changed,false);
 assert.equal(s.found.length,1);
 assert.doesNotMatch(script,/addEventListener\("pointerup"/);
 assert.doesNotMatch(script,/setInterval\(|setTimeout\(/);
});

test('nenhuma requisição externa, áudio ou ação de conclusão acadêmica',()=>{
 assert.doesNotMatch(script,/\bfetch\(|XMLHttpRequest|WebSocket|navigator\.sendBeacon/);
 assert.doesNotMatch(script,/\beval\(|\bnew Function\(/);
 assert.doesNotMatch(script,/completion\.complete\(/);
 assert.doesNotMatch(script,/attempt\.finish\(/);
 assert.doesNotMatch(html,/<audio\b|<video\b|<iframe\b/);
 assert.match(html,/exclusivamente|somente formativa|somente formativo|sem nota oficial/i);
});

test('salvamento opcional é versionado e limitado a 12 KiB',()=>{
 assert.match(script,/function getState\(/);
 assert.match(script,/function restoreState\(/);
 assert.match(script,/function destroy\(/);
 assert.match(script,/function handleAction\(/);
 assert.match(script,/progress\.load\(/);
 assert.match(script,/progress\.save\(/);
 assert.match(script,/version:remote\.version/);
 assert.match(script,/TextEncoder/);
 assert.match(script,/12288/);
 assert.match(script,/controller\.abort\(\)/);
});

test('reinício com progresso exige confirmação explícita',()=>{
 assert.match(html,/id="reset-confirm"/);
 assert.match(html,/id="confirm-reset"/);
 assert.match(html,/id="cancel-reset"/);
 assert.match(script,/function askRestart\(/);
 assert.match(script,/Reinício cancelado/);
 assert.match(script,/confirm-reset/);
});

test('catálogos iguais contêm prompt que pergunta e aguarda o professor',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-jogo-dos-sete-erros/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='jogo-dos-sete-erros-laboratorio-seguro',entry=local.examples.find(x=>x.id===id),other=global.examples.find(x=>x.id===id);
 assert.ok(entry&&other);
 assert.deepEqual(entry,other);
 assert.equal(entry.path,file);
 assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(entry.prompt,/Aguarde a resposta/);
 assert.match(entry.prompt,/\.chatgpt\/games\/25-jogo-dos-sete-erros\.md/);
 assert.match(entry.prompt,/\.chatgpt\/games\/RULES\.md/);
});
