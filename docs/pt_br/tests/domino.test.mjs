/**
 * Jogo 24 - Dominó educativo.
 * Execução: node --test docs/pt_br/tests/domino.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-domino-educativo/ciclo-da-agua.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O HTML precisa conter um script inline.');
const start=js.indexOf('const DATA='),pureStart=js.indexOf('function tileById(data,tileId)'),pureEnd=js.indexOf('// End pure rules.');
assert.ok(start>=0&&pureStart>start&&pureEnd>pureStart,'As regras puras devem estar isoladas.');
const pure=js.slice(start,js.indexOf('const root=',start))+js.slice(pureStart,pureEnd);
const {DATA,tileById,oriented,ends,chainOf,hasSolution,validate,hashSeed,shuffled,bankOrder,initialState,validState,transition}=
 new Function(pure+'return {DATA,tileById,oriented,ends,chainOf,hasSolution,validate,hashSeed,shuffled,bankOrder,initialState,validState,transition};')();
const clone=x=>structuredClone(x);
const act=(state,type,other={})=>transition(state,{type,...other},DATA);
const take=(state,id)=>act(state,'select',{id}).state;
const full=()=>{
 let s=initialState();
 for(const id of ['evaporacao-condensacao','condensacao-precipitacao','precipitacao-infiltracao','infiltracao-aquifero','aquifero-nascente','nascente-escoamento','escoamento-solar']){
  s=take(s,id);
  if(id==='infiltracao-aquifero')s=act(s,'flip').state;
  const out=act(s,'place',{side:'right'});
  assert.equal(out.changed,true,'Encaixe '+id);
  s=out.state;
 }
 return s;
};
const ambiguity=()=>{
 const endpoint=(id,label)=>({id,label});
 const tiles=[
  ['a','a','b'],['b-c','b','c'],['c-b','c','b'],
  ['b-d','b','d'],['d-b','d','b'],['b-a','b','a']
 ].map(([id,left,right])=>({id,left:endpoint(left,'Conteúdo '+left),right:endpoint(right,'Definição '+right)}));
 return {type:'domino',title:'Exemplo com escolhas equivalentes',starter:'a',settings:{allowFlip:true,requireCycle:true,seed:'ambiguo',maxTiles:12},tiles};
};

test('HTML completo e autossuficiente, sem menus, cabeçalhos ou rodapés',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture|audio|video)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:560px\)/);
 assert.match(html,/@media\(max-width:380px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-contrast:more/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(js));
});

test('dados válidos: oito peças, duas metades e inicial fixa',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'domino');
 assert.equal(DATA.tiles.length,8);
 assert.equal(DATA.starter,'sol-evaporacao');
 assert.equal(DATA.settings.allowFlip,true);
 assert.equal(DATA.settings.requireCycle,true);
 assert.equal(DATA.tiles.every(t=>t.left.id&&t.right.id&&t.left.label&&t.right.label),true);
 assert.equal(new Set(DATA.tiles.map(t=>t.id)).size,8);
 assert.equal(DATA.tiles.find(t=>t.id==='infiltracao-aquifero').left.id,'aquifero');
});

test('contrato de peça preserva IDs semânticos e rótulos distintos',()=>{
 const first=tileById(DATA,'sol-evaporacao'),second=tileById(DATA,'evaporacao-condensacao');
 assert.equal(first.right.id,second.left.id);
 assert.notEqual(first.right.label,second.left.label);
 assert.equal(first.right.id,'evaporacao');
 assert.equal(tileById(DATA,'unknown'),null);
});

test('existe cadeia completa validada por busca de encaixes',()=>{
 assert.equal(hasSolution(DATA),true);
 const result=full();
 assert.equal(result.complete,true);
 assert.equal(validState(result,DATA),true);
 assert.equal(chainOf(DATA,result.placed).length,8);
 assert.deepEqual(ends(DATA,result.placed),{left:'solar',right:'solar'});
});

test('embaralhamento determinístico não repete IDs nem inclui peça inicial',()=>{
 const a=bankOrder(DATA),b=bankOrder(DATA);
 assert.deepEqual(a,b);
 assert.equal(a.length,7);
 assert.equal(new Set(a).size,7);
 assert.equal(a.includes(DATA.starter),false);
 assert.deepEqual([...a].sort(),DATA.tiles.filter(t=>t.id!==DATA.starter).map(t=>t.id).sort());
 assert.equal(hashSeed('mesmo'),hashSeed('mesmo'));
 assert.deepEqual(shuffled(['a','b','c','d'],'seed'),shuffled(['a','b','c','d'],'seed'));
});

test('dados rejeitam peças vazias e ID de peça repetido',()=>{
 const duplicate=clone(DATA);duplicate.tiles[2].id=duplicate.tiles[1].id;
 assert.equal(validate(duplicate),false);
 const empty=clone(DATA);empty.tiles[1].right.label='  ';
 assert.equal(validate(empty),false);
 const wrong=clone(DATA);wrong.tiles[3].left.id='com espacos';
 assert.equal(validate(wrong),false);
 const noStarter=clone(DATA);noStarter.starter='inexistente';
 assert.equal(validate(noStarter),false);
});

test('IDs semânticos são obrigatórios, não é possível validar pelo rótulo',()=>{
 const invalid=clone(DATA);
 invalid.tiles[1].left.id='outro-id';
 invalid.tiles[1].left.label=DATA.tiles[0].right.label;
 assert.equal(hasSolution(invalid),false);
 assert.equal(validate(invalid),false);
 const dangerous=clone(DATA);
 dangerous.tiles[1].left.label='<img src=x onerror=alert(1)>';
 assert.equal(validate(dangerous),true,'Texto com caracteres especiais é apenas um rótulo literal.');
});

test('grafo insolúvel é rejeitado antes de renderizar',()=>{
 const invalid=clone(DATA);
 invalid.tiles[2].left.id='isolado';
 invalid.tiles[2].right.id='outro-isolado';
 assert.equal(hasSolution(invalid),false);
 assert.equal(validate(invalid),false);
});

test('configuração inválida e poucas peças são rejeitadas',()=>{
 const noFlip=clone(DATA);noFlip.settings.allowFlip='false';
 assert.equal(validate(noFlip),false);
 const max=clone(DATA);max.settings.maxTiles=3;
 assert.equal(validate(max),false);
 const few=clone(DATA);few.tiles=few.tiles.slice(0,2);
 assert.equal(validate(few),false);
 const invalid=clone(DATA);invalid.settings.requireCycle=null;
 assert.equal(validate(invalid),false);
});

test('lembretes pedagógicos são opcionais, mas o formato é validado',()=>{
 const missing=clone(DATA);delete missing.facts;
 assert.equal(validate(missing),true);
 const malformed=clone(DATA);malformed.facts=[12];
 assert.equal(validate(malformed),false);
 const tooMany=clone(DATA);tooMany.facts=Array(13).fill('Ciência');
 assert.equal(validate(tooMany),false);
});

test('estado inicial apenas com a peça fixa, sem seleção ou progresso fabricado',()=>{
 const s=initialState();
 assert.deepEqual(s,{placed:[],selected:null,hints:0,complete:false});
 assert.deepEqual(chainOf(DATA,s.placed),[{id:DATA.starter,flipped:false}]);
 assert.equal(validState(s,DATA),true);
 assert.deepEqual(ends(DATA,s.placed),{left:'solar',right:'evaporacao'});
});

test('selecionar uma peça não a remove do estoque e não gera pontuação',()=>{
 const s=initialState();
 const result=act(s,'select',{id:'evaporacao-condensacao'});
 assert.equal(result.changed,true);
 assert.equal(result.persist,false);
 assert.deepEqual(result.state.placed,[]);
 assert.deepEqual(result.state.selected,{id:'evaporacao-condensacao',flipped:false});
 assert.equal(validState(result.state,DATA),true);
});

test('tocar duas vezes a mesma peça mantém a seleção sem duas transições',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 const repeated=act(s,'select',{id:'evaporacao-condensacao'});
 assert.equal(repeated.changed,false);
 assert.deepEqual(repeated.state,s);
});

test('uma peça já usada ou a peça inicial não podem ser selecionadas novamente',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 s=act(s,'place',{side:'right'}).state;
 assert.equal(s.placed.length,1);
 assert.equal(act(s,'select',{id:'evaporacao-condensacao'}).changed,false);
 assert.equal(act(s,'select',{id:'sol-evaporacao'}).changed,false);
 assert.equal(act(s,'select',{id:'ausente'}).changed,false);
 assert.equal(validState(s,DATA),true);
});

test('encaixe correto à direita usa apenas correspondência da ponta livre',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 const result=act(s,'place',{side:'right'});
 assert.equal(result.changed,true);
 assert.equal(result.persist,true);
 assert.deepEqual(result.state.placed,[{id:'evaporacao-condensacao',flipped:false,side:'right'}]);
 assert.deepEqual(ends(DATA,result.state.placed),{left:'solar',right:'condensacao'});
 assert.equal(result.state.selected,null);
 assert.equal(validState(result.state,DATA),true);
});

test('encaixe correto à esquerda preserva a ordem da cadeia',()=>{
 let s=take(initialState(),'escoamento-solar');
 const result=act(s,'place',{side:'left'});
 assert.equal(result.changed,true);
 assert.deepEqual(chainOf(DATA,result.state.placed).map(x=>x.id),['escoamento-solar','sol-evaporacao']);
 assert.deepEqual(ends(DATA,result.state.placed),{left:'escoamento',right:'evaporacao'});
 assert.equal(validState(result.state,DATA),true);
});

test('encaixe incompatível não consome peça ou altera cadeia',()=>{
 const before=take(initialState(),'condensacao-precipitacao');
 const result=act(before,'place',{side:'right'});
 assert.equal(result.changed,false);
 assert.equal(result.kind,'warn');
 assert.deepEqual(result.state,before);
 assert.match(result.message,/incompatível/);
 assert.equal(before.placed.length,0);
 assert.equal(validState(before,DATA),true);
});

test('encaixar no lado oposto incompatível não teletransporta a peça',()=>{
 const before=take(initialState(),'evaporacao-condensacao');
 const wrong=act(before,'place',{side:'left'});
 assert.equal(wrong.changed,false);
 assert.deepEqual(wrong.state.placed,[]);
 assert.deepEqual(wrong.state.selected,before.selected);
 assert.equal(act(before,'place',{side:'right'}).changed,true);
});

test('ação sem peça escolhida ou com lado inválido não altera nada',()=>{
 const initial=initialState();
 assert.equal(act(initial,'place',{side:'right'}).changed,false);
 const s=take(initial,'evaporacao-condensacao');
 assert.equal(act(s,'place',{side:'center'}).changed,false);
 assert.equal(act(s,'place',{side:'middle'}).changed,false);
 assert.equal(act(s,'place',{side:undefined}).changed,false);
 assert.equal(validState(s,DATA),true);
});

test('girar uma vez inverte orientações sem modificar IDs nem usar peça',()=>{
 const before=take(initialState(),'infiltracao-aquifero');
 const face=oriented(tileById(DATA,before.selected.id),false);
 const flipped=act(before,'flip');
 assert.equal(flipped.changed,true);
 assert.equal(flipped.persist,false);
 assert.equal(flipped.state.placed.length,0);
 assert.equal(flipped.state.selected.flipped,true);
 const inverted=oriented(tileById(DATA,before.selected.id),true);
 assert.deepEqual(inverted.left,face.right);
 assert.deepEqual(inverted.right,face.left);
 assert.equal(validState(flipped.state,DATA),true);
});

test('girar duas vezes recupera a orientação original',()=>{
 const before=take(initialState(),'infiltracao-aquifero');
 let state=act(before,'flip').state;
 state=act(state,'flip').state;
 assert.equal(state.selected.flipped,false);
 assert.deepEqual(state.selected,before.selected);
 assert.deepEqual(state.placed,[]);
});

test('uma peça invertida precisa do giro para encaixar no percurso correto',()=>{
 let state=initialState();
 for(const id of ['evaporacao-condensacao','condensacao-precipitacao','precipitacao-infiltracao']){
  state=take(state,id);
  state=act(state,'place',{side:'right'}).state;
 }
 state=take(state,'infiltracao-aquifero');
 const rejected=act(state,'place',{side:'right'});
 assert.equal(rejected.changed,false);
 assert.deepEqual(rejected.state,state);
 state=act(state,'flip').state;
 const accepted=act(state,'place',{side:'right'});
 assert.equal(accepted.changed,true);
 assert.equal(accepted.state.placed.at(-1).flipped,true);
 assert.equal(ends(DATA,accepted.state.placed).right,'aquifero');
 assert.equal(validState(accepted.state,DATA),true);
});

test('girar indisponível na configuração é rejeitado',()=>{
 const noFlip=clone(DATA);
 noFlip.settings.allowFlip=false;
 // Reoriente previamente a única peça invertida para manter a atividade resolúvel.
 const tile=noFlip.tiles.find(x=>x.id==='infiltracao-aquifero');
 [tile.left,tile.right]=[tile.right,tile.left];
 assert.equal(validate(noFlip),true);
 let s=transition(initialState(),{type:'select',id:tile.id},noFlip).state;
 const result=transition(s,{type:'flip'},noFlip);
 assert.equal(result.changed,false);
 assert.equal(result.state.selected.flipped,false);
 assert.equal(validState(s,noFlip),true);
});

test('retirar último encaixe só devolve a peça mais recente',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 s=act(s,'place',{side:'right'}).state;
 s=take(s,'escoamento-solar');
 s=act(s,'place',{side:'left'}).state;
 const undone=act(s,'removeLast');
 assert.equal(undone.changed,true);
 assert.deepEqual(undone.state.placed,[{id:'evaporacao-condensacao',flipped:false,side:'right'}]);
 assert.deepEqual(undone.state.selected,{id:'escoamento-solar',flipped:false});
 assert.deepEqual(ends(DATA,undone.state.placed),{left:'solar',right:'condensacao'});
 assert.equal(validState(undone.state,DATA),true);
});

test('retirar peça girada devolve exatamente a orientação usada',()=>{
 let s=initialState();
 for(const id of ['evaporacao-condensacao','condensacao-precipitacao','precipitacao-infiltracao']){
  s=take(s,id);s=act(s,'place',{side:'right'}).state;
 }
 s=take(s,'infiltracao-aquifero');
 s=act(s,'flip').state;
 s=act(s,'place',{side:'right'}).state;
 const undone=act(s,'removeLast');
 assert.equal(undone.changed,true);
 assert.deepEqual(undone.state.selected,{id:'infiltracao-aquifero',flipped:true});
 assert.equal(undone.state.placed.length,3);
 assert.equal(validState(undone.state,DATA),true);
});

test('retirar última peça de uma cadeia inicial é ação neutra',()=>{
 const s=initialState();
 const res=act(s,'removeLast');
 assert.equal(res.changed,false);
 assert.deepEqual(res.state,s);
});

test('cancelar seleção não altera encaixes nem consome tentativa',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 const result=act(s,'cancel');
 assert.equal(result.changed,true);
 assert.equal(result.state.selected,null);
 assert.deepEqual(result.state.placed,[]);
 assert.equal(act(result.state,'cancel').changed,false);
});

test('dica dá duas instruções de ponta, sem executar encaixe',()=>{
 const s=initialState();
 const result=act(s,'hint');
 assert.equal(result.changed,true);
 assert.equal(result.state.hints,1);
 assert.deepEqual(result.state.placed,[]);
 assert.equal(result.state.selected,null);
 assert.match(result.message,/ESQUERDA/);
 assert.match(result.message,/DIREITA/);
 assert.match(result.message,/Energia do Sol/);
 assert.match(result.message,/A água líquida se transforma em vapor/);
 assert.equal(validState(result.state,DATA),true);
});

test('sucessivas dicas não criam pontuação nem duplicam peças',()=>{
 let s=initialState();
 for(let i=1;i<=4;i++){
  s=act(s,'hint').state;
  assert.equal(s.hints,i);
  assert.equal(s.placed.length,0);
  assert.equal(validState(s,DATA),true);
 }
});

test('múltiplas ordens aceitas quando correspondências repetidas formam ciclos',()=>{
 const data=ambiguity();
 assert.equal(validate(data),true);
 assert.equal(hasSolution(data),true);
 function run(order){
  let s=initialState();
  for(const id of order){
   s=transition(s,{type:'select',id},data).state;
   const next=transition(s,{type:'place',side:'right'},data);
   assert.equal(next.changed,true,id+' deve encaixar');
   s=next.state;
   assert.equal(validState(s,data),true);
  }
  return s;
 }
 const a=run(['b-c','c-b','b-d','d-b','b-a']);
 const b=run(['b-d','d-b','b-c','c-b','b-a']);
 assert.equal(a.complete,true);
 assert.equal(b.complete,true);
 assert.notDeepEqual(a.placed,b.placed);
 assert.deepEqual(ends(data,a.placed),{left:'a',right:'a'});
});

test('duas peças com par semântico repetido não são confundidas por texto',()=>{
 const data=ambiguity();
 data.tiles.find(x=>x.id==='b-d').right.id='c';
 data.tiles.find(x=>x.id==='d-b').left.id='c';
 assert.equal(data.tiles.filter(t=>t.left.id==='b'&&t.right.id==='c').length,2);
 // A existência do percurso depende do grafo, não da igualdade de rótulos.
 assert.equal(validate(data),true);
 const order=['b-c','c-b','b-d','d-b','b-a'];
 let s=initialState();
 for(const id of order){
  s=transition(s,{type:'select',id},data).state;
  s=transition(s,{type:'place',side:'right'},data).state;
 }
 assert.equal(s.complete,true);
 assert.equal(validState(s,data),true);
});

test('estado persistido não permite peça inicial, ID externo ou peça duplicada',()=>{
 const first=take(initialState(),'evaporacao-condensacao');
 const placed=act(first,'place',{side:'right'}).state;
 const duplicate=clone(placed);
 duplicate.placed.push({...duplicate.placed[0]});
 assert.equal(validState(duplicate,DATA),false);
 const unknown=clone(placed);
 unknown.placed[0].id='fantasma';
 assert.equal(validState(unknown,DATA),false);
 const starter=clone(placed);
 starter.placed[0].id=DATA.starter;
 assert.equal(validState(starter,DATA),false);
});

test('estado com extremidade incorreta, orientação forjada ou lado desconhecido é recusado',()=>{
 const s=act(take(initialState(),'evaporacao-condensacao'),'place',{side:'right'}).state;
 const flipped=clone(s);flipped.placed[0].flipped=true;
 assert.equal(validState(flipped,DATA),false);
 const wrongSide=clone(s);wrongSide.placed[0].side='left';
 assert.equal(validState(wrongSide,DATA),false);
 const invalidSide=clone(s);invalidSide.placed[0].side='middle';
 assert.equal(validState(invalidSide,DATA),false);
 const missingOrientation=clone(s);delete missingOrientation.placed[0].flipped;
 assert.equal(validState(missingOrientation,DATA),false);
});

test('estado concluído adulterado ou seleção de peça usada são rejeitados',()=>{
 const s=act(take(initialState(),'evaporacao-condensacao'),'place',{side:'right'}).state;
 const fake=clone(s);fake.complete=true;
 assert.equal(validState(fake,DATA),false);
 const used=clone(s);used.selected={id:'evaporacao-condensacao',flipped:false};
 assert.equal(validState(used,DATA),false);
 const finished=full();
 const selected=clone(finished);selected.selected={id:'sol-evaporacao',flipped:false};
 assert.equal(validState(selected,DATA),false);
});

test('seleção, orientação e dica podem ser restauradas após JSON round-trip',()=>{
 let s=take(initialState(),'infiltracao-aquifero');
 s=act(s,'flip').state;
 s=act(s,'hint').state;
 const restored=JSON.parse(JSON.stringify(s));
 assert.deepEqual(restored,s);
 assert.equal(validState(restored,DATA),true);
 assert.equal(restored.selected.flipped,true);
 assert.equal(restored.hints,1);
});

test('conclusão congela alterações sem impedir a revisão e reinício',()=>{
 const s=full();
 assert.equal(s.complete,true);
 assert.equal(act(s,'select',{id:'escoamento-solar'}).changed,false);
 assert.equal(act(s,'flip').changed,false);
 assert.equal(act(s,'place',{side:'left'}).changed,false);
 assert.equal(act(s,'removeLast').changed,false);
 assert.equal(act(s,'hint').changed,false);
 assert.deepEqual(act(s,'restart').state,initialState());
});

test('reinício preserva apenas peça inicial e limpa dicas e seleções',()=>{
 let s=take(initialState(),'evaporacao-condensacao');
 s=act(s,'place',{side:'right'}).state;
 s=act(s,'hint').state;
 s=take(s,'escoamento-solar');
 const reset=act(s,'restart');
 assert.equal(reset.changed,true);
 assert.deepEqual(reset.state,initialState());
 assert.deepEqual(chainOf(DATA,reset.state.placed),[{id:DATA.starter,flipped:false}]);
});

test('interface oferece botões sem drag obrigatório e funciona com toque e teclado',()=>{
 assert.match(html,/id="bank"[^>]+role="group"/);
 assert.match(html,/id="selection-box"[^>]+role="group"/);
 assert.match(html,/id="flip"/);
 assert.match(html,/id="cancel"/);
 assert.match(html,/data-place="left"/);
 assert.match(html,/data-place="right"/);
 assert.match(html,/id="remove-last"/);
 assert.match(js,/root\.addEventListener\("click"/);
 assert.doesNotMatch(js,/addEventListener\("pointerdown"/);
 assert.doesNotMatch(js,/addEventListener\("touchstart"/);
 assert.match(html,/Tab, Enter e Espaço/);
 assert.match(html,/@media\(max-width:380px\)/);
});

test('acessibilidade anuncia cada peça com ambas as metades e dá foco útil',()=>{
 assert.match(js,/setAttribute\("aria-label","Peça "/);
 assert.match(js,/\.setAttribute\("aria-pressed"/);
 assert.match(js,/setAttribute\("role","listitem"\)/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="victory"[^>]+tabindex="-1"/);
 assert.match(html,/class="chain-scroll"[^>]+tabindex="0"/);
 assert.match(html,/prefers-reduced-motion:reduce/);
});

test('peças e mensagens são montadas com DOM seguro, sem avaliar conteúdo externo',()=>{
 assert.match(js,/document\.createElement\(tag\)/);
 assert.match(js,/x\.textContent=text/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
 assert.doesNotMatch(js,/\beval\(|\bnew Function\(/);
 assert.doesNotMatch(js,/\bfetch\(|XMLHttpRequest|WebSocket|navigator\.sendBeacon/);
});

test('SDK de progresso é opcional, versionado, limitado e não atribui notas',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/new TextEncoder\(\)/);
 assert.match(js,/12288/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});

test('confirmação para reiniciar só apaga após ato explícito',()=>{
 assert.match(html,/id="reset-confirm"/);
 assert.match(html,/id="confirm-reset"/);
 assert.match(html,/id="cancel-reset"/);
 assert.match(js,/function askRestart\(/);
 assert.match(js,/O reinício apaga encaixes e dicas/);
 assert.match(js,/Reinício cancelado\. Todas as peças foram preservadas/);
});

test('manifestos iguais: perguntar assunto ao professor antes da autoria',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-domino-educativo/examples.json'),'utf8'));
 const general=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='domino-educativo-ciclo-da-agua';
 const entry=local.examples.find(x=>x.id===id),other=general.examples.find(x=>x.id===id);
 assert.ok(entry&&other);
 assert.deepEqual(entry,other);
 assert.equal(entry.path,file);
 assert.match(entry.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(entry.prompt,/Aguarde a resposta/);
 assert.match(entry.prompt,/\.chatgpt\/games\/24-domino-educativo\.md/);
 assert.match(entry.prompt,/\.chatgpt\/games\/RULES\.md/);
});
