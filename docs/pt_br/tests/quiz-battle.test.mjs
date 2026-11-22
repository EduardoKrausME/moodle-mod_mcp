/**
 * Jogo 22 - Batalha de perguntas.
 * Execute: node --test docs/pt_br/tests/quiz-battle.test.mjs
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFile} from 'node:fs/promises';
import {resolve,dirname} from 'node:path';
import {fileURLToPath} from 'node:url';
import {Script} from 'node:vm';

const root=resolve(dirname(fileURLToPath(import.meta.url)),'..');
const file='game-batalha-de-perguntas/escudo-digital.html';
const html=await readFile(resolve(root,file),'utf8');
const js=html.match(/<script>([\s\S]*?)<\/script>/)?.[1];
assert.ok(js,'O HTML deve conter JavaScript inline.');
const pureStart=js.indexOf('function hashSeed(source)'),pureEnd=js.indexOf('// End pure rules.');
assert.ok(pureStart>=0&&pureEnd>pureStart,'Regras isoladas não encontradas.');
const pure=js.slice(js.indexOf('const DATA='),js.indexOf('const root='))+js.slice(pureStart,pureEnd);
const {DATA,hashSeed,shuffled,optionOrder,validate,initialState,gainFor,scoreState,compareScores,validState,transition}=
 new Function(pure+'return {DATA,hashSeed,shuffled,optionOrder,validate,initialState,gainFor,scoreState,compareScores,validState,transition};')();
const clone=v=>structuredClone(v);
const act=(state,type,properties={})=>transition(state,{type,...properties},DATA);
const start=(settings={})=>{
 let s=initialState(DATA);
 if(Object.keys(settings).length)s=act(s,'configure',settings).state;
 return act(s,'start').state;
};
const answer=(s,index=s.index,correct=true)=>{
 const q=DATA.rounds[index],id=correct?q.correctId:q.options.find(o=>o.id!==q.correctId).id;
 return act(s,'answer',{id}).state;
};

test('HTML isolado, responsivo, acessível e sem menus, imagens ou bibliotecas',()=>{
 assert.match(html,/^<!doctype html>/i);
 assert.match(html,/<html lang="pt-BR">/);
 assert.match(html,/<main id="activity">/);
 assert.match(html,/<style>[\s\S]+<\/style>/);
 assert.equal((html.match(/<script\b/g)||[]).length,1);
 assert.doesNotMatch(html,/<(?:header|nav|footer|img|svg|canvas|picture|audio|video)\b/i);
 assert.doesNotMatch(html,/<script[^>]+src=/i);
 assert.doesNotMatch(html,/<link[^>]+rel="stylesheet"/i);
 assert.match(html,/@media\(max-width:550px\)/);
 assert.match(html,/prefers-reduced-motion:reduce/);
 assert.match(html,/prefers-color-scheme:dark/);
 assert.doesNotThrow(()=>new Script(js));
});

test('oito questões de segurança digital têm alternativas exclusivas e explicações',()=>{
 assert.equal(validate(DATA),true);
 assert.equal(DATA.type,'quiz_battle');
 assert.equal(DATA.rounds.length,8);
 assert.ok(DATA.rounds.every(q=>q.options.length===4));
 assert.equal(new Set(DATA.rounds.map(q=>q.id)).size,8);
 assert.ok(DATA.rounds.every(q=>q.prompt.length>20&&q.explanation.length>45));
 assert.ok(DATA.rounds.every(q=>q.options.some(o=>o.id===q.correctId)));
});

test('configuração define solo sem tempo por padrão e robô apenas simulado',()=>{
 assert.equal(DATA.settings.mode,'solo');
 assert.equal(DATA.settings.timeLimitSeconds,20);
 assert.equal(DATA.settings.pointsCorrect,10);
 assert.equal(DATA.settings.comboBonus,5);
 assert.equal(DATA.settings.maxComboBonus,15);
 assert.equal(DATA.settings.simulatedOpponent,true);
 const s=initialState(DATA);
 assert.equal(s.mode,'solo');
 assert.equal(s.timed,false);
 assert.equal(s.started,false);
 assert.equal(validState(s,DATA),true);
});

test('opções duplicadas, IDs repetidos e gabaritos ausentes são recusados',()=>{
 const repeatedRound=clone(DATA);
 repeatedRound.rounds[1].id=repeatedRound.rounds[0].id;
 assert.equal(validate(repeatedRound),false);
 const repeatedChoice=clone(DATA);
 repeatedChoice.rounds[0].options[1].id=repeatedChoice.rounds[0].options[0].id;
 assert.equal(validate(repeatedChoice),false);
 const repeatedLabel=clone(DATA);
 repeatedLabel.rounds[0].options[1].label=repeatedLabel.rounds[0].options[0].label;
 assert.equal(validate(repeatedLabel),false);
 const missing=clone(DATA);
 missing.rounds[0].correctId='missing';
 assert.equal(validate(missing),false);
 const missingExplanation=clone(DATA);
 missingExplanation.rounds[0].explanation='';
 assert.equal(validate(missingExplanation),false);
});

test('configurações de pontuação e adversário inválidas são recusadas',()=>{
 const negative=clone(DATA);
 negative.settings.pointsCorrect=-5;
 assert.equal(validate(negative),false);
 const opponent=clone(DATA);
 opponent.rounds[0].opponentPoints=10000;
 assert.equal(validate(opponent),false);
 const malformed=clone(DATA);
 malformed.settings.timeLimitSeconds=-1;
 assert.equal(validate(malformed),false);
 const mode=clone(DATA);
 mode.settings.mode='multiplayer';
 assert.equal(validate(mode),false);
 const noQuestions=clone(DATA);
 noQuestions.rounds=[];
 assert.equal(validate(noQuestions),false);
});

test('embaralhamento determinístico mantém as alternativas e seus IDs',()=>{
 for(const q of DATA.rounds){
  const first=optionOrder(DATA,q),again=optionOrder(DATA,q);
  assert.deepEqual(first,again);
  assert.deepEqual(new Set(first),new Set(q.options.map(o=>o.id)));
  assert.equal(new Set(first).size,q.options.length);
 }
 assert.equal(hashSeed('fixo'),hashSeed('fixo'));
 assert.deepEqual(shuffled(['a','b','c','d'],'fixo'),shuffled(['a','b','c','d'],'fixo'));
});

test('antes de começar não é possível responder ou avançar',()=>{
 const s=initialState(DATA);
 assert.equal(act(s,'answer',{id:DATA.rounds[0].correctId}).changed,false);
 assert.equal(act(s,'next').changed,false);
 assert.equal(s.answers[0],null);
 assert.equal(s.points,0);
});

test('escolha do modo solo ou robô simulado ocorre somente antes de começar',()=>{
 let s=initialState(DATA);
 s=act(s,'configure',{mode:'simulated'}).state;
 assert.equal(s.mode,'simulated');
 assert.equal(validState(s,DATA),true);
 s=act(s,'start').state;
 assert.equal(s.started,true);
 assert.equal(act(s,'configure',{mode:'solo'}).changed,false);
 assert.equal(act(s,'configure',{timed:true}).changed,false);
 assert.equal(act(s,'start').changed,false);
});

test('configuração de modo inexistente ou cronômetro impossível é bloqueada',()=>{
 const s=initialState(DATA);
 assert.equal(act(s,'configure',{mode:'versus-real'}).changed,false);
 assert.equal(act(s,'configure',{timed:'yes'}).changed,false);
 const withoutClock=clone(DATA);
 withoutClock.settings.timeLimitSeconds=0;
 const result=transition(initialState(withoutClock),{type:'configure',timed:true},withoutClock);
 assert.equal(result.changed,false);
 assert.equal(validate(withoutClock),true);
});

test('primeiro acerto concede dez pontos e primeiro turno do robô permite empate',()=>{
 let s=start({mode:'simulated'});
 const result=act(s,'answer',{id:DATA.rounds[0].correctId});
 assert.equal(result.changed,true);
 assert.equal(result.kind,'good');
 s=result.state;
 assert.equal(s.points,10);
 assert.equal(s.streak,1);
 assert.equal(s.bestStreak,1);
 assert.equal(scoreState(s,DATA).opponent,10);
 assert.equal(compareScores(s.points,scoreState(s,DATA).opponent),'tie');
 assert.equal(validState(s,DATA),true);
});

test('clique duplo não registra outra resposta nem pontuação adicional',()=>{
 let s=start();
 s=act(s,'answer',{id:DATA.rounds[0].correctId}).state;
 const duplicate=act(s,'answer',{id:DATA.rounds[0].correctId});
 assert.equal(duplicate.changed,false);
 assert.equal(duplicate.state.points,10);
 assert.equal(duplicate.state.streak,1);
 const different=DATA.rounds[0].options.find(o=>o.id!==DATA.rounds[0].correctId);
 assert.equal(act(s,'answer',{id:different.id}).changed,false);
});

test('alternativa inexistente é ignorada e não permite avançar',()=>{
 const s=start();
 assert.equal(act(s,'answer',{id:'unknown'}).changed,false);
 assert.equal(act(s,'next').changed,false);
 assert.equal(s.points,0);
 assert.equal(s.answers[0],null);
});

test('segundo e terceiro acertos aumentam combo e pontuação determinística',()=>{
 let s=start();
 for(let i=0;i<3;i++){
  s=answer(s);
  const score=scoreState(s,DATA);
  assert.equal(score.points,s.points);
  assert.equal(score.streak,i+1);
  assert.equal(validState(s,DATA),true);
  if(i<2)s=act(s,'next').state;
 }
 assert.equal(s.points,45);
 assert.equal(s.streak,3);
 assert.equal(s.bestStreak,3);
});

test('combo possui teto de 15 pontos extras por acerto',()=>{
 let s=start();
 for(let i=0;i<5;i++){
  s=answer(s);
  if(i<4)s=act(s,'next').state;
 }
 assert.equal(gainFor(1,DATA.settings),10);
 assert.equal(gainFor(2,DATA.settings),15);
 assert.equal(gainFor(3,DATA.settings),20);
 assert.equal(gainFor(4,DATA.settings),25);
 assert.equal(gainFor(8,DATA.settings),25);
 assert.equal(s.points,10+15+20+25+25);
 assert.equal(s.streak,5);
 assert.equal(s.bestStreak,5);
});

test('resposta errada zera combo mas mantém pontos e melhor sequência',()=>{
 let s=start();
 s=answer(s);
 s=act(s,'next').state;
 s=answer(s);
 s=act(s,'next').state;
 s=answer(s,2,false);
 assert.equal(s.points,25);
 assert.equal(s.streak,0);
 assert.equal(s.bestStreak,2);
 assert.equal(scoreState(s,DATA).wrong,1);
 assert.equal(validState(s,DATA),true);
});

test('acerto após erro reinicia combo em um, sem perder o histórico',()=>{
 let s=start();
 s=answer(s);
 s=act(s,'next').state;
 s=answer(s,1,false);
 s=act(s,'next').state;
 s=answer(s);
 assert.equal(s.streak,1);
 assert.equal(s.bestStreak,1);
 assert.equal(s.points,20);
 assert.equal(validState(s,DATA),true);
});

test('timeout encerra rodada relâmpago sem pontos e com explicação',()=>{
 let s=start({timed:true});
 const out=act(s,'timeout');
 assert.equal(out.changed,true);
 assert.equal(out.kind,'warn');
 assert.ok(out.message.includes(DATA.rounds[0].explanation));
 s=out.state;
 assert.equal(s.answers[0].outcome,'timeout');
 assert.equal(s.answers[0].selectedId,null);
 assert.equal(s.points,0);
 assert.equal(s.streak,0);
 assert.equal(scoreState(s,DATA).timeouts,1);
 assert.equal(act(s,'answer',{id:DATA.rounds[0].correctId}).changed,false);
 assert.equal(validState(s,DATA),true);
});

test('timeout não está disponível quando o relógio está desligado',()=>{
 const s=start();
 const result=act(s,'timeout');
 assert.equal(result.changed,false);
 assert.equal(s.answers[0],null);
 assert.equal(s.points,0);
});

test('avançar exige resposta e não atribui pontos adicionais',()=>{
 let s=start();
 assert.equal(act(s,'next').changed,false);
 s=answer(s);
 const oldPoints=s.points;
 const next=act(s,'next');
 assert.equal(next.changed,true);
 assert.equal(next.state.index,1);
 assert.equal(next.state.answers[1],null);
 assert.equal(next.state.points,oldPoints);
 assert.equal(validState(next.state,DATA),true);
});

test('placar após retomada é idêntico ao placar recalculado pelo histórico',()=>{
 let s=start({mode:'simulated'});
 s=answer(s);
 s=act(s,'next').state;
 s=answer(s,1,false);
 s=act(s,'next').state;
 s=answer(s);
 const copy=JSON.parse(JSON.stringify(s));
 assert.equal(validState(copy,DATA),true);
 assert.equal(scoreState(copy,DATA).points,copy.points);
 assert.equal(scoreState(copy,DATA).streak,copy.streak);
 assert.equal(scoreState(copy,DATA).bestStreak,copy.bestStreak);
 assert.equal(scoreState(copy,DATA).opponent,20);
 assert.equal(copy.points,20);
});

test('placar com adversário simulado pode terminar empatado, vencido ou perdido',()=>{
 assert.equal(compareScores(40,40),'tie');
 assert.equal(compareScores(45,40),'victory');
 assert.equal(compareScores(35,40),'defeat');
 const s=start({mode:'simulated'});
 assert.equal(scoreState(s,DATA).opponent,0);
 assert.equal(DATA.rounds.reduce((n,q)=>n+q.opponentPoints,0),65);
});

test('adversário simulado é apenas local, não existe competição real',()=>{
 assert.equal(DATA.settings.simulatedOpponent,true);
 assert.match(html,/robô inteiramente simulado/i);
 assert.match(html,/não representa um participante real/i);
 assert.doesNotMatch(js,/WebSocket|EventSource|fetch\(|XMLHttpRequest|navigator\.sendBeacon/);
});

test('pontuação forjada, combo adulterado e tempo fraudado são rejeitados',()=>{
 const points=start();
 points.points=5000;
 assert.equal(validState(points,DATA),false);
 const streak=start();
 streak.streak=7;
 assert.equal(validState(streak,DATA),false);
 const best=start();
 best.bestStreak=10;
 assert.equal(validState(best,DATA),false);
 const badTimer=start();
 badTimer.answers[0]={selectedId:null,outcome:'timeout'};
 assert.equal(validState(badTimer,DATA),false);
});

test('resposta inconsistente, ID inexistente ou preenchimento futuro inválido',()=>{
 const wrong=start();
 wrong.answers[0]={selectedId:DATA.rounds[0].correctId,outcome:'wrong'};
 assert.equal(validState(wrong,DATA),false);
 const invalid=start();
 invalid.answers[0]={selectedId:'not-an-option',outcome:'correct'};
 assert.equal(validState(invalid,DATA),false);
 const future=start();
 future.answers[3]={selectedId:DATA.rounds[3].correctId,outcome:'correct'};
 assert.equal(validState(future,DATA),false);
 const skipped=start();
 skipped.index=3;
 assert.equal(validState(skipped,DATA),false);
});

test('uma batalha completa com oito acertos preserva o combo até o resumo',()=>{
 let s=start();
 for(let i=0;i<DATA.rounds.length;i++){
  s=answer(s);
  assert.equal(validState(s,DATA),true);
  s=act(s,'next').state;
  assert.equal(validState(s,DATA),true);
 }
 assert.equal(s.completed,true);
 assert.equal(s.index,8);
 assert.equal(s.points,10+15+20+25+25+25+25+25);
 assert.equal(s.streak,8);
 assert.equal(s.bestStreak,8);
 assert.equal(scoreState(s,DATA).correct,8);
 assert.equal(act(s,'answer',{id:DATA.rounds[0].correctId}).changed,false);
 assert.deepEqual(act(s,'restart').state,initialState(DATA));
});

test('batalha mista com tempo expirado e acertos não falsifica pontuação',()=>{
 let s=start({timed:true,mode:'simulated'});
 s=act(s,'timeout').state;
 s=act(s,'next').state;
 s=answer(s);
 s=act(s,'next').state;
 s=answer(s,2,false);
 s=act(s,'next').state;
 s=answer(s);
 assert.equal(s.streak,1);
 assert.equal(s.bestStreak,1);
 assert.equal(s.points,20);
 assert.equal(scoreState(s,DATA).timeouts,1);
 assert.equal(scoreState(s,DATA).wrong,1);
 assert.equal(scoreState(s,DATA).correct,2);
 assert.equal(validState(s,DATA),true);
});

test('relógio opcional usa tempo monotônico e limpa intervalos ao encerrar',()=>{
 assert.match(js,/performance\.now\(\)/);
 assert.match(js,/setInterval\(/);
 assert.match(js,/clearInterval\(timerId\)/);
 assert.match(js,/function stopTimer\(/);
 assert.match(js,/function syncTimer\(/);
 assert.match(js,/function destroy\(\)\{disposed=true;stopTimer\(\);controller\.abort\(\);\}/);
 assert.match(js,/handleAction\(\{type:"timeout"\}\)/);
 assert.match(html,/id="timer-meter"[^>]+role="progressbar"/);
 assert.match(js,/setAttribute\("aria-valuenow"/);
 assert.match(html,/recomeça a rodada após uma recarga/);
});

test('jogo funciona sem áudio, som, vídeo nem serviços externos',()=>{
 assert.doesNotMatch(html.slice(0,html.indexOf('<script>')),/<(?:audio|video|source|iframe)\b/i);
 assert.doesNotMatch(js,/\bAudioContext\b|\bwebkitAudioContext\b|\bnew Audio\b|\bspeechSynthesis\b/);
 assert.match(html,/funciona sem áudio ou serviços externos/);
 assert.match(js,/root\.addEventListener\("click"/);
});

test('feedback, alternativas e resumo são acessíveis por teclado e leitor de tela',()=>{
 assert.match(html,/id="options"[^>]+role="group"/);
 assert.match(html,/id="feedback"[^>]+aria-live="polite"/);
 assert.match(html,/id="summary-title"[^>]+tabindex="-1"/);
 assert.match(html,/id="next"/);
 assert.match(html,/id="reset"/);
 assert.match(html,/id="mode-simulated"[^>]+aria-pressed="false"/);
 assert.match(html,/id="time-no"[^>]+aria-pressed="true"/);
 assert.match(js,/\.focus\(\{preventScroll:true\}\)/);
 assert.match(js,/b\.setAttribute\("aria-label"/);
 assert.match(js,/textContent=value/);
 assert.doesNotMatch(js,/\.innerHTML\s*=/);
});

test('SDK opcional salva progresso versionado e não gera nota Moodle',()=>{
 assert.match(js,/function getState\(/);
 assert.match(js,/function restoreState\(/);
 assert.match(js,/function destroy\(/);
 assert.match(js,/function handleAction\(/);
 assert.match(js,/progress\.load\(/);
 assert.match(js,/progress\.save\(/);
 assert.match(js,/version:remote\.version/);
 assert.match(js,/TextEncoder/);
 assert.match(js,/12288/);
 assert.doesNotMatch(js,/completion\.complete\(/);
 assert.doesNotMatch(js,/attempt\.finish\(/);
});


test('timeout e confirmação simultânea não permitem dois resultados na mesma rodada',()=>{
 let s=start({timed:true});
 s=act(s,'timeout').state;
 assert.equal(s.answers[0].outcome,'timeout');
 assert.equal(act(s,'answer',{id:DATA.rounds[0].correctId}).changed,false);
 assert.equal(scoreState(s,DATA).timeouts,1);
 assert.equal(validState(s,DATA),true);
 s=start({timed:true});
 s=answer(s);
 assert.equal(act(s,'timeout').changed,false);
 assert.equal(scoreState(s,DATA).correct,1);
 assert.equal(scoreState(s,DATA).timeouts,0);
 assert.equal(validState(s,DATA),true);
});

test('clique na expiração é convertido em timeout antes da confirmação',()=>{
 assert.match(js,/action\.type==="answer"&&state\.started&&state\.timed/);
 assert.match(js,/performance\.now\(\)>=deadline/);
 assert.match(js,/stopTimer\(\);action=\{type:"timeout"\}/);
 assert.match(js,/if\(answered\)return/);
});

test('aba oculta continua relógio e expiração é aplicada ao retornar',()=>{
 assert.match(js,/document\.addEventListener\("visibilitychange"/);
 assert.match(js,/if\(document\.hidden\|\|disposed/);
 assert.match(js,/deadline-performance\.now\(\)/);
 assert.match(js,/if\(remaining===0\)\{stopTimer\(\);handleAction\(\{type:"timeout"\}\);\}/);
 assert.match(html,/continua correndo com a aba oculta/);
 assert.match(html,/tempo expirado é aplicado no retorno/);
});

test('reinício com respostas exige confirmar ou cancelar sem perder progresso',()=>{
 assert.match(html,/id="reset-confirm"/);
 assert.match(html,/id="confirm-reset"/);
 assert.match(html,/id="cancel-reset"/);
 assert.match(js,/state\?\.started&&state\.answers\.some\(a=>a!==null\)/);
 assert.match(js,/if\(b\.id==="cancel-reset"\)/);
 assert.match(js,/if\(b\.id==="confirm-reset"\)/);
 assert.match(html,/Reiniciar apaga as respostas e o placar/);
});

test('manifestos têm mesmo prompt, que pergunta ao professor e aguarda resposta',async()=>{
 const local=JSON.parse(await readFile(resolve(root,'game-batalha-de-perguntas/examples.json'),'utf8'));
 const global=JSON.parse(await readFile(resolve(root,'games/examples.json'),'utf8'));
 const id='batalha-de-perguntas-escudo-digital';
 const a=local.examples.find(e=>e.id===id),b=global.examples.find(e=>e.id===id);
 assert.ok(a&&b);
 assert.deepEqual(a,b);
 assert.equal(a.path,file);
 assert.match(a.prompt,/Qual tipo de conteúdo você deseja trabalhar/);
 assert.match(a.prompt,/Aguarde a resposta/);
 assert.match(a.prompt,/\.chatgpt\/games\/22-batalha-de-perguntas\.md/);
});
